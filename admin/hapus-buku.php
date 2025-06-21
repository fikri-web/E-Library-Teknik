<?php
session_start();
require_once '../service/database.php';

if(isset($_GET['id']) && !empty(trim($_GET['id']))){
    $id = trim($_GET['id']);
    
    // 1. Ambil nama file cover dari database sebelum menghapus record
    $sql_select_cover = "SELECT cover FROM buku WHERE id = ?";
    if($stmt_select = mysqli_prepare($db, $sql_select_cover)){
        mysqli_stmt_bind_param($stmt_select, "i", $id);
        mysqli_stmt_execute($stmt_select);
        $result_cover = mysqli_stmt_get_result($stmt_select);
        if($row = mysqli_fetch_assoc($result_cover)){
            $nama_cover = $row['cover'];
            // 2. Hapus file gambar dari folder jika ada
            if(!empty($nama_cover) && file_exists('../upload/covers/' . $nama_cover)){
                unlink('../upload/covers/' . $nama_cover);
            }
        }
        mysqli_stmt_close($stmt_select);
    }

    // 3. Hapus record buku dari database
    $sql_delete = "DELETE FROM buku WHERE id = ?";
    if($stmt_delete = mysqli_prepare($db, $sql_delete)){
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        
        if(mysqli_stmt_execute($stmt_delete)){
            $_SESSION['pesan'] = "Data buku berhasil dihapus.";
        } else {
            $_SESSION['pesan_error'] = "Gagal menghapus data buku.";
        }
        mysqli_stmt_close($stmt_delete);
    }
}

header("location: buku.php");
exit();
?>