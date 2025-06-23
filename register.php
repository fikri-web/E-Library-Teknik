<?php
include "service/database.php";

if (isset($_POST['signup'])) {
    $nama = $_POST['nama'];
    $nim = $_POST['nim'];
    $prodi = $_POST['prodi'];
    $email = $_POST['email'];
    $tanggallahir = $_POST['tanggallahir'];
    $gender = $_POST['gender'];
    $password = md5($_POST['password']); // Lebih aman gunakan password_hash()

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $targetDir = "uploads/profile_photos/"; // Pastikan ini benar
        $fileTmpPath = $_FILES['profile_photo']['tmp_name'];
        $fileName = $_FILES['profile_photo']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Validasi ekstensi file
        $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Cek apakah folder ada
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true); // Buat folder jika belum ada
            }

            // Pindahkan file
            if (move_uploaded_file($fileTmpPath, $targetDir . $newFileName)) {
                // Simpan ke database
                $sql = "INSERT INTO user (nama, nim, prodi, email, tanggallahir, gender, password, profile_photo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ssssssss", $nama, $nim, $prodi, $email, $tanggallahir, $gender, $password, $newFileName);
                
                if ($stmt->execute()) {
                    echo "<script>alert('Akun berhasil dibuat!'); window.location.href='login.php';</script>";
                    exit();
                } else {
                    echo "<script>alert('Gagal membuat akun: " . addslashes($stmt->error) . "');</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Gagal memindahkan file.');</script>";
            }
        } else {
            echo "<script>alert('Ekstensi file tidak diperbolehkan.');</script>";
        }
    } else {
        echo "<script>alert('Error during file upload: " . $_FILES['profile_photo']['error'] . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrasi E-Library Teknik</title>
    <script src="https://cdn.tailwindcss.com"></script> 
</head>
<body class="bg-gradient-to-r from-indigo-500 via-purple-600 to-pink-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-4xl w-full relative">
        <h2 class="text-3xl font-bold mb-6 text-center text-gray-800">Daftar Akun Baru</h2>
        <form action="register.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-5">
                <div>
                    <label for="profile_photo" class="block mb-2 font-medium text-gray-700 cursor-pointer">Foto Profil</label>
                    <div class="flex items-center space-x-4">
                        <img id="imgPreview" src="https://placehold.co/80x80?text=No+Image" alt="Preview Foto" class="w-20 h-20 rounded-full object-cover border border-gray-300" />
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required class="block w-full cursor-pointer file:border-0 file:bg-indigo-600 file:text-white file:px-4 file:py-2 file:rounded-md file:font-semibold"/>
                    </div>
                </div>

                <div>
                    <label for="nama" class="block mb-2 font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" placeholder="Contoh: Achmad Buana" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label for="nim" class="block mb-2 font-medium text-gray-700">NIM</label>
                    <input type="text" id="nim" name="nim" placeholder="Contoh: 23103041038" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label for="prodi" class="block mb-2 font-medium text-gray-700">Prodi/Jurusan</label>
                    <input type="text" id="prodi" name="prodi" placeholder="Contoh: Teknik Informatika" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="email" class="block mb-2 font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" placeholder="Contoh: kamu@email.com" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <div>
                    <label for="tanggallahir" class="block mb-2 font-medium text-gray-700">Tanggal Lahir</label>
                    <input type="date" id="tanggallahir" name="tanggallahir" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <fieldset class="space-y-2">
                    <legend class="font-medium text-gray-700 mb-1">Gender</legend>
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="pria" checked class="form-radio text-indigo-600" />
                        <span class="ml-2">Pria</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="gender" value="wanita" class="form-radio text-indigo-600" />
                        <span class="ml-2">Wanita</span>
                    </label>
                </fieldset>

                <div>
                    <label for="password" class="block mb-2 font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" placeholder="Contoh: PasswordRahasia123" required class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
            </div>

            <div class="md:col-span-2 space-y-4">
    <button type="submit" name="signup" class="w-full bg-indigo-600 text-white p-3 rounded-md font-semibold hover:bg-indigo-700 transition duration-150">
        Daftar Sekarang
    </button>

    <p class="text-center text-sm text-gray-600">
        Sudah punya akun? 
        <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Login di sini</a>
    </p>
</div>

        </form>
    </div>

    <script>
        // Preview gambar saat dipilih
        const imgPreview = document.getElementById('imgPreview');
        const inputFile = document.getElementById('profile_photo');

        inputFile.addEventListener('change', function(e){
            const [file] = e.target.files;
            if (file) {
                imgPreview.src = URL.createObjectURL(file);
            } else {
                imgPreview.src = 'https://placehold.co/80x80?text=No+Image';
            }
        });
    </script>
</body>
</html>
