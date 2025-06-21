<?php
// File: partials/sidebar.php

// Daftar menu
$menuItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1V10a1 1 0 00-1-1H7a1 1 0 00-1 1v10a1 1 0 001 1h2z"></path>', 'url' => 'index.php'],
    'kategori' => ['label' => 'Kategori Buku', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a2 2 0 012 2v5a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm0 14h.01M7 17h5a2 2 0 012 2v5a2 2 0 01-2 2H7a2 2 0 01-2-2v-5a2 2 0 012-2z"></path>', 'url' => 'kategori.php'],
    'buku' => ['label' => 'Data Buku', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m-9-5.747h18"></path><path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"></path>', 'url' => 'buku.php'],
    'mahasiswa' => ['label' => 'Data Mahasiswa', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.134-1.276-.38-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.134-1.276.38-1.857m0 0a5.002 5.002 0 019.24 0M12 15v5M12 15a5 5 0 01-5-5V7a5 5 0 0110 0v3a5 5 0 01-5 5z"></path>', 'url' => 'mahasiswa.php'],
];
?>
<aside id="sidebar" class="w-64 bg-white dark:bg-gray-800 shadow-md hidden md:flex flex-col transition-all duration-300">
    <div class="p-6 flex items-center justify-center">
        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m-9-5.747h18"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 17.25h16.5"></path></svg>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white ml-2">E-Library Teknik</h1>
    </div>
    <nav class="flex-1 px-4 py-4 space-y-2">
        <?php foreach ($menuItems as $key => $item): ?>
            <a href="<?php echo $item['url']; ?>" 
               class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 
               <?php 
                    // Menambahkan kelas 'active' jika halaman ini sedang dibuka
                    if ($activePage === $key) {
                        echo 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold';
                    } else {
                        echo 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700';
                    }
               ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><?php echo $item['icon']; ?></svg>
                <?php echo $item['label']; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>