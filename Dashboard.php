<?php
session_start();
require 'service/database.php';

// Check if user is logged in
if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit();
}

$sql_kategori = "SELECT 
                    k.id AS id_kategori,
                    k.nama_kategori, 
                    COUNT(b.id) AS jumlah_buku
                FROM 
                    kategori_buku k
                LEFT JOIN 
                    buku b ON k.id = b.kategori_id
                GROUP BY 
                    k.id, k.nama_kategori
                ORDER BY
                    k.nama_kategori ASC";

// GANTI NAMA VARIABEL INI
$kategori_result = mysqli_query($db, $sql_kategori);

$sql_hitung_total = "SELECT COUNT(id) AS total_kategori FROM kategori_buku";
$result_hitung = mysqli_query($db, $sql_hitung_total);
$data_hitung = mysqli_fetch_assoc($result_hitung);
$jumlah_total_kategori = $data_hitung['total_kategori'] ?? 0;

// Pastikan kode ini sudah ada di bagian atas file Dashboard.php Anda
// Ambil ID user yang sedang login untuk pengecekan status bookmark
$current_user_id = $_SESSION['user_id'] ?? 0;

$sql_bookmarks_total = "SELECT COUNT(id) AS total_bookmarks FROM bookmarks";
$result_bookmarks = mysqli_query($db, $sql_bookmarks_total);
$data_bookmarks = mysqli_fetch_assoc($result_bookmarks);
$total_bookmarks = $data_bookmarks['total_bookmarks'] ?? 0;

// total buku
$sql_total_buku = "SELECT COUNT(id) AS total_buku FROM buku";
$result_total_buku = mysqli_query($db, $sql_total_buku);
$data_total_buku = mysqli_fetch_assoc($result_total_buku);
$total_buku = $data_total_buku['total_buku'] ?? 0;


// Query baru yang sudah dimodifikasi untuk mengambil status bookmark
$sql_buku_terbaru = "SELECT 
                        b.id,
                        b.judul,
                        b.penulis,
                        b.cover,
                        k.nama_kategori, 
                        b.penerbit,
                        b.tahun_terbit,
                        b.deskripsi,
                        CASE WHEN bm.id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
                    FROM 
                        buku b
                    LEFT JOIN 
                        kategori_buku k ON b.kategori_id = k.id
                    LEFT JOIN 
                        bookmarks bm ON b.id = bm.id_buku AND bm.id_user = ?
                    ORDER BY
                        b.id DESC
                    LIMIT 10";

// Kita gunakan prepared statement agar aman dan konsisten dengan kode Anda yang lain
$stmt_buku = $db->prepare($sql_buku_terbaru);
$stmt_buku->bind_param("i", $current_user_id);
$stmt_buku->execute();
$buku_terbaru_result = $stmt_buku->get_result();


// Fetch user data (kode ini tidak perlu diubah karena sudah menggunakan variabel lain)
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT nama, nim, email, prodi, profile_photo FROM user WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user_result = $stmt->get_result(); // Sebaiknya ganti juga agar konsisten
$user = $user_result->fetch_assoc();
$stmt->close();

// Set session variables
$_SESSION['nama'] = $user['nama'];
$_SESSION['profile_photo'] = $user['profile_photo'] ?? 'default.png';
$profilePhoto = htmlspecialchars($user['profile_photo'] ?? 'default.png');

