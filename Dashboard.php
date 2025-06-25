<?php
session_start();
require 'service/database.php';

// Check if user is logged in
if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit();
}

// SQL query to get categories and book counts
// SQL query to get categories, icons, and book counts
$sql_kategori = "
    SELECT
        k.id AS id_kategori,
        k.nama_kategori,
        k.icon_class, -- Tambahkan baris ini
        COUNT(b.id) AS jumlah_buku
    FROM
        kategori_buku k
    LEFT JOIN
        buku b ON k.id = b.kategori_id
    GROUP BY
        k.id, k.nama_kategori, k.icon_class -- Tambahkan kolom icon ke GROUP BY
    ORDER BY
        k.nama_kategori ASC
";

$kategori_result = mysqli_query($db, $sql_kategori);

// Count total categories
$sql_hitung_total = "SELECT COUNT(id) AS total_kategori FROM kategori_buku";
$result_hitung = mysqli_query($db, $sql_hitung_total);
$data_hitung = mysqli_fetch_assoc($result_hitung);
$jumlah_total_kategori = $data_hitung['total_kategori'] ?? 0;

// Get current user ID for bookmark checking
$current_user_id = $_SESSION['user_id'] ?? 0;

// Total bookmarks
$sql_bookmarks_total = "SELECT COUNT(id) AS total_bookmarks FROM bookmarks";
$result_bookmarks = mysqli_query($db, $sql_bookmarks_total);
$data_bookmarks = mysqli_fetch_assoc($result_bookmarks);
$total_bookmarks = $data_bookmarks['total_bookmarks'] ?? 0;

// Total books
$sql_total_buku = "SELECT COUNT(id) AS total_buku FROM buku";
$result_total_buku = mysqli_query($db, $sql_total_buku);
$data_total_buku = mysqli_fetch_assoc($result_total_buku);
$total_buku = $data_total_buku['total_buku'] ?? 0;

// Query to get latest books with bookmark status
$sql_buku_terbaru = "
    SELECT
        b.id,
        b.judul,
        b.penulis,
        b.cover,
        k.nama_kategori,
        b.penerbit,
        b.tahun_terbit,
        b.stok,
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
    LIMIT 10
";

// Prepared statement for security
$stmt_buku = $db->prepare($sql_buku_terbaru);
$stmt_buku->bind_param("i", $current_user_id);
$stmt_buku->execute();
$buku_terbaru_result = $stmt_buku->get_result();

