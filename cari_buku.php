<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "service/database.php"; 

// Pastikan variabel koneksi ($db atau $koneksi) sudah benar
if (!isset($db)) { global $koneksi; $db = $koneksi; }

// Ambil kata kunci dari JavaScript
$query = $_GET['q'] ?? '';

// Jika kata kunci kosong, tidak perlu melakukan apa-apa
if (empty(trim($query))) {
    exit();
}

// Persiapkan kata kunci untuk pencarian LIKE
$search_term = "%" . $query . "%";

$current_user_id = $_SESSION['user_id'] ?? 0;

// Query untuk mencari di judul atau penulis
// Kita juga tetap mengambil status bookmark seperti di halaman utama
$sql_search = "SELECT 
                    b.*, 
                    k.nama_kategori,
                    CASE WHEN bm.id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked 
                FROM 
                    buku b
                LEFT JOIN 
                    kategori_buku k ON b.kategori_id = k.id
                LEFT JOIN 
                    bookmarks bm ON b.id = bm.id_buku AND bm.id_user = ?
                WHERE 
                    b.judul LIKE ? OR b.penulis LIKE ?
                ORDER BY
                    b.judul ASC";

$stmt = $db->prepare($sql_search);
// Bind parameter: i untuk integer (id_user), s untuk string (kata kunci)
$stmt->bind_param("iss", $current_user_id, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Jika buku ditemukan, tampilkan dalam bentuk grid
    echo '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">';
    while ($buku = $result->fetch_assoc()) {
        $is_bookmarked = $buku['is_bookmarked'];
        $bookmark_icon_class = $is_bookmarked ? 'fas text-indigo-600' : 'far';
        
        // Tampilkan setiap kartu buku (kode ini sama seperti di Dashboard)
        echo '<div class="book-card bg-white rounded-lg shadow overflow-hidden transition duration-300">';
        echo '    <div class="relative pb-3/4">';
        echo '        <img class="w-full h-48 object-cover" src="Upload/covers/' . htmlspecialchars($buku['cover'] ?? 'default.png') . '" alt="Cover Buku">';
        echo '        <button class="bookmark-btn absolute top-2 right-2 p-2 bg-white rounded-full ..." data-id="' . $buku['id'] . '">';
        echo '            <i class="' . $bookmark_icon_class . ' fa-bookmark"></i>';
        echo '        </button>';
        echo '    </div>';
        echo '    <div class="p-3">';
        echo '        <h3 class="font-semibold text-gray-800 truncate">' . htmlspecialchars($buku['judul']) . '</h3>';
        echo '        <p class="text-sm text-gray-600">' . htmlspecialchars($buku['penulis']) . '</p>';
        echo '        <div class="flex justify-between items-center mt-2">';
        echo '            <span class="text-xs bg-indigo-100 ...">' . htmlspecialchars($buku['nama_kategori'] ?? 'Tanpa Kategori') . '</span>';
        echo '            <a href="#" class="detail-link text-indigo-600 ..." data-id="' . $buku['id'] . '">Detail</a>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';
    }
    echo '</div>';
} else {
    // Jika tidak ada hasil
    echo '<div class="text-center py-10">';
    echo '  <i class="fas fa-search fa-3x text-gray-400 mb-4"></i>';
    echo '  <p class="text-gray-500">Buku dengan kata kunci "<strong>' . htmlspecialchars($query) . '</strong>" tidak ditemukan.</p>';
    echo '</div>';
}

$stmt->close();
?>