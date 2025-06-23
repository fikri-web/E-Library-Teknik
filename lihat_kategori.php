<?php
require 'service/database.php'; // Hubungkan ke database

// Langkah 1: Tangkap ID Kategori dari URL
// Pastikan ID ada di URL dan merupakan angka
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Jika tidak ada ID, atau bukan angka, hentikan eksekusi
    die("Error: Kategori tidak valid atau tidak ditemukan.");
}
$id_kategori_dipilih = (int)$_GET['id']; // Ubah menjadi integer untuk keamanan

// Langkah 2 (Opsional tapi bagus): Ambil nama kategori untuk ditampilkan di judul
$sql_nama_kategori = "SELECT nama_kategori FROM kategori_buku WHERE id = ?";
$stmt_nama = $db->prepare($sql_nama_kategori);
$stmt_nama->bind_param("i", $id_kategori_dipilih);
$stmt_nama->execute();
$result_nama = $stmt_nama->get_result();
$kategori = $result_nama->fetch_assoc();
$nama_kategori = $kategori ? htmlspecialchars($kategori['nama_kategori']) : "Kategori Tidak Dikenal";
$stmt_nama->close();

// Langkah 3: Ambil SEMUA BUKU yang termasuk dalam kategori tersebut
$sql_buku = "SELECT id, judul, penulis, penerbit, tahun_terbit, stok FROM buku WHERE kategori_id = ?";
$stmt_buku = $db->prepare($sql_buku);
$stmt_buku->bind_param("i", $id_kategori_dipilih);
$stmt_buku->execute();
$result_buku = $stmt_buku->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku dalam Kategori: <?= $nama_kategori ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="container mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Daftar Buku: <?= $nama_kategori ?></h1>
            <p class="text-gray-600">Berikut adalah semua buku yang tersedia dalam kategori ini.</p>
            <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800 mt-2 inline-block">&larr; Kembali ke Dashboard</a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Judul</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Penulis</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Penerbit</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tahun Terbit</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_buku && mysqli_num_rows($result_buku) > 0): ?>
                        <?php while($buku = mysqli_fetch_assoc($result_buku)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($buku['judul']) ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($buku['penulis']) ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($buku['penerbit']) ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($buku['tahun_terbit']) ?></p>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <p class="text-gray-900 whitespace-no-wrap"><?= htmlspecialchars($buku['stok']) ?></p>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-500">
                                Belum ada buku dalam kategori ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>