// Fetch user data
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT nama, nim, email, prodi, profile_photo FROM user WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user_result = $stmt->get_result();
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
                            <input type="text" placeholder="Cari buku..." class="searchInput w-full px-4 py-2 rounded-lg bg-indigo-700 text-white placeholder-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button class="searchBtn absolute right-3 top-2 text-indigo-300 hover:text-white">
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
                            <input type="text" placeholder="Cari buku..." class="searchInput w-full px-4 py-2 rounded-lg bg-indigo-700 text-white placeholder-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button class="searchBtn absolute right-3 top-2 text-indigo-300 hover:text-white">
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
                        <input type="text" placeholder="Cari buku..." class="searchInput w-full px-4 py-2 rounded-lg bg-gray-100 text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button class="searchBtn absolute right-3 top-2 text-gray-500 hover:text-indigo-600">
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
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                                    <i class="fas fa-layer-group text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Jumlah Kategori Buku</p>
                                    <p class="text-xl font-semibold text-gray-800"><?= $jumlah_total_kategori ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600">
                                    <i class="fas fa-bookmark text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Bookmarks</p>
                                    <p class="text-xl font-semibold text-gray-800"><?= $total_bookmarks ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                    <i class="fas fa-book-atlas text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Buku</p>
                                    <p class="text-xl font-semibold text-gray-800"><?= $total_buku ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-800">Buku Terbaru</h2>
                            <a href="#" id="lihatSemuaDashboardBtn" class="text-sm text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <?php if ($buku_terbaru_result && mysqli_num_rows($buku_terbaru_result) > 0): ?>
                                <?php while($buku = mysqli_fetch_assoc($buku_terbaru_result)): ?>
                                    <?php $coverPath = !empty($buku['cover']) ? 'Upload/covers/' . htmlspecialchars($buku['cover']) : 'path/to/default-cover.png'; ?>
                                    <div class="book-card bg-white rounded-lg shadow overflow-hidden transition duration-300">
                                        <div class="relative pb-3/4">
                                            <img class="w-full h-48 object-cover" src="<?= $coverPath; ?>" alt="Cover Buku <?= htmlspecialchars($buku['judul']); ?>">
                                                                                    </div>
                                        <div class="p-3">
                                            <h3 class="font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($buku['judul']); ?>"><?= htmlspecialchars($buku['judul']); ?></h3>
                                            <p class="text-sm text-gray-600 whitespace-nowrap overflow-hidden text-ellipsis"><?= htmlspecialchars($buku['penulis']); ?></p>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded"><?= htmlspecialchars($buku['nama_kategori'] ?? 'Tanpa Kategori'); ?></span>
                                                <a href="#"
                                                   class="detail-link text-indigo-600 hover:text-indigo-800 text-sm"
                                                   data-id="<?= $buku['id']; ?>"
                                                   data-judul="<?= htmlspecialchars($buku['judul']); ?>"
                                                   data-penulis="<?= htmlspecialchars($buku['penulis']); ?>"
                                                   data-penerbit="<?= htmlspecialchars($buku['penerbit'] ?? 'Tidak diketahui'); ?>"
                                                   data-tahun="<?= htmlspecialchars($buku['tahun_terbit'] ?? '-'); ?>"
                                                   data-stok="<?= htmlspecialchars($buku['stok'] ?? '0'); ?>"
                                                   data-deskripsi="<?= htmlspecialchars($buku['deskripsi'] ?? 'Deskripsi tidak tersedia.'); ?>"
                                                   data-cover="<?= !empty($buku['cover']) ? 'Upload/covers/' . htmlspecialchars($buku['cover']) : 'path/to/default-cover.png'; ?>">
                                                    Pinjam
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
                                        <?php
                                            // Tentukan kelas ikon. Jika kosong di DB, gunakan ikon default.
                                            $iconClass = !empty($row['icon_class']) ? htmlspecialchars($row['icon_class']) : 'fas fa-book';
                                        ?>
                                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                                            <i class="<?= $iconClass ?> text-xl"></i>
                                        </div>
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
                        <p class="text-gray-600">Buku yang Anda simpan untuk dipinjam nanti.</p>
                    </div>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="divide-y divide-gray-200">
                            <?php
                            // Query untuk mengambil semua buku yang di-bookmark oleh user ini.
                            $sql_my_bookmarks = "
                                SELECT
                                    b.id, b.judul, b.penulis, b.cover, b.deskripsi,
                                    b.penerbit, b.tahun_terbit, b.stok, k.nama_kategori
                                FROM buku b
                                JOIN bookmarks bm ON b.id = bm.id_buku
                                LEFT JOIN kategori_buku k ON b.kategori_id = k.id
                                WHERE bm.id_user = ?
                                ORDER BY bm.tanggal_bookmark DESC
                            ";

                            $stmt_bm = $db->prepare($sql_my_bookmarks);
                            $stmt_bm->bind_param("i", $current_user_id);
                            $stmt_bm->execute();
                            $my_bookmarks_result = $stmt_bm->get_result();

                            if ($my_bookmarks_result->num_rows > 0) :
                                while ($book = $my_bookmarks_result->fetch_assoc()) :
                                    $coverPath = !empty($book['cover']) ? 'Upload/covers/' . htmlspecialchars($book['cover']) : 'path/to/default-cover.png';
                            ?>
                                    <div class="bookmark-item relative p-4 hover:bg-gray-50 transition-all duration-300">
                                        <button class="remove-bookmark-btn absolute top-2 right-2 text-gray-400 hover:text-red-600 hover:scale-125 transition-transform" data-id="<?= $book['id']; ?>" title="Hapus bookmark">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <div class="flex items-start space-x-4">
                                            <img class="h-24 w-16 object-cover rounded flex-shrink-0" src="<?= $coverPath; ?>" alt="Cover <?= htmlspecialchars($book['judul']); ?>">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($book['judul']); ?></h3>
                                                <p class="text-sm text-gray-600"><?= htmlspecialchars($book['penulis']); ?></p>
                                                <p class="text-sm text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($book['deskripsi']); ?></p>
                                                <div class="mt-2">
                                                    <a href="#"
                                                       class="detail-link text-indigo-600 hover:text-indigo-800 text-sm"
                                                       data-aksi="kembalikan"
                                                       data-id="<?= $book['id']; ?>"
                                                       data-judul="<?= htmlspecialchars($book['judul']); ?>"
                                                       data-penulis="<?= htmlspecialchars($book['penulis']); ?>"
                                                       data-penerbit="<?= htmlspecialchars($book['penerbit']); ?>"
                                                       data-tahun="<?= $book['tahun_terbit']; ?>"
                                                       data-stok="<?= $book['stok']; ?>"
                                                       data-deskripsi="<?= htmlspecialchars($book['deskripsi']); ?>"
                                                       data-cover="<?= $coverPath; ?>">
                                                        Kembalikan Buku
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                endwhile;
                            else :
                            ?>
                                <p id="no-bookmarks-message" class="p-6 text-center text-gray-500">Anda belum memiliki bookmark.</p>
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
                                        <div></div>
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
                </div>
                <div id="searchResultsContent" class="hidden fade-in"></div>
            </div>
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
                            <div class="flex items-center">
                                <i class="fas fa-book w-5 mr-3 text-gray-500"></i>
                                <p><strong>Stok:</strong> <span id="modal-book-stok"></span></p>
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
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a id="pinjam-link" href="#" class="inline-block w-full text-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                        Pinjam Buku
                    </a>
                    <button id="kembalikan-btn" class="mt-4 w-full text-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors hidden">
                        Kembalikan Buku
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript code for handling UI interactions and AJAX requests
        document.addEventListener('DOMContentLoaded', () => {
            // Declare DOM elements
            const elements = {
                mobileMenuButton: document.getElementById('mobileMenuButton'),
                mobileSidebar: document.getElementById('mobileSidebar'),
                closeSidebar: document.getElementById('closeSidebar'),
                sidebarBackdrop: document.getElementById('sidebarBackdrop'),
                userMenuButton: document.getElementById('userMenuButton'),
                userDropdown: document.getElementById('userDropdown'),
                logoutLinks: document.querySelectorAll('.logout-link'),
                dashboardLink: document.getElementById('dashboardLink'),
                                categoriesLink: document.getElementById('categoriesLink'),
                bookmarksLink: document.getElementById('bookmarksLink'),
                settingsLink: document.getElementById('settingsLink'),
                dashboardContent: document.getElementById('dashboardContent'),
                categoriesContent: document.getElementById('categoriesContent'),
                bookmarksContent: document.getElementById('bookmarksContent'),
                settingsContent: document.getElementById('settingsContent'),
                searchResultsContent: document.getElementById('searchResultsContent'),
                searchInputs: document.querySelectorAll('.searchInput'),
                searchBtns: document.querySelectorAll('.searchBtn'),
                booksModal: document.getElementById('booksModal'),
                closeModalBtn: document.getElementById('closeModalBtn'),
                modalTitle: document.getElementById('modalTitle'),
                modalContent: document.getElementById('modalContent'),
                lihatSemuaDashboardBtn: document.getElementById('lihatSemuaDashboardBtn'),
                lihatSemuaKategoriLinks: document.querySelectorAll('.lihat-semua-link'),
                bookDetailModal: document.getElementById('bookDetailModal'),
                modalContentArea: document.getElementById('modalContentArea'),
                closeDetailModalBtn: document.getElementById('closeDetailModalBtn'),
                modalCover: document.getElementById('modal-book-cover'),
                modalTitleBook: document.getElementById('modal-book-title'),
                modalAuthor: document.getElementById('modal-book-author'),
                modalPublisher: document.getElementById('modal-book-publisher'),
                modalYear: document.getElementById('modal-book-year'),
                modalStok: document.getElementById('modal-book-stok'),
                modalDescription: document.getElementById('modal-book-description'),
                pinjamBtn: document.getElementById('pinjam-link'),
                kembalikanBtn: document.getElementById('kembalikan-btn'),
                profileForm: document.getElementById('profileForm'),
                profileImageInput: document.getElementById('profileImageInput'),
                profileImage: document.getElementById('profileImage'),
                passwordForm: document.getElementById('passwordForm'),
                deleteAccountBtn: document.getElementById('deleteAccountBtn'),
            };

            // Function to show the appropriate tab content
            function showTab(targetHash) {
                if (!['#dashboard', '#categories', '#bookmarks', '#settings'].includes(targetHash)) {
                    targetHash = '#dashboard';
                }

                const contentPanels = {
                    '#dashboard': elements.dashboardContent,
                    '#categories': elements.categoriesContent,
                    '#bookmarks': elements.bookmarksContent,
                    '#settings': elements.settingsContent
                    
                };

                const sidebarLinks = {
                    '#dashboard': elements.dashboardLink,
                    '#categories': elements.categoriesLink,
                    '#bookmarks': elements.bookmarksLink,
                    '#settings': elements.settingsLink
                };

                // Hide all content panels
                Object.values(contentPanels).forEach(panel => panel?.classList.add('hidden'));

                // Deactivate all sidebar links
                Object.values(sidebarLinks).forEach(link => {
                    link?.classList.remove('bg-indigo-900', 'text-white');
                    link?.classList.add('text-indigo-200', 'hover:text-white');
                });

                // Show the selected panel and activate the corresponding link
                contentPanels[targetHash]?.classList.remove('hidden');
                sidebarLinks[targetHash]?.classList.add('bg-indigo-900', 'text-white');
                sidebarLinks[targetHash]?.classList.remove('text-indigo-200', 'hover:text-white');

                // Reload bookmarks content if the bookmarks tab is opened
                if (targetHash === '#bookmarks') {
                    loadBookmarksContent();
                }
            }

            // Function to open the book detail modal
            function openBookDetailModal(bookData) {
                if (!elements.bookDetailModal || !bookData) return;

                // Fill in the book data into the modal
                elements.modalCover.src = bookData.cover;
                elements.modalTitleBook.textContent = bookData.judul;
                elements.modalAuthor.textContent = bookData.penulis;
                elements.modalPublisher.textContent = bookData.penerbit;
                elements.modalYear.textContent = bookData.tahun;
                elements.modalStok.textContent = bookData.stok;
                elements.modalDescription.textContent = bookData.deskripsi || 'Deskripsi tidak tersedia.';

                // Show the correct action button
                if (bookData.aksi === 'kembalikan') {
                    elements.pinjamBtn.classList.add('hidden');
                    elements.kembalikanBtn.classList.remove('hidden');
                    elements.kembalikanBtn.dataset.id = bookData.id;
                    elements.kembalikanBtn.dataset.judul = bookData.judul;
                } else {
                    elements.kembalikanBtn.classList.add('hidden');
                    elements.pinjamBtn.classList.remove('hidden');
                    elements.pinjamBtn.dataset.id = bookData.id;
                }

                // Show the modal with animation
                elements.bookDetailModal.classList.remove('hidden');
                setTimeout(() => {
                    elements.modalContentArea.classList.remove('scale-95');
                }, 50);
            }

            // Function to close the book detail modal
            function closeBookDetailModal() {
                if (!elements.bookDetailModal) return;

                elements.modalContentArea.classList.add('scale-95');
                setTimeout(() => {
                    elements.bookDetailModal.classList.add('hidden');
                }, 200);
            }

            // Function to load bookmarks content
            function loadBookmarksContent() {
                const container = elements.bookmarksContent.querySelector('.divide-y');
                if (!container) return;

                container.innerHTML = '<p class="p-6 text-center text-gray-500">Memuat bookmark...</p>';
                fetch('ambil_bookmarks.php')
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal memuat data dari server.');
                        return response.text();
                    })
                    .then(html => {
                        container.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error memuat bookmark:', error);
                        container.innerHTML = '<p class="p-6 text-center text-red-500">Gagal memuat bookmark.</p>';
                    });
            }

            // Function to perform search
           function performSearch() {
    let query = '';

    // Membaca dari semua input pencarian
    elements.searchInputs.forEach(input => {
        if (input.value.trim() !== '') {
            query = input.value.trim();
        }
    });

    // Sembunyikan SEMUA panel konten utama
    elements.dashboardContent?.classList.add('hidden');
    elements.categoriesContent?.classList.add('hidden');
    elements.bookmarksContent?.classList.add('hidden');
    elements.settingsContent?.classList.add('hidden');

    if (!query) {
        elements.searchResultsContent?.classList.add('hidden');
        // Jika query kosong, kembali ke tampilan dashboard
        showTab('#dashboard');
        return;
    }

    // Tampilkan panel hasil pencarian dan muat data
    elements.searchResultsContent?.classList.remove('hidden');
    elements.searchResultsContent.innerHTML = '<p class="text-center text-gray-500 py-10">Mencari...</p>';

    fetch(`cari_buku.php?q=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.text();
        })
        .then(html => {
            elements.searchResultsContent.innerHTML = html;
        })
        .catch(error => {
            console.error('Error pencarian:', error);
            elements.searchResultsContent.innerHTML = '<p class="text-red-500 text-center py-10">Gagal melakukan pencarian.</p>';
        });

    // Opsional: nonaktifkan semua link sidebar aktif saat menampilkan hasil pencarian
    Object.values({
        dashboard: elements.dashboardLink,
        categories: elements.categoriesLink,
        bookmarks: elements.bookmarksLink,
        settings: elements.settingsLink
    }).forEach(link => {
        link?.classList.remove('bg-indigo-900', 'text-white');
        link?.classList.add('text-indigo-200', 'hover:text-white');
    });
}
            // Initialize the page
            showTab(window.location.hash);

            // Handle hash change for navigation
            window.addEventListener('hashchange', () => {
                showTab(window.location.hash);
            });

            // Global click listener
            document.addEventListener('click', (event) => {
                const target = event.target;
                const targetClosest = (selector) => target.closest(selector);

                // Open book detail modal
                const detailLink = targetClosest('.detail-link');
                if (detailLink) {
                    event.preventDefault();
                    const bookData = {
                        id: detailLink.dataset.id,
                        judul: detailLink.dataset.judul,
                        penulis: detailLink.dataset.penulis,
                        penerbit: detailLink.dataset.penerbit,
                        tahun: detailLink.dataset.tahun,
                        cover: detailLink.dataset.cover,
                        stok: detailLink.dataset.stok,
                        deskripsi: detailLink.dataset.deskripsi,
                        aksi: detailLink.dataset.aksi // Important for context
                    };
                    openBookDetailModal(bookData);
                    return;
                }

                // Borrow book action
                if (targetClosest('#pinjam-link')) {
                    event.preventDefault();
                    const bookId = elements.pinjamBtn.dataset.id;
                    Swal.fire({
                        title: 'Konfirmasi Peminjaman',
                        text: 'Buku ini akan ditambahkan ke daftar pinjaman Anda. Silakan ambil di perpustakaan.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Pinjam!',
                        cancelButtonText: 'Batal'
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch('api_pinjam_buku.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id_buku=${bookId}`
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Gagal!', data.message, 'error');
                                }
                            }).catch(error => Swal.fire('Error!', 'Tidak dapat terhubung ke server.', 'error'));
                        }
                    });
                    return;
                }

                // Return book action
                if (targetClosest('#kembalikan-btn')) {
                    event.preventDefault();
                    const bookId = elements.kembalikanBtn.dataset.id;
                    const bookTitle = elements.kembalikanBtn.dataset.judul;
                    Swal.fire({
                        title: 'Konfirmasi Pengembalian',
                        text: `Anda yakin ingin mengembalikan buku "${bookTitle}"?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Kembalikan!',
                        cancelButtonText: 'Batal'
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch('api_kembalikan_buku.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id_buku=${bookId}`
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Gagal!', data.message, 'error');
                                }
                            }).catch(error => Swal.fire('Error!', 'Tidak bisa menghubungi server.', 'error'));
                        }
                    });
                    return;
                }

                // Remove bookmark action
                const removeBookmarkBtn = targetClosest('.remove-bookmark-btn');
                if (removeBookmarkBtn) {
                    event.preventDefault();
                    const bookId = removeBookmarkBtn.dataset.id;
                    const bookmarkItem = removeBookmarkBtn.closest('.bookmark-item');
                    Swal.fire({
                        title: 'Hapus Bookmark?',
                        text: "Buku ini akan dihapus dari daftar bookmark Anda.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('handle_bookmark.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id_buku=${bookId}&action=remove`
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire('Dihapus!', 'Bookmark telah dihapus.', 'success');
                                    bookmarkItem.style.transition = 'opacity 0.5s ease';
                                    bookmarkItem.style.opacity = '0';
                                    setTimeout(() => {
                                        bookmarkItem.remove();
                                        if (document.querySelectorAll('.bookmark-item').length === 0) {
                                            loadBookmarksContent(); // Reload to show empty message
                                        }
                                    }, 500);
                                } else {
                                    Swal.fire('Gagal', 'Gagal menghapus bookmark.', 'error');
                                }
                            }).catch(error => Swal.fire('Error', 'Tidak bisa terhubung ke server.', 'error'));
                        }
                    });
                    return;
                }

                // Sidebar and dropdown navigation
                if (targetClosest('#mobileMenuButton')) elements.mobileSidebar?.classList.remove('hidden');
                if (targetClosest('#closeSidebar') || targetClosest('#sidebarBackdrop')) elements.mobileSidebar?.classList.add('hidden');
                if (targetClosest('#userMenuButton')) elements.userDropdown?.classList.toggle('hidden');

                // Hide dropdown if clicked outside
                if (!targetClosest('#userMenuButton') && !targetClosest('#userDropdown')) {
                    elements.userDropdown?.classList.add('hidden');
                }
            });

            // Listener for main sidebar navigation links
            ['dashboardLink', 'categoriesLink', 'bookmarksLink', 'settingsLink'].forEach(linkKey => {
                elements[linkKey]?.addEventListener('click', (event) => {
                    event.preventDefault();
                    const targetHash = '#' + linkKey.replace('Link', '');
                    window.location.hash = targetHash;
                });
            });

            // Listener for logout links
            elements.logoutLinks.forEach(link => {
                link.addEventListener('click', function (event) {
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
                        if (result.isConfirmed) {
                            window.location.href = this.href;
                        }
                    });
                });
            });

            // Listener for delete account button
            elements.deleteAccountBtn?.addEventListener('click', function(event) {
                event.preventDefault();
                Swal.fire({
                    title: 'Anda yakin ingin menghapus akun?',
                    text: "Tindakan ini tidak dapat diurungkan!",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus akun saya!',
                    cancelButtonText: 'Jangan Hapus'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Replace with fetch to account deletion API if available
                        Swal.fire('Dihapus!', 'Akun Anda telah dihapus (simulasi).', 'success');
                    }
                });
            });

            // Listener for search
            elements.searchBtns.forEach(btn => {
                btn.addEventListener('click', performSearch);
            });
            elements.searchInputs.forEach(input => {
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') performSearch();
                });
            });

            // Listener for modal close actions
            elements.closeDetailModalBtn?.addEventListener('click', closeBookDetailModal);
            elements.bookDetailModal?.addEventListener('click', (e) => {
                if (e.target === elements.bookDetailModal) closeBookDetailModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !elements.bookDetailModal?.classList.contains('hidden')) {
                    closeBookDetailModal();
                }
            });

            // Listener for category modal
            elements.lihatSemuaKategoriLinks.forEach(link => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    const kategoriId = this.dataset.id;
                    elements.booksModal.classList.remove('hidden');
                    elements.modalContent.innerHTML = '<p class="text-center py-5">Memuat data...</p>';
                    fetch(`api_get_books.php?id=${kategoriId}`)
                        .then(res => {
                            if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
                            return res.json();
                        })
                        .then(data => {
                            elements.modalTitle.innerText = `Daftar Buku: ${data.nama_kategori}`;
                            let html = `
                                <div class="overflow-x-auto">
                                    <table class="min-w-full leading-normal">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Cover</th>
                                                <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Judul</th>
                                                <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Penulis</th>
                                                <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Penerbit</th>
                                                <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Tahun</th>
                                                <th class="px-5 py-3 border-b-2 text-left text-xs font-semibold uppercase">Stok</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                            if (data.buku && data.buku.length > 0) {
                                data.buku.forEach(b => {
                                    const imgSrc = b.cover ? `Upload/covers/${b.cover}` : 'path/to/default-image.png';
                                    html += `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-5 py-3 border-b"><img src="${imgSrc}" class="w-16 h-24 object-cover rounded" alt=""></td>
                                            <td class="px-5 py-3 border-b">${b.judul}</td>
                                            <td class="px-5 py-3 border-b">${b.penulis}</td>
                                            <td class="px-5 py-3 border-b">${b.penerbit}</td>
                                            <td class="px-5 py-3 border-b">${b.tahun_terbit}</td>
                                            <td class="px-5 py-3 border-b">${b.stok}</td>
                                        </tr>`;
                                });
                            } else {
                                html += `
                                    <tr>
                                        <td colspan="6" class="text-center py-10">Belum ada buku dalam kategori ini.</td>
                                    </tr>`;
                            }
                            html += `</tbody></table></div>`;
                                                       elements.modalContent.innerHTML = html;
                        })
                        .catch(err => {
                            console.error('Fetch error:', err);
                            elements.modalContent.innerHTML = `<p class="text-red-500 text-center py-5">Gagal memuat data: ${err.message}</p>`;
                        });
                });
            });

            // Listener for "Lihat Semua" button in Dashboard
            elements.lihatSemuaDashboardBtn?.addEventListener('click', function(event) {
                event.preventDefault();
                elements.booksModal.classList.remove('hidden');
                elements.modalContent.innerHTML = '<p class="text-center py-10 text-gray-500">Memuat semua koleksi...</p>';
                elements.modalTitle.innerText = 'Semua Buku';
                fetch('api_get_semua_buku.php')
                    .then(res => {
                        if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
                        return res.json();
                    })
                    .then(bukuList => {
                        let html = '';
                        if (bukuList && bukuList.length > 0) {
                            html += '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">';
                            bukuList.forEach(buku => {
                                const coverPath = buku.cover ? `Upload/covers/${buku.cover}` : 'path/to/default-cover.png';
                                html += `
                                    <div class="book-card flex flex-col bg-white rounded-lg shadow overflow-hidden transition duration-300">
                                        <div class="relative w-full" style="padding-bottom: 125%;">
                                            <img class="absolute inset-0 w-full h-full object-cover" src="${coverPath}" alt="Cover ${buku.judul}">
                                        </div>
                                        <div class="p-2 flex flex-col flex-grow min-w-0">
                                            <h3 class="font-semibold text-gray-900 whitespace-nowrap overflow-hidden text-ellipsis" title="${buku.judul}">${buku.judul}</h3>
                                            <p class="text-sm text-gray-600 whitespace-nowrap overflow-hidden text-ellipsis" title="${buku.penulis}">${buku.penulis}</p>
                                            <div class="mt-auto pt-2 text-right">
                                                <a href="#" class="detail-link text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                                   data-id="${buku.id}" data-judul="${buku.judul}" data-penulis="${buku.penulis}" data-penerbit="${buku.penerbit}" data-tahun="${buku.tahun_terbit}" data-stok="${buku.stok}" data-deskripsi="${buku.deskripsi}" data-cover="${coverPath}">
                                                    Detail
                                                </a>
                                            </div>
                                        </div>
                                    </div>`;
                            });
                            html += '</div>';
                        } else {
                            html = '<p class="text-center py-10 text-gray-500">Belum ada buku di perpustakaan.</p>';
                        }
                        elements.modalContent.innerHTML = html;
                    })
                    .catch(err => {
                        console.error('Fetch error:', err);
                        elements.modalContent.innerHTML = `<p class="text-red-500 text-center py-5">Gagal memuat data: ${err.message}</p>`;
                    });
            });

            // Listener for closing the books modal
            elements.closeModalBtn?.addEventListener('click', () => {
                elements.booksModal.classList.add('hidden');
            });
            elements.booksModal?.addEventListener('click', (e) => {
                if (e.target === elements.booksModal) {
                    elements.booksModal.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
