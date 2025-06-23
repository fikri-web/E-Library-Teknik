<?php
$menuItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-solid fa-house', 'url' => 'index.php'],
    'kategori' => ['label' => 'Kategori Buku', 'icon' => 'fa-solid fa-layer-group', 'url' => 'kategori.php'],
    'buku' => ['label' => 'Data Buku', 'icon' => 'fa-solid fa-book', 'url' => 'buku.php'],
    'mahasiswa' => ['label' => 'Data Mahasiswa', 'icon' => 'fa-solid fa-users', 'url' => 'mahasiswa.php'],
];
?>

<aside id="sidebar" class="w-64 bg-white dark:bg-gray-800 shadow-lg hidden md:flex flex-col transition-all duration-300">
  <div class="p-6 flex items-center justify-center border-b border-gray-200 dark:border-gray-700">
    <i class="fa-solid fa-book-open text-indigo-600 dark:text-indigo-400 text-2xl"></i>
    <h1 class="ml-3 text-xl font-bold text-gray-900 dark:text-white">E-Library Teknik</h1>
  </div>

  <nav class="flex-1 px-4 py-4 space-y-2">
    <?php foreach ($menuItems as $key => $item): ?>
      <a href="<?= $item['url']; ?>"
         class="flex items-center px-4 py-2 rounded-lg transition 
         <?= ($activePage === $key) 
             ? 'bg-indigo-100 dark:bg-indigo-700 text-indigo-800 dark:text-white font-semibold' 
             : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
         
         <i class="<?= $item['icon']; ?> w-5 mr-3 text-lg"></i>
         <?= $item['label']; ?>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
