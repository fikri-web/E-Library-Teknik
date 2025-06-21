<?php 
session_start();
require 'service/database.php';

// Cek hanya JIKA tombol submit ditekan
if (isset($_POST['submit'])) {
    // Ambil data dari form setelah disubmit
    $namaKategori = mysqli_real_escape_string($db, $_POST['namaKategori']);
    $deskripsi = mysqli_real_escape_string($db, $_POST['deskripsi']);

    // Jalankan query HANYA setelah data diambil
    $query = "INSERT INTO kategoribuku (namaKategori, deskripsi) VALUES ('$namaKategori', '$deskripsi')";
    if (mysqli_query($db, $query)) {
        $_SESSION['success'] = "Kategori buku berhasil ditambahkan.";
    } else {
        $_SESSION['error'] = "Gagal menambahkan kategori buku: " . mysqli_error($db);
    }
    
    // Opsional: Redirect untuk mencegah re-submit form saat refresh
    // header('Location: ' . $_SERVER['PHP_SELF']);
    // exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Tambah Kategori</title>
    </head>
<body>
    <div id="popup-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button onclick="document.getElementById('popup-modal').classList.add('hidden')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                &times;
            </button>
            <h2 class="text-2xl font-semibold mb-4 text-center">Tambah Kategori Buku</h2>
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label for="namaKategori" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                    <input type="text" name="namaKategori" id="namaKategori" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" name="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tambah</button>
                </div>
            </form>
        </div>
    </div>

    <div class="flex justify-center mt-8">
        <button onclick="document.getElementById('popup-modal').classList.remove('hidden')" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            Tambah Kategori Buku
        </button>
    </div>
</body>
</html>