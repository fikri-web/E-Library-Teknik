<?php
session_start();
require_once '../service/database.php';

$mhs = null;
$error_msg = "";

// Logika untuk UPDATE (ketika form disubmit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $prodi = trim($_POST['prodi']);
    $email = trim($_POST['email']);
    $foto_lama = $_POST['foto_lama'];
    
    $nama_foto = $foto_lama;

    // Cek jika ada file foto baru yang diupload
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        // Hapus file foto lama jika ada
        if(!empty($foto_lama) && file_exists('../uploads/profile_photos/' . $foto_lama)){
            unlink('../uploads/profile_photos/' . $foto_lama);
        }
        
        $target_dir = "../uploads/profile_photos/";
        $nama_foto = uniqid() . '-' . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $nama_foto;
        
        if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            $error_msg = "Gagal upload foto baru.";
            $nama_foto = $foto_lama; // Kembalikan ke nama foto lama jika gagal
        }
    }

    if(empty($error_msg)){
        $sql = "UPDATE user SET nim=?, nama=?, prodi=?, email=?, profile_photo=? WHERE id=?";
        
        if ($stmt = mysqli_prepare($db, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssi", $nim, $nama, $prodi, $email,  $nama_foto, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['pesan'] = "Data mahasiswa berhasil diperbarui.";
                header("location: mahasiswa.php");
                exit();
            } else {
                $error_msg = "Gagal memperbarui data. NIM atau Email mungkin sudah terdaftar.";
            }
        }
    }
} 
// Logika untuk SELECT (mengisi form saat halaman di-load)
else if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $id = trim($_GET['id']);
    $sql = "SELECT * FROM user WHERE id = ?";
    if($stmt = mysqli_prepare($db, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $mhs = mysqli_fetch_assoc($result);
            } else {
                header("location: mahasiswa.php");
                exit();
            }
        }
    }
} else {
    header("location: mahasiswa.php");
    exit();
}
?>

<?php 
$pageTitle = "Edit Data Mahasiswa";
include 'header.php';
?>
<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Data Mahasiswa</h1>
    </header>
    <main class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <form action="edit-mahasiswa.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $mhs['id']; ?>">
                <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($mhs['profile_photo']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <label for="nim" class="block font-bold mb-2">NIM</label>
                            <input type="text" name="nim" value="<?php echo htmlspecialchars($mhs['nim']); ?>" class="shadow w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="nama" class="block font-bold mb-2">Nama Lengkap</label>
                            <input type="text" name="nama" value="<?php echo htmlspecialchars($mhs['nama']); ?>" class="shadow w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block font-bold mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($mhs['email']); ?>" class="shadow w-full py-2 px-3" required>
                        </div>
                    </div>
                    <div>
                        <div class="mb-4">
                            <label for="prodi" class="block font-bold mb-2">Program Studi / Jurusan</label>
                            <input type="text" name="prodi" value="<?php echo htmlspecialchars($mhs['prodi']); ?>" class="shadow w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                             <label class="block font-bold mb-2">Foto Saat Ini</label>
                             <?php if(!empty($mhs['foto']) && file_exists('../uploads/profile_photos/' . $mhs['profile_photo'])): ?>
                                <img src="../uploads/profile_photos<?php echo htmlspecialchars($mhs['profile_photo']); ?>" class="w-24 h-24 object-cover rounded-full shadow-md mb-2">
                             <?php endif; ?>
                             <label for="foto" class="block text-sm font-medium text-gray-500">Ganti Foto (opsional)</label>
                             <input type="file" name="foto" id="foto" class="block w-full text-sm mt-1">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-4 mt-6">
                    <a href="mahasiswa.php" class="inline-block font-bold text-sm text-gray-600">Batal</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>