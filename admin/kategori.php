<?php
// Memulai session di baris paling atas untuk menangani pesan notifikasi
session_start();

// File: kategori.php
// Halaman utama untuk menampilkan, menambah, mengedit, dan menghapus data kategori

// 1. Menyertakan file koneksi database
// Path disesuaikan karena file ini ada di dalam folder 'admin'
require_once '../service/database.php';

$pageTitle = 'Manajemen Kategori Buku';
$activePage = 'kategori';

// 2. Query untuk mengambil semua data kategori dari database
// Diurutkan berdasarkan ID agar berurutan
$query = "SELECT id, nama_kategori FROM kategori_buku ORDER BY id ASC";
$result = mysqli_query($db, $query);

// Cek jika query gagal dijalankan
if (!$result) {
    die("ERROR: Query gagal dijalankan. " . mysqli_error($db));
}

// 3. Ambil semua data ke dalam array asosiatif
$data_kategori = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Memanggil komponen header HTML
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
        
        <?php if(isset($_SESSION['pesan'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['pesan']); ?></span>
            </div>
            <?php unset($_SESSION['pesan']); // Hapus pesan dari session setelah ditampilkan ?>
        <?php endif; ?>

        <div class="flex justify-start items-center mb-6">
            <a href="tambah-kategori.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Kategori
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">ID</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Nama Kategori</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($data_kategori)): ?>
                            <tr>
                                <td colspan="3" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                    Tidak ada data untuk ditampilkan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data_kategori as $kategori): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="p-4 text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($kategori['id']); ?></td>
                                <td class="p-4 text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($kategori['nama_kategori']); ?></td>
                                <td class="p-4 space-x-2 text-center">
                                    <a href="edit-kategori.php?id=<?php echo $kategori['id']; ?>" class="px-3 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition-colors">Edit</a>
                                    <a href="hapus-kategori.php?id=<?php echo $kategori['id']; ?>" class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors" onclick="return confirm('Anda yakin ingin menghapus data ini?');">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>