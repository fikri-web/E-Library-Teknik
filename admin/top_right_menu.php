<div class="flex items-center space-x-4">
    <!-- Toggle Tema -->
   <!-- Toggle Tema -->
<button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none rounded-full p-2.5 transition">
    <!-- Ikon Bulan (untuk dark mode aktif) -->
    <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
    </svg>

    <!-- Ikon Matahari (untuk light mode aktif) -->
    <svg id="theme-toggle-light-icon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" clip-rule="evenodd"
            d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zM5 11a1 1 0 100-2H4a1 1 0 100 2h1zM10 15a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1z" />
    </svg>
</button>


    <!-- Nama Admin -->
    <span class="text-sm md:text-base text-gray-700 dark:text-white font-medium hidden md:block">
        <?php echo $_SESSION['nama'] ?? 'Admin'; ?>
    </span>

    <!-- Tombol Logout -->
    <a href="../logout.php"
       class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-400 transition">
        Logout
        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7" />
        </svg>
    </a>
</div>
