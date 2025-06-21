<?php
session_start();
require 'service/database.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Sesi tidak valid. Silakan login kembali.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $password_input = $_POST['password'];

    if (empty($password_input)) {
        $response['message'] = 'Password harus diisi untuk konfirmasi.';
        echo json_encode($response);
        exit();
    }

    // 1. Ambil hash password dari database untuk verifikasi
    $stmt = $db->prepare("SELECT password FROM user WHERE id = ?");
    if (!$stmt) {
        $response['message'] = 'Gagal menyiapkan pernyataan: ' . $db->error;
        echo json_encode($response);
        exit();
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // 2. Verifikasi password yang dimasukkan
        $password_benar = false;
        if (strlen($user['password']) === 32) { // Cek jika ini MD5
            if (md5($password_input) === $user['password']) {
                $password_benar = true;
            }
        } else { // Asumsikan ini hash modern
            if (password_verify($password_input, $user['password'])) {
                $password_benar = true;
            }
        }

        if ($password_benar) {
            // 3. Jika password benar, hapus akun
            $stmt_delete = $db->prepare("DELETE FROM user WHERE id = ?");
            if (!$stmt_delete) {
                $response['message'] = 'Gagal menyiapkan pernyataan penghapusan: ' . $db->error;
                echo json_encode($response);
                exit();
            }

            $stmt_delete->bind_param("i", $userId);

            if ($stmt_delete->execute()) {
                // Hapus akun berhasil, hancurkan session
                session_destroy();
                $response['status'] = 'success';
                $response['message'] = 'Akun Anda telah berhasil dihapus.';
            } else {
                $response['message'] = 'Gagal menghapus akun dari database: ' . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
            $response['message'] = 'Password yang Anda masukkan salah.';
        }
    } else {
        $response['message'] = 'Data pengguna tidak ditemukan.';
    }
}

$db->close();
echo json_encode($response);
exit();
?>
