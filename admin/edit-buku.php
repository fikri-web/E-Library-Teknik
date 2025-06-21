<?php
session_start();
require_once '../service/database.php';

// Ambil semua data kategori untuk dropdown
$sql_kategori = "SELECT * FROM kategori_buku ORDER BY nama_kategori ASC";
$result_kategori = mysqli_query($db, $sql_kategori);

$buku = null;
$error_msg = "";

// Logika untuk UPDATE (ketika form disubmit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $judul = trim($_POST['judul']);
    $penulis = trim($_POST['penulis']);
    $penerbit = trim($_POST['penerbit']);
    $tahun_terbit = trim($_POST['tahun_terbit']);
    $stok = trim($_POST['stok']);
    $deskripsi = trim($_POST['deskripsi']);
    $kategori_id = trim($_POST['kategori_id']);
    $cover_lama = $_POST['cover_lama'];
    
    $nama_cover = $cover_lama;

    // Cek jika ada file cover baru yang diupload
    if (isset($_FILES["cover"]) && $_FILES["cover"]["error"] == 0) {
        // Hapus file cover lama jika ada
        if(!empty($cover_lama) && file_exists('../upload/covers/' . $cover_lama)){
            unlink('../upload/covers/' . $cover_lama);
        }
        
        $target_dir = "../upload/covers/";
        $nama_cover = uniqid() . '-' . basename($_FILES["cover"]["name"]);
        $target_file = $target_dir . $nama_cover;
        
        if (!move_uploaded_file($_FILES["cover"]["tmp_name"], $target_file)) {
            $error_msg = "Maaf, terjadi error saat mengupload file cover baru.";
            $nama_cover = $cover_lama; // Kembalikan ke nama cover lama jika gagal upload
        }
    }

    if(empty($error_msg)){
        $sql = "UPDATE buku SET judul=?, penulis=?, penerbit=?, tahun_terbit=?, stok=?, deskripsi=?, cover=?, kategori_id=? WHERE id=?";
        
        if ($stmt = mysqli_prepare($db, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssiissii", $judul, $penulis, $penerbit, $tahun_terbit, $stok, $deskripsi, $nama_cover, $kategori_id, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['pesan'] = "Data buku berhasil diperbarui.";
                header("location: buku.php");
                exit();
            } else {
                $error_msg = "Gagal memperbarui data buku.";
            }
        }
    }
} 
// Logika untuk SELECT (mengisi form saat halaman di-load)
else if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $id = trim($_GET['id']);
    $sql = "SELECT * FROM buku WHERE id = ?";
    if($stmt = mysqli_prepare($db, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $buku = mysqli_fetch_assoc($result);
            } else {
                header("location: buku.php");
                exit();
            }
        }
    }
} else {
    header("location: buku.php");
    exit();
}
?>

<?php 
$pageTitle = "Edit Buku";
include 'header.php';
?>
<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Buku</h1>
    </header>
    <main class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <form action="edit-buku.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $buku['id']; ?>">
                <input type="hidden" name="cover_lama" value="<?php echo htmlspecialchars($buku['cover']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <label for="judul" class="block font-bold mb-2">Judul Buku</label>
                            <input type="text" name="judul" id="judul" value="<?php echo htmlspecialchars($buku['judul']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="penulis" class="block font-bold mb-2">Penulis</label>
                            <input type="text" name="penulis" id="penulis" value="<?php echo htmlspecialchars($buku['penulis']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="penerbit" class="block font-bold mb-2">Penerbit</label>
                            <input type="text" name="penerbit" id="penerbit" value="<?php echo htmlspecialchars($buku['penerbit']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                         <div class="mb-4">
                            <label for="kategori_id" class="block font-bold mb-2">Kategori</label>
                            <select name="kategori_id" id="kategori_id" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                                <option value="">Pilih Kategori</option>
                                <?php mysqli_data_seek($result_kategori, 0); // Reset pointer result set ?>
                                <?php while($kategori = mysqli_fetch_assoc($result_kategori)): ?>
                                    <option value="<?php echo $kategori['id']; ?>" <?php echo ($buku['kategori_id'] == $kategori['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                         <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="tahun_terbit" class="block font-bold mb-2">Tahun Terbit</label>
                                <input type="number" name="tahun_terbit" id="tahun_terbit" value="<?php echo htmlspecialchars($buku['tahun_terbit']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                            </div>
                            <div>
                                <label for="stok" class="block font-bold mb-2">Stok</label>
                                <input type="number" name="stok" id="stok" value="<?php echo htmlspecialchars($buku['stok']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="deskripsi" class="block font-bold mb-2">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3"><?php echo htmlspecialchars($buku['deskripsi']); ?></textarea>
                        </div>
                        <div class="mb-4">
                             <label class="block font-bold mb-2">Cover Saat Ini</label>
                             <?php if(!empty($buku['cover']) && file_exists('../uploads/covers/' . $buku['cover'])): ?>
                                <img src="../uploads/covers/<?php echo htmlspecialchars($buku['cover']); ?>" class="w-24 h-36 object-cover rounded shadow-md mb-2">
                             <?php else: ?>
                                <p class="text-sm text-gray-500">Tidak ada cover.</p>
                             <?php endif; ?>
                             <label for="cover" class="block text-sm font-medium text-gray-500">Ganti Cover (opsional)</label>
                             <input type="file" name="cover" id="cover" class="block w-full text-sm mt-1">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-4 mt-6">
                    <a href="buku.php" class="inline-block font-bold text-sm text-gray-600">Batal</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>