// Handle profile update form submission
if (isset($_POST['save_changes'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $prodi = $_POST['prodi'];
    $userId = $_SESSION['user_id'];

    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $targetDir = "Uploads/profile_photos/";
        $fileName = basename($_FILES["profile_photo"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (in_array(strtolower($fileType), $allowedTypes)) {
            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $targetFilePath)) {
                $sql_update = "UPDATE user SET profile_photo = ? WHERE id = ?";
                $stmt = $db->prepare($sql_update);
                $stmt->bind_param("si", $fileName, $userId);
                $stmt->execute();
                $_SESSION['profile_photo'] = $fileName;
                $stmt->close();
            } else {
                error_log("File upload failed for: " . $targetFilePath);
            }
        } else {
            error_log("Invalid file type: " . $fileType);
        }
    }

    // Update other profile data
    $sql_update = "UPDATE user SET nama = ?, email = ?, prodi = ? WHERE id = ?";
    $stmt = $db->prepare($sql_update);
    $stmt->bind_param("sssi", $nama, $email, $prodi, $userId);
    $stmt->execute();
    $_SESSION['nama'] = $nama;
    $_SESSION['pesan_sukses'] = "Profil berhasil diperbarui!";
    $stmt->close();

    header("Location: Dashboard.php#settingsContent");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Library Teknik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 bg-indigo-800 text-white">
                <div class="flex items-center justify-center h-16 px-4 border-b border-indigo-700">
                    <div class="flex items-center">
                        <i class="fas fa-book-open text-2xl mr-2 text-indigo-300"></i>
                        <span class="text-xl font-semibold">E-Library Teknik</span>
                    </div>
                </div>
                <div class="flex flex-col flex-grow overflow-y-auto custom-scrollbar">
                    <div class="px-4 py-6">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Cari buku..." 
                                   class="w-full px-4 py-2 rounded-lg bg-indigo-700 text-white placeholder-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button id="searchBtn" class="absolute right-3 top-2 text-indigo-300 hover:text-white">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <nav class="flex-1 px-2 space-y-1">
                        <a href="#" id="dashboardLink" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-white bg-indigo-900">
                            <i class="fas fa-home mr-3 text-indigo-300"></i>
                            Dashboard
                        </a>
                        <a href="#" id="categoriesLink" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-indigo-200 hover:text-white">
                            <i class="fas fa-list-ul mr-3 text-indigo-300"></i>
                            Kategori Buku
                        </a>
                        <a href="#" id="bookmarksLink" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-indigo-200 hover:text-white">
                            <i class="fas fa-bookmark mr-3 text-indigo-300"></i>
                            Bookmarks
                        </a>
                        <a href="#" id="settingsLink" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-indigo-200 hover:text-white">
                            <i class="fas fa-cog mr-3 text-indigo-300"></i>
                            Settings
                        </a>
                    </nav>
                    <div class="px-4 py-4 border-t border-indigo-700">
                        <div class="flex items-center">
                            <img src="Uploads/profile_photos/<?php echo $profilePhoto; ?>" alt="Foto Profil" class="w-14 h-14 rounded-full object-cover border border-gray-300" />
                            <div class="ml-3">
                                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
                                <p class="text-xs font-medium text-indigo-300">Mahasiswa</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Sidebar -->
        <div id="mobileSidebar" class="fixed inset-0 z-40 md:hidden hidden">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" id="sidebarBackdrop"></div>
            <div class="relative flex flex-col w-72 h-full bg-indigo-800">
                <div class="flex items-center justify-between h-16 px-4 border-b border-indigo-700">
                    <div class="flex items-center">
                        <i class="fas fa-book-open text-2xl mr-2 text-indigo-300"></i>
                        <span class="text-xl font-semibold text-white">E-Library Teknik</span>
                    </div>
                    <button id="closeSidebar" class="text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex flex-col flex-grow overflow-y-auto custom-scrollbar">
                    <div class="px-4 py-6">
                        <div class="relative">
                            <input type="text" placeholder="Cari buku..." 
                                   class="w-full px-4 py-2 rounded-lg bg-indigo-700 text-white placeholder-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button class="absolute right-3 top-2 text-indigo-300 hover:text-white">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <nav class="flex-1 px-2 space-y-1">
                        <a href="#" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-white bg-indigo-900">
                            <i class="fas fa-home mr-3 text-indigo-300"></i>
                            Dashboard
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-indigo-200 hover:text-white">
                            <i class="fas fa-list-ul mr-3 text-indigo-300"></i>
                            Kategori Buku
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-indigo-200 hover:text-white">
                            <i class="fas fa-bookmark mr-3 text-indigo-300"></i>
                            Bookmarks
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-3 text-sm font-medium rounded-md text-indigo-200 hover:text-white">
                            <i class="fas fa-cog mr-3 text-indigo-300"></i>
                            Settings
                        </a>
                    </nav>
                    <div class="px-4 py-4 border-t border-indigo-700">
                        <div class="flex items-center">
                            <img src="Uploads/profile_photos/<?php echo $profilePhoto; ?>" alt="Foto Profil" class="h-10 w-10 rounded-full object-cover" />
                            <div class="ml-3">
                                <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
                                <p class="text-xs font-medium text-indigo-300">Member</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Top Navigation -->
            <div class="flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200">
                <div class="flex items-center">
                    <button id="mobileMenuButton" class="md:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="relative ml-4 md:hidden">
                        <input type="text" placeholder="Cari buku..." 
                               class="w-full px-4 py-2 rounded-lg bg-gray-100 text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button class="absolute right-3 top-2 text-gray-500 hover:text-indigo-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 text-gray-500 hover:text-indigo-600">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center focus:outline-none">
                            <span class="hidden md:inline-block mr-2 text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                            <img src="Uploads/profile_photos/<?php echo $profilePhoto; ?>" alt="Foto Profil" class="h-8 w-8 rounded-full object-cover" />
                        </button>
                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="logout.php" class="logout-link block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Keluar</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
<div class="flex-1 overflow-auto p-4 custom-scrollbar">
    
    <div id="dashboardContent" class="fade-in">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
            <p class="text-gray-600">Apa yang ingin anda baca hari ini?</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600"><i class="fas fa-book text-xl"></i></div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Jumlah Kategori Buku</p>
                        <p class="text-xl font-semibold text-gray-800"><?= $jumlah_total_kategori ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600"><i class="fas fa-bookmark text-xl"></i></div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Bookmarks</p>
                        <p class="text-xl font-semibold text-gray-800"><?= $total_bookmarks ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600"><i class="fas fa-clock text-xl"></i></div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Buku</p>
                        <p class="text-xl font-semibold text-gray-800"><?= $total_buku ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Semua Buku</h2>
                <a href="daftar-buku.php" class="text-sm text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <?php if ($buku_terbaru_result && mysqli_num_rows($buku_terbaru_result) > 0): ?>
                    <?php while($buku = mysqli_fetch_assoc($buku_terbaru_result)): ?>
                        <?php $coverPath = !empty($buku['cover']) ? 'Upload/covers/' . htmlspecialchars($buku['cover']) : 'path/to/default-cover.png'; ?>
                        <div class="book-card bg-white rounded-lg shadow overflow-hidden transition duration-300">
                            <div class="relative pb-3/4">
                                <img class="w-full h-48 object-cover" src="<?= $coverPath; ?>" alt="Cover Buku <?= htmlspecialchars($buku['judul']); ?>">
                                <button type="button" class="bookmark-btn absolute top-2 right-2 p-2 bg-white rounded-full shadow-md text-gray-500 hover:text-red-500" data-id="<?= $buku['id']; ?>"><i class="far fa-bookmark"></i></button>
                            </div>
                            <div class="p-3">
                                <h3 class="font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($buku['judul']); ?>"><?= htmlspecialchars($buku['judul']); ?></h3>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($buku['penulis']); ?></p>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded"><?= htmlspecialchars($buku['nama_kategori'] ?? 'Tanpa Kategori'); ?></span>
                                    <a href="#" 
                                        class="detail-link text-indigo-600 hover:text-indigo-800 text-sm"
                                        data-id="<?= $buku['id']; ?>"
                                        data-judul="<?= htmlspecialchars($buku['judul']); ?>"
                                        data-penulis="<?= htmlspecialchars($buku['penulis']); ?>"
                                        data-penerbit="<?= htmlspecialchars($buku['penerbit'] ?? 'Tidak diketahui'); ?>"
                                        data-tahun="<?= htmlspecialchars($buku['tahun_terbit'] ?? '-'); ?>"
                                        data-deskripsi="<?= htmlspecialchars($buku['deskripsi'] ?? 'Deskripsi tidak tersedia.'); ?>"
                                        data-cover="<?= !empty($buku['cover']) ? 'Upload/covers/' . htmlspecialchars($buku['cover']) : 'path/to/default-cover.png'; ?>">
                                        Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-8">
                        <p class="text-gray-500">Belum ada buku yang ditambahkan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="categoriesContent" class="hidden fade-in">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Kategori Buku</h1>
            <p class="text-gray-600">Telusuri buku berdasarkan kategori</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php mysqli_data_seek($kategori_result, 0); // Reset pointer untuk loop lagi ?>
            <?php while($row = mysqli_fetch_assoc($kategori_result)): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden transition duration-300 hover:shadow-lg">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600"><i class="fas fa-book text-xl"></i></div>
                            <div class="ml-4">
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($row['nama_kategori']) ?></h3>
                                <p class="text-sm text-gray-600"><?= $row['jumlah_buku'] ?> buku</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="javascript:void(0)" data-id="<?= $row['id_kategori'] ?>" class="lihat-semua-link text-indigo-600 hover:text-indigo-800 text-sm font-medium">Lihat Semua</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="bookmarksContent" class="hidden fade-in">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Bookmark Saya</h1>
        <p class="text-gray-600">Buku yang Anda simpan untuk dibaca nanti.</p>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="divide-y divide-gray-200">

            <?php
            // Query untuk mengambil semua buku yang di-bookmark oleh user ini.
            // Kita menggunakan variabel $current_user_id yang sudah kita definisikan di Langkah 3.
            $sql_my_bookmarks = "SELECT 
                                    b.id, b.judul, b.penulis, b.cover, b.deskripsi,
                                    b.penerbit, b.tahun_terbit, k.nama_kategori
                                 FROM buku b 
                                 JOIN bookmarks bm ON b.id = bm.id_buku
                                 LEFT JOIN kategori_buku k ON b.kategori_id = k.id
                                 WHERE bm.id_user = ?
                                 ORDER BY bm.tanggal_bookmark DESC";
            
            $stmt_bm = $db->prepare($sql_my_bookmarks);
            $stmt_bm->bind_param("i", $current_user_id);
            $stmt_bm->execute();
            $my_bookmarks_result = $stmt_bm->get_result();

            if ($my_bookmarks_result->num_rows > 0) :
                while ($book = $my_bookmarks_result->fetch_assoc()) :
                    // Tentukan path cover buku
                    $coverPath = !empty($book['cover']) ? 'Upload/covers/' . htmlspecialchars($book['cover']) : 'path/to/default-cover.png'; // Sesuaikan path default jika perlu
            ?>
                    <div class="p-4 hover:bg-gray-50 transition duration-150">
                        <div class="flex items-start space-x-4">
                            <img class="h-24 w-16 object-cover rounded flex-shrink-0" src="<?= $coverPath; ?>" alt="Cover <?= htmlspecialchars($book['judul']); ?>">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($book['judul']); ?></h3>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($book['penulis']); ?></p>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($book['deskripsi']); ?></p>
                                <div class="mt-2">
                                    <a href="#" 
                                       class="detail-link text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                       data-id="<?= $book['id']; ?>"
                                       data-judul="<?= htmlspecialchars($book['judul']); ?>"
                                       data-penulis="<?= htmlspecialchars($book['penulis']); ?>"
                                       data-penerbit="<?= htmlspecialchars($book['penerbit'] ?? 'Tidak diketahui'); ?>"
                                       data-tahun="<?= htmlspecialchars($book['tahun_terbit'] ?? '-'); ?>"
                                       data-deskripsi="<?= htmlspecialchars($book['deskripsi'] ?? 'Deskripsi tidak tersedia.'); ?>"
                                       data-cover="<?= $coverPath; ?>">
                                       Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php 
                endwhile;
            else :
            ?>
                <p class="p-6 text-center text-gray-500">Anda belum memiliki bookmark.</p>
            <?php endif; 
            $stmt_bm->close();
            ?>

        </div>
    </div>
</div>

    <div id="settingsContent" class="hidden fade-in">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Pengaturan</h1>
            <p class="text-gray-600">Kelola akun dan preferensi Anda</p>
        </div>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="divide-y divide-gray-200">
                <div class="p-6">
                    <form id="profileForm" action="Dashboard.php#settingsContent" method="POST" enctype="multipart/form-data">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Profil</h2>
                        <div class="flex items-center mb-6">
                            <img id="profileImage" src="Uploads/profile_photos/<?php echo $profilePhoto; ?>" alt="Foto Profil" class="h-16 w-16 rounded-full object-cover" />
                            <div class="ml-4">
                                <input type="file" id="profileImageInput" name="profile_photo" accept="image/*" class="hidden" />
                                <label for="profileImageInput" class="cursor-pointer text-indigo-600 hover:text-indigo-800 text-sm font-medium">Ganti Foto</label>
                                <p class="text-xs text-gray-500 mt-1">JPG, GIF atau PNG. Maksimal 2MB</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="nim" class="block text-sm font-medium text-gray-700 mb-1">NIM</label>
                                <input type="text" id="nim" name="nim" value="<?php echo htmlspecialchars($user['nim']); ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="prodi" class="block text-sm font-medium text-gray-700 mb-1">Prodi</label>
                                <input type="text" id="prodi" name="prodi" value="<?php echo htmlspecialchars($user['prodi']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" name="save_changes" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Simpan</button>
                        </div>
                    </form>
                </div>
                <form id="passwordForm">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Update Password</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label>
                                <input type="password" id="currentPassword" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="md:col-span-2"></div>
                            <div>
                                <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                                <input type="password" id="newPassword" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                                <input type="password" id="confirmPassword" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" id="updatePasswordBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Update Password</button>
                        </div>
                    </div>
                </form>
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Pengaturan Akun</h2>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Hapus Akun</h3>
                            <p class="text-sm text-gray-500">Setelah akun dihapus, semua data akan dihapus secara permanen.</p>
                        </div>
                        <button id="deleteAccountBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Hapus Akun</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="searchResultsContent" class="hidden fade-in"></div>
    </div>



    </div>
        <div id="booksModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
        <div class="relative mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-2xl leading-6 font-medium text-gray-900">Daftar Buku</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                
                <div id="modalContent" class="mt-2 py-3">
                    <p id="modalLoading" class="text-center">Memuat data...</p>
                    </div>

            </div>
        </div>
    </div>

    <div id="bookDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 transition-opacity">
    <div id="modalContentArea" class="bg-white rounded-lg shadow-xl w-full max-w-3xl m-4 transform transition-transform scale-95">
        
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800">Detail Buku</h2>
                <button id="closeDetailModalBtn" class="text-gray-500 hover:text-gray-800 focus:outline-none">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>

            <div class="mt-4 md:flex">
                <div class="md:w-1/3 md:pr-8 flex-shrink-0 mb-4 md:mb-0">
                    <img id="modal-book-cover" src="" alt="Book Cover" class="w-full h-auto object-cover rounded-lg shadow-md">
                </div>

                <div class="md:w-2/3">
                    <h3 id="modal-book-title" class="text-3xl font-bold text-gray-900 mb-2"></h3>
                    
                    <div class="space-y-3 text-gray-700">
                        <div class="flex items-center">
                            <i class="fas fa-user-edit w-5 mr-3 text-gray-500"></i>
                            <p><strong>Penulis:</strong> <span id="modal-book-author"></span></p>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-building w-5 mr-3 text-gray-500"></i>
                            <p><strong>Penerbit:</strong> <span id="modal-book-publisher"></span></p>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt w-5 mr-3 text-gray-500"></i>
                            <p><strong>Tahun Terbit:</strong> <span id="modal-book-year"></span></p>
                        </div>
                    </div>
                    <div class="mt-4 md:flex">
                        <div class="md:w-1/3 md:pr-8 flex-shrink-0 mb-4 md:mb-0">
                    </div>

                <div class="md:w-2/3">
                    </div>
                </div> 
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">Deskripsi</h4>
                <div class="max-h-40 overflow-y-auto custom-scrollbar pr-2">
                    <p id="modal-book-description" class="text-sm text-gray-600 leading-relaxed"></p>
                </div>
            </div>
        </div> 
    </div> 
</div>

                    <div class="mt-6 pt-4 border-t border-gray-200">
                         <a id="modal-read-book-link" href="#" class="inline-block w-full text-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                            Baca Buku
                         </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
    <script>
        // DOM Elements
    // =================================================
    // BAGIAN 1: DEKLARASI SEMUA ELEMEN DOM (SATU KALI)
    // =================================================
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const dashboardLink = document.getElementById('dashboardLink');
    const categoriesLink = document.getElementById('categoriesLink');
    const bookmarksLink = document.getElementById('bookmarksLink');
    const settingsLink = document.getElementById('settingsLink');
    const dashboardContent = document.getElementById('dashboardContent');
    const categoriesContent = document.getElementById('categoriesContent');
    const bookmarksContent = document.getElementById('bookmarksContent');
    const settingsContent = document.getElementById('settingsContent');
    const profileImageInput = document.getElementById('profileImageInput');
    const profileImage = document.getElementById('profileImage');
    const passwordForm = document.getElementById('passwordForm');
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    const logoutLinks = document.querySelectorAll('.logout-link');
    const modal = document.getElementById('booksModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    const lihatSemuaLinks = document.querySelectorAll('.lihat-semua-link');
    


    // =================================================
    // BAGIAN 2: SEMUA EVENT LISTENER
    // =================================================

    // --- Event Listener untuk Navigasi & UI Dasar ---
    mobileMenuButton.addEventListener('click', () => mobileSidebar.classList.remove('hidden'));
    closeSidebar.addEventListener('click', () => mobileSidebar.classList.add('hidden'));
    sidebarBackdrop.addEventListener('click', () => mobileSidebar.classList.add('hidden'));

    userMenuButton.addEventListener('click', () => userDropdown.classList.toggle('hidden'));
    document.addEventListener('click', (e) => {
        if (!userMenuButton.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
    
    // --- Event Listener untuk Pindah Konten (SPA Logic) ---
    dashboardLink.addEventListener('click', (e) => showPage(e, dashboardLink, dashboardContent));
    categoriesLink.addEventListener('click', (e) => showPage(e, categoriesLink, categoriesContent));
    bookmarksLink.addEventListener('click', (e) => showPage(e, bookmarksLink, bookmarksContent));
    settingsLink.addEventListener('click', (e) => showPage(e, settingsLink, settingsContent));
    
    // --- Event Listener untuk Popup Kategori (VERSI BARU YANG LENGKAP) ---
    lihatSemuaLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const kategoriId = this.dataset.id;
            
            showModal();
            modalContent.innerHTML = '<p id="modalLoading" class="text-center py-5">Memuat data...</p>';

            fetch(`api_get_books.php?id=${kategoriId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    modalTitle.innerText = `Daftar Buku: ${data.nama_kategori}`;
                    
                    let tableHTML = `<div class="overflow-x-auto"><table class="min-w-full leading-normal"><thead><tr><th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">Cover</th><th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">Judul</th><th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">Penulis</th><th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">Penerbit</th><th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">Tahun</th></tr></thead><tbody>`;

                    if (data.buku && data.buku.length > 0) {
                        data.buku.forEach(buku => {
                            const imagePath = `upload/covers/${buku.cover}`; // <-- SESUAIKAN PATH INI
                            const defaultImage = 'path/to/default-image.png'; // <-- GANTI DENGAN GAMBAR DEFAULT
                            
                            tableHTML += `<tr class="hover:bg-gray-50"><td class="px-5 py-3 border-b border-gray-200"><img src="${buku.cover ? imagePath : defaultImage}" alt="Cover" class="w-16 h-24 object-cover rounded"></td><td class="px-5 py-3 border-b border-gray-200 align-top"><p class="font-semibold">${buku.judul}</p></td><td class="px-5 py-3 border-b border-gray-200 align-top">${buku.penulis}</td><td class="px-5 py-3 border-b border-gray-200 align-top">${buku.penerbit}</td><td class="px-5 py-3 border-b border-gray-200 align-top">${buku.tahun_terbit}</td></tr>`;
                        });
                    } else {
                        tableHTML += `<tr><td colspan="5" class="text-center py-10">Belum ada buku dalam kategori ini.</td></tr>`;
                    }
                    
                    tableHTML += `</tbody></table></div>`;
                    modalContent.innerHTML = tableHTML;
                })
                .catch(error => {
                    modalContent.innerHTML = '<p class="text-red-500 text-center">Gagal memuat data.</p>';
                    console.error('Error:', error);
                });
        });
    });

    closeModalBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) hideModal();
    });

    document.body.addEventListener('click', function(event) {
        
        // 1. Cek apakah yang diklik adalah tombol bookmark
        const bookmarkBtn = event.target.closest('.bookmark-btn');

        if (bookmarkBtn) {
            // 2. Ambil ID buku dari atribut 'data-id'
            const bookId = bookmarkBtn.dataset.id;
            const icon = bookmarkBtn.querySelector('i');

            // 3. Kirim permintaan ke server di belakang layar (AJAX/Fetch)
            fetch('handle_bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_buku=' + bookId
            })
            .then(response => response.json()) // 4. Terima balasan dari server
            .then(data => {
                if (data.status === 'success') {
                    // 5. Jika sukses, ubah ikonnya secara langsung
                    if (data.action === 'bookmarked') {
                        // Jika berhasil di-bookmark, ubah ikon jadi solid & berwarna
                        icon.classList.remove('far'); 
                        icon.classList.add('fas', 'text-indigo-600'); 
                    } else { // Jika aksinya 'removed'
                        // Jika berhasil di-unbookmark, kembalikan ikon jadi outline
                        icon.classList.remove('fas', 'text-indigo-600'); 
                        icon.classList.add('far');
                    }
                } else {
                    // Jika gagal, tampilkan pesan error
                    alert(data.message || 'Terjadi kesalahan!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Tidak bisa terhubung ke server.');
            });
        }
    });


    // --- Event Listener untuk Fitur Lainnya ---
    // Gantikan blok logoutLinks yang lama dengan ini
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            // Mencegah link langsung berpindah halaman
            event.preventDefault(); 

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Anda akan keluar dari sesi ini.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, keluar!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Jika pengguna menekan tombol "Ya, keluar!"
                if (result.isConfirmed) {
                    // Arahkan ke halaman logout
                    window.location.href = this.href; 
                }
            });
        });
    });
        deleteAccountBtn.addEventListener('click', function(event) {
        event.preventDefault(); // Mencegah perilaku default jika tombol ada di dalam form

        Swal.fire({
            title: 'Anda yakin ingin menghapus akun?',
            text: "Tindakan ini tidak dapat diurungkan! Semua data Anda akan hilang secara permanen.",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus akun saya!',
            cancelButtonText: 'Jangan Hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                // Di sini Anda akan menambahkan logika untuk menghapus akun.
                // Biasanya, ini akan mengarahkan ke file PHP terpisah
                // atau mengirim permintaan AJAX.
                
                // Contoh: Mengarahkan ke skrip penghapusan
                // window.location.href = 'service/delete_account.php';

                // Untuk sekarang, kita hanya akan menampilkan pesan sukses
                Swal.fire(
                    'Dihapus!',
                    'Akun Anda telah dihapus (simulasi).',
                    'success'
                );
            }
        });
    });
        


    // =================================================
    // BAGIAN 3: FUNGSI-FUNGSI BANTUAN (HELPER)
    // =================================================
    function showModal() { modal.classList.remove('hidden'); }
    function hideModal() { modal.classList.add('hidden'); }

    function setActiveNav(activeLink) {
        [dashboardLink, categoriesLink, bookmarksLink, settingsLink].forEach(link => {
            link.classList.remove('bg-indigo-900');
            link.classList.add('hover:bg-indigo-700');
        });
        activeLink.classList.add('bg-indigo-900');
    }

    function showContent(contentToShow) {
        [dashboardContent, categoriesContent, bookmarksContent, settingsContent].forEach(content => {
            content.classList.add('hidden');
        });
        contentToShow.classList.remove('hidden');
    }
    
    function showPage(event, linkElement, contentElement){
        event.preventDefault();
        setActiveNav(linkElement);
        showContent(contentElement);
    }
    
    // Default view on page load
    document.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash;
        if (hash === '#settingsContent') {
            showPage({preventDefault: () => {}}, settingsLink, settingsContent);
        } else if (hash === '#categoriesContent') {
            showPage({preventDefault: () => {}}, categoriesLink, categoriesContent);
        } else if (hash === '#bookmarksContent') {
            showPage({preventDefault: () => {}}, bookmarksLink, bookmarksContent);
        } else {
            showPage({preventDefault: () => {}}, dashboardLink, dashboardContent);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {

    // --- LOGIKA UNTUK MODAL DETAIL BUKU ---

    // Ambil elemen-elemen modal
    const bookDetailModal = document.getElementById('bookDetailModal');
    const modalContentArea = document.getElementById('modalContentArea');
    const closeDetailModalBtn = document.getElementById('closeDetailModalBtn');

    // Ambil elemen-elemen konten di dalam modal
    const modalCover = document.getElementById('modal-book-cover');
    const modalTitle = document.getElementById('modal-book-title');
    const modalAuthor = document.getElementById('modal-book-author');
    const modalPublisher = document.getElementById('modal-book-publisher');
    const modalYear = document.getElementById('modal-book-year');
    const modalReadLink = document.getElementById('modal-read-book-link');
    const modalDescription = document.getElementById('modal-book-description');

    

    
    // Fungsi untuk membuka modal
    const openModal = (data) => {
        // Isi data ke dalam modal
        modalCover.src = data.cover;
        modalTitle.textContent = data.judul;
        modalAuthor.textContent = data.penulis;
        modalPublisher.textContent = data.penerbit;
        modalYear.textContent = data.tahun;
        modalDescription.textContent = data.deskripsi || 'Deskripsi tidak tersedia.';
        
        // Atur link untuk tombol "Baca Buku"
        modalReadLink.href = `Dashboard.php?id=${data.id}`; // Ganti dengan URL yang sesuai

        // Tampilkan modal dengan animasi
        bookDetailModal.classList.remove('hidden');
        setTimeout(() => {
            modalContentArea.classList.remove('scale-95');
        }, 50); // Small delay to allow transition
    };

    // Fungsi untuk menutup modal
    const closeModal = () => {
        modalContentArea.classList.add('scale-95');
        setTimeout(() => {
            bookDetailModal.classList.add('hidden');
        }, 200); // Wait for transition to finish
    };

    // Event Delegation: Dengerin klik pada elemen parent
    document.body.addEventListener('click', (event) => {
        // Cek apakah yang diklik adalah link dengan class 'detail-link'
        if (event.target.classList.contains('detail-link')) {
            event.preventDefault(); // Mencegah link pindah halaman

            const link = event.target;
            const bookData = {
                id: link.dataset.id,
                judul: link.dataset.judul,
                penulis: link.dataset.penulis,
                penerbit: link.dataset.penerbit,
                tahun: link.dataset.tahun,
                cover: link.dataset.cover,
                deskripsi: link.dataset.deskripsi
            };
            
            openModal(bookData);
        }
    });


    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    const dashboardContent = document.getElementById('dashboardContent'); // Konten utama
    const searchResultsContent = document.getElementById('searchResultsContent'); // Wadah hasil pencarian
    console.log('Mencari wadah untuk hasil pencarian:', searchResultsContent);

    function performSearch() {
        const query = searchInput.value.trim();

        if (query === '') {
            // Jika input kosong, tampilkan kembali dashboard utama
            dashboardContent.classList.remove('hidden');
            searchResultsContent.classList.add('hidden');
            return;
        }

        // Tampilkan pesan loading dan sembunyikan konten utama
        dashboardContent.classList.add('hidden');
        searchResultsContent.classList.remove('hidden');
        searchResultsContent.innerHTML = '<p class="text-center text-gray-500 py-10">Mencari...</p>';

        // Panggil "mesin pencari" kita
        fetch(`cari_buku.php?q=${encodeURIComponent(query)}`)
    .then(response => response.text())
    .then(html => {
        // =======================================================
        // TAMBAHKAN BARIS INI UNTUK DEBUGGING
        console.log("Data HTML diterima dari server:", html);
        // =======================================================

        // Tampilkan hasil HTML dari server
        searchResultsContent.innerHTML = html;
    });
    }

    // Jalankan pencarian saat tombol search diklik
    searchBtn.addEventListener('click', performSearch);

    // Jalankan pencarian saat menekan tombol "Enter" di kolom input
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            performSearch();
        }
    });

    // Event listener untuk tombol close
    closeDetailModalBtn.addEventListener('click', closeModal);

    // Event listener untuk menutup modal jika klik di area backdrop (luar konten)
    bookDetailModal.addEventListener('click', (event) => {
        if (event.target === bookDetailModal) {
            closeModal();
        }
    });

    // Event listener untuk menutup modal dengan tombol 'Escape'
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !bookDetailModal.classList.contains('hidden')) {
            closeModal();
        }
    });

     const sidebarLinks = {
        '#dashboard': document.getElementById('dashboardLink'),
        '#categories': document.getElementById('categoriesLink'),
        '#bookmarks': document.getElementById('bookmarksLink'),
        '#settings': document.getElementById('settingsLink')
    };

    const contentPanels = {
        '#dashboard': document.getElementById('dashboardContent'),
        '#categories': document.getElementById('categoriesContent'),
        '#bookmarks': document.getElementById('bookmarksContent'),
        '#settings': document.getElementById('settingsContent')
    };

    function loadBookmarksContent() {
    const bookmarksContainer = document.querySelector('#bookmarksContent .divide-y');
    if (bookmarksContainer) {
        // Tampilkan pesan loading untuk UX yang lebih baik
        bookmarksContainer.innerHTML = '<p class="p-6 text-center text-gray-500">Memuat bookmark...</p>';

        fetch('ambil_bookmarks.php')
            .then(response => response.text()) // Ambil respons sebagai HTML mentah
            .then(html => {
                // Ganti konten loading dengan HTML baru dari server
                bookmarksContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Gagal memuat bookmark:', error);
                bookmarksContainer.innerHTML = '<p class="p-6 text-center text-red-500">Gagal memuat bookmark.</p>';
            });
    }
}

    // 2. Fungsi untuk menampilkan tab yang benar
    function showTab(targetHash) {
        // Jika tidak ada target, default ke #dashboard
        if (!targetHash || !contentPanels[targetHash]) {
            targetHash = '#dashboard';
        }

        // Sembunyikan semua panel konten
        Object.values(contentPanels).forEach(panel => {
            if (panel) panel.classList.add('hidden');
        });

         if (targetHash === '#bookmarks') {
        loadBookmarksContent();
         }

        // Hapus style aktif dari semua link
        Object.values(sidebarLinks).forEach(link => {
            if (link) {
                link.classList.remove('bg-indigo-900', 'text-white');
                link.classList.add('text-indigo-200', 'hover:text-white');
            }
        });

        // Tampilkan panel yang dituju
        if (contentPanels[targetHash]) {
            contentPanels[targetHash].classList.remove('hidden');
        }

        // Beri style aktif pada link yang sesuai
        if (sidebarLinks[targetHash]) {
            sidebarLinks[targetHash].classList.add('bg-indigo-900', 'text-white');
            sidebarLinks[targetHash].classList.remove('text-indigo-200', 'hover:text-white');
        }
    }



    // 3. Tampilkan tab yang benar saat halaman pertama kali dimuat
    // Berdasarkan hash di URL, atau default ke #dashboard jika tidak ada
    showTab(window.location.hash);


    // 4. Tambahkan event listener untuk setiap link di sidebar
    Object.keys(sidebarLinks).forEach(hash => {
        const link = sidebarLinks[hash];
        if (link) {
            link.addEventListener('click', (event) => {
                event.preventDefault(); // Mencegah link pindah halaman
                
                // Perbarui hash di URL tanpa me-refresh halaman
                window.location.hash = hash;
                
                // Tampilkan tab yang sesuai
                showTab(hash);
            });
        }
    });

    // Opsional: Dengarkan perubahan hash jika pengguna menggunakan tombol back/forward browser
    window.addEventListener('hashchange', () => {
        showTab(window.location.hash);
    });

});

    </script>
</body>
</html>