<?php
// File: ambil_bookmarks.php
session_start();
require_once "service/database.php";

// Pastikan variabel koneksi ($db atau $koneksi) sudah benar
if (!isset($db)) { 
    // Jika Anda menggunakan nama variabel $koneksi di file database.php
    if (isset($koneksi)) {
        $db = $koneksi;
    } else {
        echo '<p class="p-6 text-center text-red-500">Koneksi database gagal.</p>';
        exit();
    }
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo '<p class="p-6 text-center text-gray-500">Silakan login terlebih dahulu.</p>';
    exit();
}

$current_user_id = $_SESSION['user_id'];

// PERBAIKAN 1: Menambahkan koma (,) yang hilang antara b.stok dan k.nama_kategori
$sql_my_bookmarks = "SELECT 
                        b.id, b.judul, b.penulis, b.cover, b.deskripsi,
                        b.penerbit, b.tahun_terbit, b.stok, k.nama_kategori
                     FROM buku b 
                     JOIN bookmarks bm ON b.id = bm.id_buku
                     LEFT JOIN kategori_buku k ON b.kategori_id = k.id
                     WHERE bm.id_user = ?
                     ORDER BY bm.tanggal_bookmark DESC";

$stmt_bm = $db->prepare($sql_my_bookmarks);
if ($stmt_bm === false) {
    // Menangani error jika prepare statement gagal
    echo '<p class="p-6 text-center text-red-500">Terjadi kesalahan pada query database.</p>';
    exit();
}

$stmt_bm->bind_param("i", $current_user_id);
$stmt_bm->execute();
$my_bookmarks_result = $stmt_bm->get_result();

// Loop dan cetak HTML-nya
if ($my_bookmarks_result->num_rows > 0) {
    while ($book = $my_bookmarks_result->fetch_assoc()) {
        $coverPath = !empty($book['cover']) ? 'Upload/covers/' . htmlspecialchars($book['cover']) : 'path/to/default-cover.png'; // Pastikan path ini benar
?>
        <div class="p-4 hover:bg-gray-50 transition duration-150">
            <div class="flex items-start space-x-4">
                <img class="h-24 w-16 object-cover rounded flex-shrink-0" src="<?= $coverPath; ?>" alt="Cover <?= htmlspecialchars($book['judul']); ?>">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($book['judul']); ?></h3>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($book['penulis']); ?></p>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($book['deskripsi']); ?></p>
                    <div class="mt-2 flex justify-between items-center">
                        <a href="#" class="detail-link text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                           data-id="<?= $book['id']; ?>"
                           data-judul="<?= htmlspecialchars($book['judul']); ?>"
                           data-penulis="<?= htmlspecialchars($book['penulis']); ?>"
                           data-penerbit="<?= htmlspecialchars($book['penerbit'] ?? 'Tidak diketahui'); ?>"
                           data-tahun="<?= htmlspecialchars($book['tahun_terbit'] ?? '-'); ?>"
                           data-deskripsi="<?= htmlspecialchars($book['deskripsi'] ?? 'Deskripsi tidak tersedia.'); ?>"
                           data-cover="<?= $coverPath; ?>"
                           data-stok="<?= htmlspecialchars($book['stok'] ?? 0); ?>"> Lihat Detail
                        </a>
                        </div>
                </div>
            </div>
        </div>
<?php
    }
} else {
    // Tampilkan pesan ini jika tidak ada bookmark
    echo '<p class="p-6 text-center text-gray-500">Anda belum memiliki bookmark.</p>';
}
$stmt_bm->close();
?>