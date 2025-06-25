<?php
/**
 * File: cari_buku.php
 * Deskripsi: Skrip untuk menangani pencarian buku melalui AJAX dari halaman dashboard.
 */

// Sesi dimulai untuk mengakses data login pengguna
session_start();

// ====== PENGATURAN UNTUK DEBUGGING ======
// Baris ini akan menampilkan semua error PHP. Sangat membantu saat development.
// Hapus atau beri komentar pada baris ini jika website sudah berjalan (production).
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// =======================================


// ====== KONEKSI & KEAMANAN ======

// 1. Memuat file koneksi database.
// PASTIKAN NAMA FILE INI BENAR! Jika nama file Anda koneksi.php, ganti menjadi koneksi.php
require_once "service/database.php"; 

// 2. Memastikan variabel koneksi database ($db) ada.
// Kode ini mengatasi jika nama variabel di file koneksi adalah $koneksi, bukan $db.
if (!isset($db)) { 
    if (isset($koneksi)) {
        $db = $koneksi;
    } else {
        // Jika tidak ada koneksi sama sekali, hentikan skrip dengan pesan error.
        die('<p class="p-6 text-center text-red-500">Error: Koneksi database tidak dapat ditemukan.</p>');
    }
}

// 3. Memastikan pengguna sudah login.
if (!isset($_SESSION['user_id'])) {
    die('<p class="text-red-500 text-center py-10">Akses ditolak. Silakan login terlebih dahulu.</p>');
}
$current_user_id = $_SESSION['user_id'];


// ====== PROSES INPUT ======

// 4. Ambil kata kunci pencarian dari URL (dikirim oleh JavaScript) dengan aman.
$query = trim($_GET['q'] ?? '');

// Jika kata kunci kosong, jangan lakukan pencarian.
if (empty($query)) {
    echo '<p class="text-gray-500 text-center py-10">Silakan masukkan kata kunci pencarian.</p>';
    exit();
}


// ====== QUERY DATABASE ======

// 5. Siapkan query pencarian yang aman menggunakan Prepared Statements.
$search_term = "%" . $query . "%";
$sql = "SELECT 
            b.id, b.judul, b.penulis, b.cover, b.penerbit, b.tahun_terbit, b.stok, b.deskripsi, k.nama_kategori,
            CASE WHEN bm.id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM buku b
        LEFT JOIN kategori_buku k ON b.kategori_id = k.id
        LEFT JOIN bookmarks bm ON b.id = bm.id_buku AND bm.id_user = ?
        WHERE b.judul LIKE ? OR b.penulis LIKE ?
        ORDER BY b.judul ASC";

$stmt = $db->prepare($sql);

// Jika persiapan query gagal (misal: ada error di SQL), hentikan skrip.
if ($stmt === false) {
    die("Error saat mempersiapkan query: " . $db->error);
}

// 6. Bind parameter untuk keamanan (mencegah SQL Injection).
$stmt->bind_param("iss", $current_user_id, $search_term, $search_term);

// 7. Eksekusi query.
$stmt->execute();
$result = $stmt->get_result();


// ====== TAMPILKAN HASIL ======

// 8. Tampilkan header untuk hasil pencarian.
echo '<h2 class="text-2xl font-bold text-gray-800 mb-4">Hasil Pencarian untuk: <span class="text-indigo-600">' . htmlspecialchars($query) . '</span></h2>';

if ($result->num_rows > 0) {
    // Jika ada hasil, tampilkan dalam format grid.
    echo '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">';
    while ($buku = $result->fetch_assoc()) {
        $coverPath = !empty($buku['cover']) ? 'Upload/covers/' . htmlspecialchars($buku['cover']) : 'path/to/default-cover.png';
        // Tampilkan setiap kartu buku. Struktur HTML dan semua atribut data-*
        // harus sama persis dengan yang ada di Dashboard.php agar JavaScript bisa berfungsi.
        ?>
        <div class="book-card bg-white rounded-lg shadow overflow-hidden transition duration-300">
            <div class="relative w-full" style="padding-bottom: 140%;">
                <img class="absolute inset-0 w-full h-full object-cover" src="<?= $coverPath; ?>" alt="Cover Buku <?= htmlspecialchars($buku['judul']); ?>">
            </div>
            <div class="p-3 flex flex-col flex-grow">
                <h3 class="font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($buku['judul']); ?>"><?= htmlspecialchars($buku['judul']); ?></h3>
                <p class="text-sm text-gray-600 whitespace-nowrap overflow-hidden text-ellipsis"><?= htmlspecialchars($buku['penulis']); ?></p>
                <div class="flex justify-between items-center mt-2 pt-2 border-t">
                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded"><?= htmlspecialchars($buku['nama_kategori'] ?? 'N/A'); ?></span>
                    
                    <a href="#" 
                        class="detail-link text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                        data-id="<?= $buku['id']; ?>"
                        data-judul="<?= htmlspecialchars($buku['judul']); ?>"
                        data-penulis="<?= htmlspecialchars($buku['penulis']); ?>"
                        data-penerbit="<?= htmlspecialchars($buku['penerbit'] ?? 'N/A'); ?>"
                        data-tahun="<?= htmlspecialchars($buku['tahun_terbit'] ?? '-'); ?>"
                        data-stok="<?= htmlspecialchars($buku['stok'] ?? '0'); ?>"
                        data-deskripsi="<?= htmlspecialchars($buku['deskripsi'] ?? 'Deskripsi tidak tersedia.'); ?>"
                        data-cover="<?= $coverPath; ?>">
                        Detail
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>'; // Penutup grid container
} else {
    // Jika tidak ada hasil, tampilkan pesan.
    echo '<div class="text-center py-10">';
    echo '  <i class="fas fa-search fa-3x text-gray-400 mb-4"></i>';
    echo '  <p class="text-gray-500">Buku dengan kata kunci "<strong>' . htmlspecialchars($query) . '</strong>" tidak ditemukan.</p>';
    echo '</div>';
}

// 9. Tutup statement dan koneksi untuk membersihkan resource.
$stmt->close();
$db->close();
?>