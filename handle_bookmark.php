<?php
// File: handle_bookmark.php

session_start();
header('Content-Type: application/json');

// Ganti 'service/database.php' sesuai dengan path file koneksi Anda
require_once "service/database.php"; 

// Pastikan Anda menggunakan variabel koneksi yang benar, contoh: $db atau $koneksi
// Saya asumsikan nama variabel koneksi Anda adalah $db dari file login Anda.
// Jika berbeda, sesuaikan variabel $db di bawah ini dengan nama variabel koneksi Anda.
if (!isset($db)) {
    global $koneksi; 
    $db = $koneksi;
}

// 1. Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk bookmark.']);
    exit();
}

// 2. Cek apakah id_buku dikirim dari JavaScript
if (!isset($_POST['id_buku'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID Buku tidak ditemukan.']);
    exit();
}

$id_user = $_SESSION['user_id'];
$id_buku = (int)$_POST['id_buku'];

// 3. Cek apakah bookmark sudah ada di database
$stmt_check = $db->prepare("SELECT id FROM bookmarks WHERE id_user = ? AND id_buku = ?");
$stmt_check->bind_param("ii", $id_user, $id_buku);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // 4. Jika sudah ada, hapus bookmark (un-bookmark)
    $stmt_del = $db->prepare("DELETE FROM bookmarks WHERE id_user = ? AND id_buku = ?");
    $stmt_del->bind_param("ii", $id_user, $id_buku);
    if ($stmt_del->execute()) {
        // Kirim pesan sukses 'removed' ke JavaScript
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    }
} else {
    // 5. Jika belum ada, tambahkan bookmark baru
    $stmt_ins = $db->prepare("INSERT INTO bookmarks (id_user, id_buku) VALUES (?, ?)");
    $stmt_ins->bind_param("ii", $id_user, $id_buku);
    if ($stmt_ins->execute()) {
        // Kirim pesan sukses 'bookmarked' ke JavaScript
        echo json_encode(['status' => 'success', 'action' => 'bookmarked']);
    }
}

// Tutup statement dan koneksi jika perlu
$stmt_check->close();
// $db->close(); // Jangan ditutup jika skrip lain masih butuh koneksi

?>