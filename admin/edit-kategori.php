<?php
session_start();
require_once '../service/database.php';

$nama_kategori_lama = "";
$id = 0;

// Logika untuk UPDATE (ketika form disubmit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nama_kategori = $_POST['nama_kategori'];

    if (!empty($nama_kategori)) {
        $sql = "UPDATE kategori_buku SET nama_kategori = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($db, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $nama_kategori, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['pesan'] = "Data kategori berhasil diperbarui.";
                header("location: kategori.php");
                exit();
            } else {
                echo "Error: Gagal memperbarui data.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Nama kategori tidak boleh kosong.";
    }
} 
// Logika untuk SELECT (mengisi form saat halaman di-load)
else {
    if(isset($_GET['id']) && !empty(trim($_GET['id']))){
        $id = trim($_GET['id']);
        
        $sql = "SELECT nama_kategori FROM kategori_buku WHERE id = ?";
        if($stmt = mysqli_prepare($db, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $id);
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $nama_kategori_lama = $row['nama_kategori'];
                } else{
                    // Redirect jika ID tidak ditemukan
                    header("location: kategori.php");
                    exit();
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Redirect jika tidak ada ID
        header("location: kategori.php");
        exit();
    }
}
?>

<?php 
$pageTitle = "Edit Kategori";
include 'header.php'; 
?>
<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Kategori</h1>
    </header>
    <main class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <form action="edit-kategori.php" method="post">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="mb-4">
                    <label for="nama_kategori" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                        Nama Kategori
                    </label>
                    <input type="text" name="nama_kategori" id="nama_kategori" value="<?php echo htmlspecialchars($nama_kategori_lama); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Perubahan
                    </button>
                    <a href="kategori.php" class="inline-block align-baseline font-bold text-sm text-blue-600 hover:text-blue-800">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>