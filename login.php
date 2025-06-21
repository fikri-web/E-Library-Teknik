<?php
session_start();

// Debug mode aktif – matikan di production
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "service/database.php";

$show_alert = false;
$alert_message = 'NIM atau Password salah!';

if (isset($_POST['btnLogin'])) {
    $nim_input = trim($_POST['nim'] ?? '');
    $password_dari_form = $_POST['password'] ?? '';

    if (empty($nim_input) || empty($password_dari_form)) {
        $show_alert = true;
        $alert_message = 'NIM dan Password harus diisi!';
    } else {
        try {
            // === Cek ADMIN terlebih dahulu ===
            $sql_admin = "SELECT id, username, password FROM admin WHERE username = ?";
            $stmt_admin = $db->prepare($sql_admin);
            if (!$stmt_admin) throw new Exception("Prepare admin failed: " . $db->error);

            $stmt_admin->bind_param("s", $nim_input);
            $stmt_admin->execute();
            $result_admin = $stmt_admin->get_result();

            if ($result_admin->num_rows === 1) {
                $admin = $result_admin->fetch_assoc();
                if (password_verify($password_dari_form, $admin['password']) ||
                    (strlen($admin['password']) === 32 && md5($password_dari_form) === $admin['password'])) {

                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['nama'] = $admin['username']; // tidak ada kolom nama di admin
                    $_SESSION['is_login'] = true;
                    $_SESSION['is_admin'] = true;

                    header("Location: admin/index.php");
                    exit;
                } else {
                    $show_alert = true;
                }
            }
            $stmt_admin->close();

            // === Jika bukan admin, cek sebagai USER ===
            $sql_user = "SELECT id, nama, prodi, password FROM user WHERE nim = ?";
            $stmt_user = $db->prepare($sql_user);
            if (!$stmt_user) throw new Exception("Prepare user failed: " . $db->error);

            $stmt_user->bind_param("s", $nim_input);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();

            if ($result_user->num_rows === 1) {
                $user = $result_user->fetch_assoc();

                $login_berhasil = false;

                // Cek password hash (bcrypt atau legacy MD5)
                if (password_verify($password_dari_form, $user['password'])) {
                    $login_berhasil = true;
                } elseif (strlen($user['password']) === 32 && md5($password_dari_form) === $user['password']) {
                    $login_berhasil = true;
                    // Update password ke hash baru
                    $hash_baru_aman = password_hash($password_dari_form, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE user SET password = ? WHERE id = ?";
                    $update_stmt = $db->prepare($update_sql);
                    $update_stmt->bind_param("si", $hash_baru_aman, $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                if ($login_berhasil) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['prodi'] = $user['prodi'];
                    $_SESSION['is_login'] = true;
                    $_SESSION['is_admin'] = false;

                    header("Location: Dashboard.php");
                    exit;
                } else {
                    $show_alert = true;
                }
            } else {
                $show_alert = true;
            }
            $stmt_user->close();
        } catch (Exception $e) {
            $show_alert = true;
            $alert_message = "Terjadi kesalahan sistem: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | E-Library Teknik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-sm">
        <h2 class="text-2xl font-bold text-center mb-2">Login E-Library</h2>
        <p class="text-gray-600 text-center mb-6">Masukkan NIM dan Password Anda</p>

        <form action="login.php" method="POST">
            <div class="mb-4">
                <label for="nim" class="block text-sm font-medium text-gray-700">NIM</label>
                <input type="text" id="nim" name="nim" required placeholder="Masukkan NIM"
                       class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan password"
                       class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" name="btnLogin"
                    class="w-full py-2 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                Login
            </button>
        </form>
        
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                Belum punya akun?
                <a href="register.php" class="text-blue-600 hover:underline">Daftar di sini!</a>
            </p>
        </div>
    </div>

    <?php if ($show_alert): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Gagal!',
            text: '<?= htmlspecialchars($alert_message, ENT_QUOTES, 'UTF-8') ?>',
            confirmButtonText: 'Coba Lagi',
            confirmButtonColor: '#2563eb'
        });
    </script>
    <?php endif; ?>
</body>
</html>