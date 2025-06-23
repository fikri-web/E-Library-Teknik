<?php
session_start();
if (!isset($_SESSION['is_login'])) {
    header("Location: ../login.php");
    exit();
}

// // Jika bukan admin, redirect ke dashboard user
// if (empty($_SESSION['is_admin'])) {
//     header("Location: ../Dashboard.php");
//     exit();
// }

require_once '../service/database.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Ambil data statistik
$total_buku = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(id) AS total FROM buku"))['total'] ?? 0;
$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(id) AS total FROM user"))['total'] ?? 0;
$total_kategori = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(id) AS total FROM kategori_buku"))['total'] ?? 0;

include 'header.php';
include 'sidebar.php';
?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <div class="flex items-center">
            <button id="menu-button" class="text-gray-500 dark:text-gray-300 focus:outline-none md:hidden">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white ml-4"><?php echo $pageTitle; ?></h1>
        </div>
        <?php include 'top_right_menu.php'; ?>
    </header>

    <main class="flex-1 p-6 overflow-y-auto">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Selamat Datang, Admin!</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-8">Berikut adalah ringkasan data perpustakaan saat ini.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Buku</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white"><?php echo number_format($total_buku); ?></p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                    <i class="fas fa-book text-blue-500 dark:text-blue-300 text-2xl"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Anggota</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white"><?php echo number_format($total_mahasiswa); ?></p>
                </div>
                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                    <i class="fas fa-users text-green-500 dark:text-green-300 text-2xl"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kategori</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white"><?php echo number_format($total_kategori); ?></p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                    <i class="fas fa-layer-group text-yellow-500 dark:text-yellow-300 text-2xl"></i>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>
