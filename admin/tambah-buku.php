<?php
session_start();
require_once '../service/database.php';

// Ambil semua data kategori untuk ditampilkan di dropdown
$sql_kategori = "SELECT * FROM kategori_buku ORDER BY nama_kategori ASC";
$result_kategori = mysqli_query($db, $sql_kategori);

$error_msg = "";

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = trim($_POST['judul']);
    $penulis = trim($_POST['penulis']);
    $penerbit = trim($_POST['penerbit']);
    $tahun_terbit = trim($_POST['tahun_terbit']);
    $stok = trim($_POST['stok']);
    $deskripsi = trim($_POST['deskripsi']);
    $kategori_id = trim($_POST['kategori_id']);
    
    $nama_cover = "";

    // Logika untuk upload file cover
    if (isset($_FILES["cover"]) && $_FILES["cover"]["error"] == 0) {
        $target_dir = "../upload/covers/";
        // Buat nama file unik untuk menghindari menimpa file yang ada
        $nama_cover = uniqid() . '-' . basename($_FILES["cover"]["name"]);
        $target_file = $target_dir . $nama_cover;
        
        // Pindahkan file yang diupload ke direktori tujuan
        if (!move_uploaded_file($_FILES["cover"]["tmp_name"], $target_file)) {
            $error_msg = "Maaf, terjadi error saat mengupload file cover.";
            $nama_cover = ""; // Kosongkan nama cover jika gagal
        }
    }

    if (empty($error_msg)) {
        // Query INSERT dengan prepared statement
        $sql = "INSERT INTO buku (judul, penulis, penerbit, tahun_terbit, stok, deskripsi, cover, kategori_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($db, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssiissi", $judul, $penulis, $penerbit, $tahun_terbit, $stok, $deskripsi, $nama_cover, $kategori_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['pesan'] = "Buku baru berhasil ditambahkan.";
                header("location: buku.php");
                exit();
            } else {
                $error_msg = "Gagal menyimpan data buku.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<?php 
$pageTitle = "Tambah Buku Baru";
include 'header.php';
?>
<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Tambah Buku Baru</h1>
    </header>
    <main class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <?php if(!empty($error_msg)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <form action="tambah-buku.php" method="post" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <label for="judul" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Judul Buku</label>
                            <input type="text" name="judul" id="judul" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="penulis" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Penulis</label>
                            <input type="text" name="penulis" id="penulis" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="penerbit" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Penerbit</label>
                            <input type="text" name="penerbit" id="penerbit" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="kategori_id" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Kategori</label>
                            <select name="kategori_id" id="kategori_id" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                                <option value="">Pilih Kategori</option>
                                <?php while($kategori = mysqli_fetch_assoc($result_kategori)): ?>
                                    <option value="<?php echo $kategori['id']; ?>"><?php echo htmlspecialchars($kategori['nama_kategori']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="tahun_terbit" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Tahun Terbit</label>
                                <input type="number" name="tahun_terbit" id="tahun_terbit" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                            </div>
                            <div>
                                <label for="stok" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Stok</label>
                                <input type="number" name="stok" id="stok" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="deskripsi" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3"></textarea>
                        </div>
                        <div class="mb-4">
                             <label for="cover" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Upload Cover</label>
                             <input type="file" name="cover" id="cover" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-4 mt-6">
                    <a href="buku.php" class="inline-block font-bold text-sm text-gray-600">Batal</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Buku</button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>