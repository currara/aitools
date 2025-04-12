/**
 * Mobile Menu Handler
 * Controls mobile menu functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const body = document.body;

    // Toggle mobile menu
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            menuToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            body.classList.toggle('menu-open');
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (mobileMenu && mobileMenu.classList.contains('active') &&
            !menuToggle.contains(e.target) &&
            !mobileMenu.contains(e.target)) {
            menuToggle.classList.remove('active');
            mobileMenu.classList.remove('active');
            body.classList.remove('menu-open');
        }
    });

    // Close menu when clicking on a link
    const mobileMenuLinks = mobileMenu.querySelectorAll('a');
    mobileMenuLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Don't close for language or settings links
            if (!this.parentElement.classList.contains('language-options') &&
                !this.parentElement.classList.contains('mobile-theme-switcher') &&
                !this.classList.contains('dropdown-toggle')) {
                menuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
                body.classList.remove('menu-open');
            }
        });
    });

    // Add body padding when menu is open
    function updateBodyPadding() {
        if (body.classList.contains('menu-open')) {
            body.style.overflow = 'hidden';
        } else {
            body.style.overflow = '';
        }
    }

    // Listen for menu open/close
    const menuOpenObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                updateBodyPadding();
            }
        });
    });

    // Start observing body element
    menuOpenObserver.observe(body, { attributes: true });

    // Handle resize events
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && mobileMenu.classList.contains('active')) {
            menuToggle.classList.remove('active');
            mobileMenu.classList.remove('active');
            body.classList.remove('menu-open');
        }
    });

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            menuToggle.classList.remove('active');
            mobileMenu.classList.remove('active');
            body.classList.remove('menu-open');
        }
    });
});
