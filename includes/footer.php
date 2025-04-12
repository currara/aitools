<?php
// Dodajemy funkcję getBasePath jeśli nie została wcześniej zdefiniowana
if (!function_exists('getBasePath')) {
    function getBasePath() {
        $script_name = $_SERVER['SCRIPT_NAME'];
        $script_path = dirname($script_name);

        // Sprawdzanie czy jesteśmy w podfolderze czy w głównym katalogu
        if ($script_path == '/' || $script_path == '\\') {
            return '';
        }

        return $script_path;
    }
}

// Określenie ścieżki bazowej jeśli nie została wcześniej zdefiniowana
if (!isset($base_path)) {
    $base_path = getBasePath();
}
?>
</main>

        <footer class="footer">
            <div class="container">
                <div class="footer-inner">
                    <div class="footer-col footer-about">
                        <div class="footer-logo">
                            <img src="<?php echo $base_path; ?>/images/logo-white.png" alt="AITools Logo">
                        </div>
                        <p><?php echo __('footer_description'); ?></p>
                    </div>

                    <div class="footer-col footer-links">
                        <h3><?php echo __('quick_links'); ?></h3>
                        <ul>
                            <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/' : $base_path . '/' . $current_language; ?>"><?php echo __('home'); ?></a></li>
                            <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/categories' : $base_path . '/' . $current_language . '/categories'; ?>"><?php echo __('categories'); ?></a></li>
                            <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/tools' : $base_path . '/' . $current_language . '/tools'; ?>"><?php echo __('all_tools'); ?></a></li>
                            <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/submit' : $base_path . '/' . $current_language . '/submit'; ?>"><?php echo __('submit_tool_footer'); ?></a></li>
                            <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/about' : $base_path . '/' . $current_language . '/about'; ?>"><?php echo __('about_us'); ?></a></li>
                            <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/contact' : $base_path . '/' . $current_language . '/contact'; ?>"><?php echo __('contact_us'); ?></a></li>
                        </ul>
                    </div>

                    <div class="footer-col footer-categories">
                        <h3><?php echo __('categories'); ?></h3>
                        <ul>
                            <?php
                            $categories = get_categories();
                            $count = 0;
                            foreach ($categories as $category):
                                if ($count < 5):
                            ?>
                            <li><a href="<?php echo $base_path; ?>/<?php echo ($current_language === $default_language) ? '' : $current_language . '/'; ?>category/<?php echo $category['slug']; ?>"><?php echo $category['name']; ?></a></li>
                            <?php
                                endif;
                                $count++;
                            endforeach;
                            ?>
                            <li><a href="<?php echo $base_path; ?>/<?php echo ($current_language === $default_language) ? '' : $current_language . '/'; ?>categories"><?php echo __('view_all_categories'); ?></a></li>
                        </ul>
                    </div>

                    <div class="footer-col footer-newsletter">
                        <h3><?php echo __('newsletter'); ?></h3>
                        <p><?php echo __('footer_newsletter_text'); ?></p>

                        <form action="<?php echo $base_path; ?>/<?php echo ($current_language === $default_language) ? '' : $current_language . '/'; ?>subscribe" method="post" class="newsletter-form">
                            <div class="form-group">
                                <input type="email" name="email" placeholder="<?php echo __('email_placeholder'); ?>" required>
                                <button type="submit" class="btn-subscribe"><?php echo __('subscribe'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="footer-bottom">
                    <div class="copyright">
                        <?php echo sprintf(__('copyright'), date('Y')); ?>
                    </div>

                    <div class="footer-bottom-links">
                        <a href="<?php echo ($current_language === $default_language) ? $base_path . '/privacy-policy' : $base_path . '/' . $current_language . '/privacy-policy'; ?>"><?php echo __('privacy_policy'); ?></a>
                        <a href="<?php echo ($current_language === $default_language) ? $base_path . '/terms-of-service' : $base_path . '/' . $current_language . '/terms-of-service'; ?>"><?php echo __('terms_of_service'); ?></a>
                        <a href="<?php echo ($current_language === $default_language) ? $base_path . '/about' : $base_path . '/' . $current_language . '/about'; ?>"><?php echo __('about_us'); ?></a>
                        <a href="<?php echo ($current_language === $default_language) ? $base_path . '/submit' : $base_path . '/' . $current_language . '/submit'; ?>"><?php echo __('submit_tool_footer'); ?></a>
                    </div>
                </div>
            </div>
        </footer>

        <!-- JavaScript Files -->
        <script src="<?php echo $base_path; ?>/js/script.js"></script>
        <script src="<?php echo $base_path; ?>/js/theme-switcher.js"></script>
        <script src="<?php echo $base_path; ?>/js/mobile-menu.js"></script>

        <!-- Initialize Star Ratings -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize star ratings
                const ratingElements = document.querySelectorAll('.stars');
                ratingElements.forEach(function(element) {
                    const rating = parseFloat(element.getAttribute('data-rating')) || 0;
                    const fullStars = Math.floor(rating);
                    const halfStar = rating % 1 >= 0.5;
                    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

                    let starsHTML = '';

                    // Add full stars
                    for (let i = 0; i < fullStars; i++) {
                        starsHTML += '<i class="fas fa-star"></i>';
                    }

                    // Add half star if needed
                    if (halfStar) {
                        starsHTML += '<i class="fas fa-star-half-alt"></i>';
                    }

                    // Add empty stars
                    for (let i = 0; i < emptyStars; i++) {
                        starsHTML += '<i class="far fa-star"></i>';
                    }

                    element.innerHTML = starsHTML;
                });

                // Mobile menu toggle
                const menuToggle = document.querySelector('.mobile-menu-toggle');
                const mobileMenu = document.querySelector('.mobile-menu');

                if (menuToggle && mobileMenu) {
                    menuToggle.addEventListener('click', function() {
                        menuToggle.classList.toggle('active');
                        mobileMenu.classList.toggle('active');
                        document.body.classList.toggle('menu-open');
                    });
                }

                // Dropdown toggle
                const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

                dropdownToggles.forEach(function(toggle) {
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const dropdown = this.closest('.dropdown');
                        dropdown.classList.toggle('active');

                        // Close other dropdowns
                        dropdownToggles.forEach(function(otherToggle) {
                            if (otherToggle !== toggle) {
                                otherToggle.closest('.dropdown').classList.remove('active');
                            }
                        });
                    });
                });

                // Close dropdowns when clicking outside
                document.addEventListener('click', function(e) {
                    dropdownToggles.forEach(function(toggle) {
                        const dropdown = toggle.closest('.dropdown');
                        if (!dropdown.contains(e.target)) {
                            dropdown.classList.remove('active');
                        }
                    });
                });
            });
        </script>
    </body>
</html>
