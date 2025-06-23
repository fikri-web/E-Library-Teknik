<?php
header('Content-Type: application/json');
require 'service/database.php';

if (!isset($db)) {
    global $koneksi;
    $db = $koneksi;
}

$buku_list = [];

// =========================================================================
// PERUBAHAN DI SINI: Menggunakan LEFT JOIN untuk mengambil nama_kategori
// =========================================================================
$sql = "SELECT 
            b.id, 
            b.cover, 
            b.judul, 
            b.penulis, 
            b.penerbit, 
            b.tahun_terbit, 
            b.stok, 
            b.deskripsi, 
            k.nama_kategori 
        FROM 
            buku b
        LEFT JOIN 
            kategori_buku k ON b.kategori_id = k.id
        ORDER BY 
            b.judul ASC";

$result = $db->query($sql);

if ($result) {
    while ($buku = $result->fetch_assoc()) {
        $buku_list[] = $buku;
    }
}

echo json_encode($buku_list);
$db->close();
?>