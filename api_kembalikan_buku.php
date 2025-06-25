<?php
session_start();
require_once 'service/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$id_buku = $_POST['id_buku'] ?? null;
$id_user = $_SESSION['user_id'];

if (!$id_buku) {
    echo json_encode(['status' => 'error', 'message' => 'ID buku tidak valid']);
    exit;
}

// Hapus dari bookmarks
$stmt = $db->prepare("DELETE FROM bookmarks WHERE id_buku = ? AND id_user = ?");
$stmt->bind_param("ii", $id_buku, $id_user);

if ($stmt->execute()) {
    // Tambahkan kembali stok buku
    $update = $db->prepare("UPDATE buku SET stok = stok + 1 WHERE id = ?");
    $update->bind_param("i", $id_buku);
    $update->execute();

    echo json_encode(['status' => 'success', 'message' => 'Buku berhasil dikembalikan']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengembalikan buku']);
}
?>
