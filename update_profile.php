<?php
session_start();
require 'service/database.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $prodi = $_POST['prodi'];

    $sql = "UPDATE user SET nama = ?, email = ?, prodi = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sssi", $nama, $email, $prodi, $userId);

    if ($stmt->execute()) {
        $_SESSION['nama'] = $nama;
        $response = ['status' => 'success', 'message' => 'Profil berhasil diperbarui!', 'newName' => $nama];
    } else {
        $response['message'] = 'Gagal memperbarui profil.';
    }
    $stmt->close();
} else {
    $response['message'] = 'Sesi tidak valid.';
}

echo json_encode($response);
exit();
?>

