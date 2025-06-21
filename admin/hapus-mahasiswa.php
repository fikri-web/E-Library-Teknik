<?php
session_start();
require_once '../service/database.php';

if(isset($_GET['id']) && !empty(trim($_GET['id']))){
    $id = trim($_GET['id']);
    
    // 1. Ambil nama file foto dari DB sebelum dihapus
    $sql_select_foto = "SELECT profile_photo FROM user WHERE id = ?";
    if($stmt_select = mysqli_prepare($db, $sql_select_foto)){
        mysqli_stmt_bind_param($stmt_select, "i", $id);
        mysqli_stmt_execute($stmt_select);
        $result_foto = mysqli_stmt_get_result($stmt_select);
        if($row = mysqli_fetch_assoc($result_foto)){
            $nama_foto = $row['foto'];
            // 2. Hapus file foto dari folder jika ada
            if(!empty($nama_foto) && file_exists('../uploads/profile_photos/' . $nama_foto)){
                unlink('../uploads/profile_photos/' . $nama_foto);
            }
        }
        mysqli_stmt_close($stmt_select);
    }

    // 3. Hapus record mahasiswa dari database
    $sql_delete = "DELETE FROM user WHERE id = ?";
    if($stmt_delete = mysqli_prepare($db, $sql_delete)){
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        
        if(mysqli_stmt_execute($stmt_delete)){
            $_SESSION['pesan'] = "Data mahasiswa berhasil dihapus.";
        } else {
            $_SESSION['pesan_error'] = "Gagal menghapus data mahasiswa.";
        }
        mysqli_stmt_close($stmt_delete);
    }
}

header("location: mahasiswa.php");
exit();
?>