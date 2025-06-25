<?php
session_start();

require_once 'service/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$id_buku = $_POST['id_buku'] ?? null;

if (!$id_buku) {
    echo json_encode(['status' => 'error', 'message' => 'ID buku tidak valid']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek apakah sudah dipinjam oleh user yang sama
$stmt = $db->prepare("SELECT * FROM bookmarks WHERE id_buku = ? AND id_user = ?");
$stmt->bind_param("ii", $id_buku, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Buku sudah dipinjam']);
    exit;
}

// Cek stok buku
$stmt = $db->prepare("SELECT stok FROM buku WHERE id = ?");
$stmt->bind_param("i", $id_buku);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book || $book['stok'] <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Stok buku habis']);
    exit;
}

// Tambah ke bookmarks
$stmt = $db->prepare("INSERT INTO bookmarks (id_buku, id_user) VALUES (?, ?)");
$stmt->bind_param("ii", $id_buku, $user_id);
if ($stmt->execute()) {
    // Kurangi stok
    $stmt = $db->prepare("UPDATE buku SET stok = stok - 1 WHERE id = ?");
    $stmt->bind_param("i", $id_buku);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Buku berhasil dipinjam']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal meminjam buku']);
}
?>
