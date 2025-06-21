<?php
session_start();
require_once '../service/database.php';

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $jurusan = trim($_POST['jurusan']);
    $email = trim($_POST['email']);
    $tanggallahir = trim($_POST['tanggallahir']);
    $gender = trim($_POST['gender']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Amankan password
    $status_keanggotaan = trim($_POST['status_keanggotaan']);

    $profile_photo = "";

    // Upload foto
    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $target_dir = "../uploads/profile_photos/";
        $profile_photo = uniqid() . '-' . basename($_FILES["foto"]["name"]);
        $target_file = $target_dir . $profile_photo;

        if (!move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            $error_msg = "Maaf, terjadi error saat mengupload foto.";
            $profile_photo = "";
        }
    }

    if (empty($error_msg)) {
        $sql = "INSERT INTO user (nim, nama, prodi, email, tanggallahir, gender, password, profile_photo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($db, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssss", $nim, $nama, $jurusan, $email, $tanggallahir, $gender, $password, $profile_photo);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['pesan'] = "Data mahasiswa baru berhasil ditambahkan.";
                header("location: mahasiswa.php");
                exit();
            } else {
                $error_msg = "Gagal menyimpan data. NIM atau Email mungkin sudah terdaftar.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Gagal mempersiapkan statement SQL.";
        }
    }
}

?>

<?php 
$pageTitle = "Tambah Mahasiswa Baru";
include 'header.php';
?>
<?php include 'sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Tambah Mahasiswa Baru</h1>
    </header>
    <main class="flex-1 p-6 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <?php if(!empty($error_msg)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <form action="tambah-mahasiswa.php" method="post" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <label for="nim" class="block font-bold mb-2">NIM</label>
                            <input type="text" name="nim" id="nim" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="nama" class="block font-bold mb-2">Nama</label>
                            <input type="text" name="nama" id="nama" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block font-bold mb-2">Email</label>
                            <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                    </div>
                    <div>
                        <div class="mb-4">
                            <label for="jurusan" class="block font-bold mb-2">Program Studi / Jurusan</label>
                            <input type="text" name="jurusan" id="jurusan" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                        </div>
                         
                        <div class="mb-4">
                            <label for="status_keanggotaan" class="block font-bold mb-2">Status Keanggotaan</label>
                            <select name="status_keanggotaan" id="status_keanggotaan" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                                <option value="Lulus">Lulus</option>
                            </select>
                        </div>
                         <div class="mb-4">
                             <label for="foto" class="block font-bold mb-2">Upload Foto</label>
                             <input type="file" name="foto" id="foto" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-4 mt-6">
                    <a href="mahasiswa.php" class="inline-block font-bold text-sm text-gray-600">Batal</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Mahasiswa</button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>