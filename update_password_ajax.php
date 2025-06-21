<?php
session_start();
require 'service/database.php';

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Sesi Anda telah berakhir. Silakan login kembali.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // --- Validasi Input ---
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $response['message'] = 'Semua field wajib diisi.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    if (strlen($newPassword) < 5) {
        $response['message'] = 'Password baru minimal harus 5 karakter.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        $response['message'] = 'Password baru dan konfirmasi password tidak cocok.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // --- Proses Utama ---
    // 1. Ambil HASH password saat ini dari database
    $stmt = $db->prepare("SELECT password FROM user WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // 2. Verifikasi password saat ini
       // UBAH MENJADI SEPERTI INI:
    if (md5($currentPassword) === $user['password'])  {
            // Password lama cocok, lanjutkan untuk update
            
            // 3. HASH password yang baru (SANGAT PENTING!)
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // 4. Update password baru di database
            $stmt_update = $db->prepare("UPDATE user SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $newPasswordHash, $userId);

            if ($stmt_update->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Password berhasil diperbarui!';
            } else {
                $response['message'] = 'Gagal memperbarui password di database.';
            }
            $stmt_update->close();
        } else {
            // Password lama tidak cocok
            $response['message'] = 'Password lama yang Anda masukkan salah.';
        }
    } else {
        $response['message'] = 'User tidak ditemukan.';
    }
} else {
    $response['message'] = 'Metode pengiriman tidak valid.';
}

$db->close();
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>