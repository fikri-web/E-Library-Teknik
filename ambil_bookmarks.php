<?php
session_start();
require_once "service/database.php";

// Pastikan koneksi
if (!isset($db)) {
    if (isset($koneksi)) {
        $db = $koneksi;
    } else {
        echo '<p class="p-6 text-center text-red-500">Koneksi database gagal.</p>';
        exit();
    }
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo '<p class="p-6 text-center text-gray-500">Silakan login terlebih dahulu.</p>';
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Query untuk mengambil semua buku yang di-bookmark oleh user ini.
$sql_my_bookmarks = "SELECT 
                        b.id, b.judul, b.penulis, b.cover, b.deskripsi,
                        b.penerbit, b.tahun_terbit, b.stok, k.nama_kategori
                    FROM buku b 
                    JOIN bookmarks bm ON b.id = bm.id_buku
                    LEFT JOIN kategori_buku k ON b.kategori_id = k.id
                    WHERE bm.id_user = ?
                    ORDER BY bm.tanggal_bookmark DESC";

$stmt_bm = $db->prepare($sql_my_bookmarks);
if (!$stmt_bm) {
    echo '<p class="p-6 text-center text-red-500">Terjadi kesalahan pada query database.</p>';
    exit();
}

$stmt_bm->bind_param("i", $current_user_id);
$stmt_bm->execute();
$my_bookmarks_result = $stmt_bm->get_result();

// Tampilkan hasil
if ($my_bookmarks_result->num_rows > 0) {
    while ($book = $my_bookmarks_result->fetch_assoc()) {
        $coverPath = !empty($book['cover']) ? 'Upload/covers/' . htmlspecialchars($book['cover']) : 'path/to/default-cover.png';
?>
        <div class="bookmark-item relative p-4 hover:bg-gray-50 transition-all duration-300">
            <button 
                class="remove-bookmark-btn absolute top-2 right-2 text-gray-400 hover:text-red-600 hover:scale-125 transition-transform"
                data-id="<?= $book['id']; ?>"
                title="Hapus bookmark">
                <i class="fas fa-times"></i>
            </button>
            <div class="flex items-start space-x-4">
                <img class="h-24 w-16 object-cover rounded flex-shrink-0" src="<?= $coverPath; ?>" alt="Cover <?= htmlspecialchars($book['judul']); ?>">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($book['judul']); ?></h3>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($book['penulis']); ?></p>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($book['deskripsi']); ?></p>
                    <div class="mt-2 flex justify-end">
                        
                        <button
                            class="detail-link text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            data-id="<?= htmlspecialchars($book['id']); ?>"
                            data-judul="<?= htmlspecialchars($book['judul']); ?>"
                            data-penulis="<?= htmlspecialchars($book['penulis']); ?>"
                            data-penerbit="<?= htmlspecialchars($book['penerbit'] ?? 'Tidak diketahui'); ?>"
                            data-tahun="<?= htmlspecialchars($book['tahun_terbit'] ?? '-'); ?>"
                            data-deskripsi="<?= htmlspecialchars($book['deskripsi'] ?? 'Deskripsi tidak tersedia.'); ?>"
                            data-cover="<?= $coverPath; ?>"
                            data-stok="<?= htmlspecialchars($book['stok'] ?? 0); ?>"
                            data-aksi="kembalikan">  Kembalikan buku                      </button>

                    </div>
                </div>
            </div>
        </div>
<?php
    }
} else {
    echo '<p id="no-bookmarks-message" class="p-6 text-center text-gray-500">Anda belum memiliki bookmark.</p>';
}
$stmt_bm->close();
?>