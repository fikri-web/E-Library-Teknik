<?php
header('Content-Type: application/json');
require 'service/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID Kategori tidak valid.']);
    exit();
}
$id_kategori_dipilih = (int)$_GET['id'];

$response = [
    'nama_kategori' => 'Tidak Dikenal',
    'buku' => []
];

// Ambil nama kategori
$sql_nama_kategori = "SELECT nama_kategori FROM kategori_buku WHERE id = ?";
$stmt_nama = $db->prepare($sql_nama_kategori);
$stmt_nama->bind_param("i", $id_kategori_dipilih);
$stmt_nama->execute();
$result_nama = $stmt_nama->get_result();
if ($kategori = $result_nama->fetch_assoc()) {
    $response['nama_kategori'] = $kategori['nama_kategori'];
}
$stmt_nama->close();

// Ambil daftar buku + stok
$sql_buku = "SELECT cover, judul, penulis, penerbit, tahun_terbit, stok FROM buku WHERE kategori_id = ?";
$stmt_buku = $db->prepare($sql_buku);
$stmt_buku->bind_param("i", $id_kategori_dipilih);
$stmt_buku->execute();
$result_buku = $stmt_buku->get_result();

while ($buku = $result_buku->fetch_assoc()) {
    $response['buku'][] = $buku;
}
$stmt_buku->close();

// Kirim sebagai JSON
echo json_encode($response);
?>
