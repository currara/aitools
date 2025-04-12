/**
 * Theme Switcher JavaScript
 * Handles toggling between light and dark themes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get theme elements
    const themeSwitcher = document.getElementById('theme-switcher');
    const mobileSwitcher = document.getElementById('mobile-theme-switcher');
    const body = document.body;

    // Function to toggle theme
    function toggleTheme() {
        const isDarkTheme = body.classList.contains('dark-theme');

        // Toggle body class
        body.classList.toggle('dark-theme');

        // Update toggle appearance
        const toggles = document.querySelectorAll('.theme-switcher-toggle');
        toggles.forEach(toggle => {
            toggle.classList.toggle('dark');

            const icon = toggle.querySelector('.theme-switcher-toggler i');
            if (isDarkTheme) {
                icon.classList.remove('fas', 'fa-moon');
                icon.classList.add('fas', 'fa-sun');
            } else {
                icon.classList.remove('fas', 'fa-sun');
                icon.classList.add('fas', 'fa-moon');
            }
        });

        // Update labels if they exist
        const labels = document.querySelectorAll('.theme-switcher-label');
        labels.forEach(label => {
            if (isDarkTheme) {
                label.textContent = label.dataset.lightText || 'Jasny motyw';
            } else {
                label.textContent = label.dataset.darkText || 'Ciemny motyw';
            }
        });

        // Save preference in cookie (30 days expiration)
        const newValue = !isDarkTheme;
        const expiryDate = new Date();
        expiryDate.setDate(expiryDate.getDate() + 30);
        document.cookie = `dark_theme=${newValue}; expires=${expiryDate.toUTCString()}; path=/`;
    }

    // Add click event to theme switchers
    if (themeSwitcher) {
        themeSwitcher.addEventListener('click', toggleTheme);
    }

    if (mobileSwitcher) {
        mobileSwitcher.addEventListener('click', toggleTheme);
    }

    // Set initial state based on cookies or system preference
    function initializeTheme() {
        // Check for cookie first
        const cookieMatch = document.cookie.match(/dark_theme=(true|false)/);
        if (cookieMatch) {
            const isDarkTheme = cookieMatch[1] === 'true';
            if (isDarkTheme) {
                body.classList.add('dark-theme');
                document.querySelectorAll('.theme-switcher-toggle').forEach(toggle => {
                    toggle.classList.add('dark');
                });
                document.querySelectorAll('.theme-switcher-toggler i').forEach(icon => {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                });
            } else {
                body.classList.remove('dark-theme');
                document.querySelectorAll('.theme-switcher-toggle').forEach(toggle => {
                    toggle.classList.remove('dark');
                });
                document.querySelectorAll('.theme-switcher-toggler i').forEach(icon => {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                });
            }
            return;
        }

        // If no cookie, use system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            body.classList.add('dark-theme');
            document.querySelectorAll('.theme-switcher-toggle').forEach(toggle => {
                toggle.classList.add('dark');
            });
            document.querySelectorAll('.theme-switcher-toggler i').forEach(icon => {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            });

            // Save preference in cookie
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + 30);
            document.cookie = `dark_theme=true; expires=${expiryDate.toUTCString()}; path=/`;
        }
    }

    // Initialize theme settings
    initializeTheme();
});
