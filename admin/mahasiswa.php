<?php
// DIUBAH: Pastikan baris ini ada di paling atas
session_start();

require_once '../service/database.php';

$pageTitle = 'Manajemen Data Mahasiswa';
$activePage = 'mahasiswa';

// Query untuk mengambil data mahasiswa yang dibutuhkan
$query = "SELECT id, profile_photo, nama, nim, prodi FROM user ORDER BY id ASC";

$result = mysqli_query($db, $query);
if (!$result) {
    die("ERROR: Query gagal dijalankan: " . mysqli_error($db));
}
$data_mahasiswa = mysqli_fetch_all($result, MYSQLI_ASSOC);

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
            <?php unset($_SESSION['pesan']); // Hapus pesan dari session setelah ditampilkan ?>
        <?php endif; ?>
        
        <div class="flex justify-start items-center mb-6">
            <a href="tambah-mahasiswa.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Mahasiswa
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left table-auto">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 w-12 text-center">No</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 w-24">Foto</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Nama Lengkap</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">NIM</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300">Program Studi</th>
                            <th class="p-4 font-semibold text-gray-600 dark:text-gray-300 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($data_mahasiswa)): ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data mahasiswa.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($data_mahasiswa as $mhs): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="p-4 text-gray-700 dark:text-gray-300 text-center align-middle"><?php echo $no++; ?></td>
                                <td class="p-2 align-middle">
                                    <?php if(!empty($mhs['profile_photo']) && file_exists("../uploads/profile_photos/" . $mhs['profile_photo'])): ?>
                                        <img src="../uploads/profile_photos/<?php echo htmlspecialchars($mhs['profile_photo']); ?>" alt="Foto <?php echo htmlspecialchars($mhs['nama']); ?>" class="w-12 h-12 object-cover rounded-full shadow-md">
                                    <?php else: ?>
                                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 flex items-center justify-center rounded-full shadow-md">
                                            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-gray-900 dark:text-white font-medium align-middle"><?php echo htmlspecialchars($mhs['nama']); ?></td>
                                <td class="p-4 text-gray-700 dark:text-gray-300 align-middle"><?php echo htmlspecialchars($mhs['nim']); ?></td>
                                <td class="p-4 text-gray-700 dark:text-gray-300 align-middle"><?php echo htmlspecialchars($mhs['prodi']); ?></td>
                                <td class="p-4 space-x-2 text-center whitespace-nowrap align-middle">
                                    <a href="edit-mahasiswa.php?id=<?php echo $mhs['id']; ?>" class="px-3 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition-colors text-sm">Edit</a>
                                    <a href="hapus-mahasiswa.php?id=<?php echo $mhs['id']; ?>" class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors text-sm" onclick="return confirm('Anda yakin ingin menghapusnya?');">Hapus</a>
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