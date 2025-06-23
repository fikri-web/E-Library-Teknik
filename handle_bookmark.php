<?php
// File: handle_bookmark.php

session_start();
header('Content-Type: application/json');

// 1. Panggil koneksi database
require_once "service/database.php"; 

// Pastikan variabel koneksi ($db) tersedia
if (!isset($db)) {
    global $koneksi; 
    $db = $koneksi;
}

// 2. Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk bookmark.']);
    exit();
}

// 3. Cek apakah data id_buku dikirim
if (!isset($_POST['id_buku'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID Buku tidak ditemukan.']);
    exit();
}

// 4. Ambil data user dan buku
$id_user = $_SESSION['user_id'];
$id_buku = (int)$_POST['id_buku'];

// 5. Cek apakah bookmark untuk buku ini sudah ada
$stmt_check = $db->prepare("SELECT id FROM bookmarks WHERE id_user = ? AND id_buku = ?");
$stmt_check->bind_param("ii", $id_user, $id_buku);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // 6a. Jika SUDAH ADA, maka HAPUS bookmark
    $stmt_del = $db->prepare("DELETE FROM bookmarks WHERE id_user = ? AND id_buku = ?");
    $stmt_del->bind_param("ii", $id_user, $id_buku);
    if ($stmt_del->execute()) {
        // Kirim pesan 'removed' ke JavaScript
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus bookmark.']);
    }
} else {
    // 6b. Jika BELUM ADA, maka TAMBAHKAN bookmark baru
    $stmt_ins = $db->prepare("INSERT INTO bookmarks (id_user, id_buku) VALUES (?, ?)");
    $stmt_ins->bind_param("ii", $id_user, $id_buku);
    if ($stmt_ins->execute()) {
        // =================================================================
        // INI BAGIAN YANG DIPERBAIKI
        // Mengirim 'added' agar sesuai dengan yang diperiksa JavaScript
        // =================================================================
        echo json_encode(['status' => 'success', 'action' => 'added']);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambah bookmark.']);
    }
}

// 7. Tutup statement
$stmt_check->close();
// Disarankan untuk tidak menutup koneksi $db jika ada skrip lain yang masih berjalan

?>