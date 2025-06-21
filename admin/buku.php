<?php
// Memulai session untuk menangani pesan notifikasi
session_start();

// File: buku.php
// Halaman utama untuk menampilkan daftar buku dengan semua fungsionalitas

require_once '../service/database.php';

$pageTitle = 'Manajemen Data Buku';
$activePage = 'buku';

// Query SQL dengan JOIN untuk mengambil semua data yang dibutuhkan
$query = "
    SELECT
        b.id,
        b.cover,
        b.judul,
        b.penulis,
        b.penerbit,
        b.tahun_terbit,
        b.stok,
        b.deskripsi,
        k.nama_kategori
    FROM
        buku AS b
    LEFT JOIN
        kategori_buku AS k ON b.kategori_id = k.id
    ORDER BY
        b.id ASC;
";

$result = mysqli_query($db, $query);
if (!$result) {
    die("ERROR: Query gagal dijalankan: " . mysqli_error($db));
}
$data_buku = mysqli_fetch_all($result, MYSQLI_ASSOC);

include 'header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <div class="flex items-center">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white ml-4"><?php echo $pageTitle; ?></h1>
        </div>
        <?php include 'top_right_menu.php'; ?>
    </header>

    <main class="flex-1 p-6 overflow-y-auto">

        <?php if(isset($_SESSION['pesan'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['pesan']); ?></span>
            </div>
            <?php unset($_SESSION['pesan']); ?>
        <?php endif; ?>

        <div class="flex justify-start items-center mb-6">
            <a href="tambah-buku.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Buku
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left table-auto">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 w-12 text-center">No</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 w-24">Cover</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Judul Buku</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Penulis</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Kategori</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 w-1/4">Deskripsi</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 text-center">Stok</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($data_buku)): ?>
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data buku.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($data_buku as $buku): ?>
                                
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="p-4 text-gray-700 dark:text-gray-300 text-center align-top"><?php echo $no++; ?></td>
                                <td class="p-2 align-top">
                                    <?php if(!empty($buku['cover']) && file_exists("../upload/covers/" . $buku['cover'])): ?>
                                        <img src="../upload/covers/<?php echo htmlspecialchars($buku['cover']); ?>" alt="Cover <?php echo htmlspecialchars($buku['judul']); ?>" class="w-16 h-24 object-cover rounded shadow-md">
                                    <?php else: ?>
                                        <div class="w-16 h-24 bg-gray-200 dark:bg-gray-700 flex items-center justify-center rounded shadow-md">
                                            <span class="text-xs text-gray-500">No Cover</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-gray-900 dark:text-white font-medium align-top"><?php echo htmlspecialchars($buku['judul']); ?></td>
                                <td class="p-4 text-gray-700 dark:text-gray-300 align-top"><?php echo htmlspecialchars($buku['penulis']); ?></td>
                                <td class="p-4 text-gray-700 dark:text-gray-300 align-top"><?php echo htmlspecialchars($buku['nama_kategori']); ?></td>
                                <td class="p-4 text-sm text-gray-600 dark:text-gray-400 align-top">
                                    <?php
                                        // Potong deskripsi jika lebih dari 100 karakter
                                        $deskripsi_penuh = htmlspecialchars($buku['deskripsi'] ?? '');
                                        if (strlen($deskripsi_penuh) > 100) {
                                            echo substr($deskripsi_penuh, 0, 100) . '...';
                                        } else {
                                            echo $deskripsi_penuh;
                                        }
                                    ?>
                                </td>
                                <td class="p-4 text-gray-700 dark:text-gray-300 text-center align-top"><?php echo htmlspecialchars($buku['stok']); ?></td>
                                <td class="p-4 space-x-2 text-center whitespace-nowrap align-top">
                                    <a href="edit-buku.php?id=<?php echo $buku['id']; ?>" class="px-3 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition-colors text-sm">Edit</a>
                                    <a href="hapus-buku.php?id=<?php echo $buku['id']; ?>" class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors text-sm" onclick="return confirm('Anda yakin ingin menghapus buku ini?');">Hapus</a>
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