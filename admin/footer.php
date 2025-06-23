<?php
// File: partials/footer.php
?>
       </div> <!-- Penutup div utama layout -->

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Toggle Sidebar untuk mobile
            const menuButton = document.getElementById("menu-button");
            const sidebar = document.getElementById("sidebar");

            if (menuButton && sidebar) {
                menuButton.addEventListener("click", () => {
                    sidebar.classList.toggle("hidden");
                });
            }

            // Toggle Tema Siang/Malam
            const themeToggleBtn = document.getElementById("theme-toggle");
            const darkIcon = document.getElementById("theme-toggle-dark-icon");
            const lightIcon = document.getElementById("theme-toggle-light-icon");

            function updateThemeIcon() {
                const isDark = localStorage.getItem("color-theme") === "dark" ||
                    (!("color-theme" in localStorage) &&
                        window.matchMedia("(prefers-color-scheme: dark)").matches);

                if (isDark) {
                    document.documentElement.classList.add("dark");
                    lightIcon?.classList.remove("hidden");
                    darkIcon?.classList.add("hidden");
                } else {
                    document.documentElement.classList.remove("dark");
                    lightIcon?.classList.add("hidden");
                    darkIcon?.classList.remove("hidden");
                }
            }

            updateThemeIcon();

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener("click", () => {
                    document.documentElement.classList.toggle("dark");
                    const isDark = document.documentElement.classList.contains("dark");
                    localStorage.setItem("color-theme", isDark ? "dark" : "light");
                    updateThemeIcon();
                });
            }
        });
    </script>
</body>
</html>
