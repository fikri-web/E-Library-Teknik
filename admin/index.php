<?php
session_start();
// 1. Sertakan file koneksi ke database
// Path ini disesuaikan agar konsisten dengan file lain
require_once '../service/database.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// --- LOGIKA PENGAMBILAN DATA STATISTIK ---

// 2. Query untuk menghitung total buku
$sql_buku = "SELECT COUNT(id) AS total_buku FROM buku";
$result_buku = mysqli_query($db, $sql_buku);
$total_buku = mysqli_fetch_assoc($result_buku)['total_buku'] ?? 0;

// 3. Query untuk menghitung total anggota (mahasiswa)
$sql_mahasiswa = "SELECT COUNT(id) AS total_mahasiswa FROM user";
$result_mahasiswa = mysqli_query($db, $sql_mahasiswa);
$total_mahasiswa = mysqli_fetch_assoc($result_mahasiswa)['total_mahasiswa'] ?? 0;

// 4. Query untuk menghitung total kategori
$sql_kategori = "SELECT COUNT(id) AS total_kategori FROM kategori_buku";
$result_kategori = mysqli_query($db, $sql_kategori);
$total_kategori = mysqli_fetch_assoc($result_kategori)['total_kategori'] ?? 0;

// --- AKHIR LOGIKA ---


// Memanggil header (path disesuaikan)
include 'header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <div class="flex items-center">
            <button id="menu-button" class="text-gray-500 dark:text-gray-300 focus:outline-none md:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
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
                    <svg class="w-8 h-8 text-blue-500 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 6.253v11.494m-9-5.747h18"></path><path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"></path></svg>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Anggota</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white"><?php echo number_format($total_mahasiswa); ?></p>
                </div>
                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                    <svg class="w-8 h-8 text-green-500 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.134-1.276-.38-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.134-1.276.38-1.857m0 0a5.002 5.002 0 019.24 0M12 15v5M12 15a5 5 0 01-5-5V7a5 5 0 0110 0v3a5 5 0 01-5 5z"></path></svg>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Kategori</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white"><?php echo number_format($total_kategori); ?></p>
                </div>
                 <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                    <svg class="w-8 h-8 text-yellow-500 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a2 2 0 012 2v5a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm0 14h.01M7 17h5a2 2 0 012 2v5a2 2 0 01-2 2H7a2 2 0 01-2-2v-5a2 2 0 012-2z"></path></svg>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>