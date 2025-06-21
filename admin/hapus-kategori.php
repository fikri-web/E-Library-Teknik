<?php
session_start();
require_once '../service/database.php';

// Cek apakah ID ada di URL
if(isset($_GET['id']) && !empty(trim($_GET['id']))){
    $id = trim($_GET['id']);
    
    $sql = "DELETE FROM kategori_buku WHERE id = ?";
    
    if($stmt = mysqli_prepare($db, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if(mysqli_stmt_execute($stmt)){
            $_SESSION['pesan'] = "Data berhasil dihapus.";
        } else {
            $_SESSION['pesan_error'] = "Gagal menghapus data.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Redirect kembali ke halaman utama
header("location: kategori.php");
exit();
?>