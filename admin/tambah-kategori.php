<?php
// Memulai session untuk menangani pesan notifikasi
session_start();
// Menyertakan file koneksi database
require_once '../service/database.php';

$error_msg = ""; // Variabel untuk menyimpan pesan error

// Cek apakah form telah disubmit (ketika tombol 'Simpan' ditekan)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil dan bersihkan data dari form
    $nama_kategori = trim($_POST['nama_kategori']);

    // Validasi sederhana: pastikan nama kategori tidak kosong
    if (!empty($nama_kategori)) {
        
        // Gunakan prepared statement untuk mencegah SQL Injection (lebih aman)
        $sql = "INSERT INTO kategori_buku (nama_kategori) VALUES (?)";
        
        if ($stmt = mysqli_prepare($db, $sql)) {
            // Bind variabel ke prepared statement sebagai parameter
            // "s" berarti tipe datanya adalah string
            mysqli_stmt_bind_param($stmt, "s", $nama_kategori);
            
            // Coba eksekusi statement yang sudah disiapkan
            if (mysqli_stmt_execute($stmt)) {
                // Jika berhasil, buat pesan sukses dan arahkan kembali ke halaman utama
                $_SESSION['pesan'] = "Kategori baru berhasil ditambahkan.";
                header("location: kategori.php");
                exit(); // Penting untuk menghentikan eksekusi script setelah redirect
            } else {
                $error_msg = "Error: Tidak bisa mengeksekusi query.";
            }
            // Tutup statement
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Error: Tidak bisa menyiapkan query.";
        }
    } else {
        $error_msg = "Nama kategori tidak boleh kosong.";
    }
}
?>

<?php 
// Set judul halaman dan panggil komponen header HTML
$pageTitle = "Tambah Kategori";
include 'header.php'; 
?>
<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <div class="flex items-center">
             <a href="kategori.php" class="text-gray-500 dark:text-gray-300 hover:text-gray-700 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Tambah Kategori Baru</h1>
        </div>
        <?php include 'top_right_menu.php'; ?>
    </header>

    <main class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-lg mx-auto">
            
            <?php if(!empty($error_msg)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <form action="tambah-kategori.php" method="post">
                <div class="mb-6">
                    <label for="nama_kategori" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        Nama Kategori
                    </label>
                    <input type="text" name="nama_kategori" id="nama_kategori" placeholder="Contoh: Novel Fiksi Ilmiah" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="flex items-center justify-end gap-4">
                    <a href="kategori.php" class="inline-block align-baseline font-bold text-sm text-gray-600 dark:text-gray-400 hover:text-blue-800">
                        Batal
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>