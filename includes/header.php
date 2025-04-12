<?php
// Include configuration, ale tylko jeśli nie została już załadowana
if (!defined('SITE_TITLE')) {
    require_once 'includes/config.php';
}

// Debug language settings before processing anything
debug_log('Header.php - Current language before processing: ' . $current_language);
debug_log('Header.php - Session language: ' . (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'not set'));
debug_log('Header.php - GET lang: ' . (isset($_GET['lang']) ? $_GET['lang'] : 'not set'));

// Specjalne sprawdzenie parametru lang w URL, aby zapewnić, że zmiana języka działa poprawnie
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages) && $current_language !== $_GET['lang']) {
    debug_log('Header.php - Reloading language from URL parameter: ' . $_GET['lang']);
    $current_language = $_GET['lang'];
    $lang = load_language($current_language);
}

// Pomocnicza funkcja do określania ścieżki bazowej
function getBasePath() {
    $script_name = $_SERVER['SCRIPT_NAME'];
    $script_path = dirname($script_name);

    // Sprawdzanie czy jesteśmy w podfolderze czy w głównym katalogu
    if ($script_path == '/' || $script_path == '\\') {
        return '';
    }

    return $script_path;
}

// Określenie ścieżki bazowej
$base_path = getBasePath();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" dir="<?php echo $text_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">

    <!-- Block search engines -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">

    <!-- hreflang tags for SEO -->
    <?php echo get_hreflang_tags(); ?>

    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>/css/styles.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/css/improved-styles.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/css/dark-theme.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/css/filters.css">
    <link rel="stylesheet" href="/css/components/tool-card.css">

    <!-- RTL stylesheet if needed -->
    <?php if ($text_direction === 'rtl'): ?>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/css/rtl.css">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" href="<?php echo $base_path; ?>/images/favicon.ico">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?php echo isset($_COOKIE['dark_theme']) && $_COOKIE['dark_theme'] === 'true' ? 'dark-theme' : ''; ?>">
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="<?php echo ($current_language === $default_language) ? $base_path . '/' : $base_path . '/' . $current_language . '/'; ?>">
                        <img src="<?php echo $base_path; ?>/images/logo.png" alt="AITools Logo">
                    </a>
                </div>

                <nav class="main-nav">
                    <ul>
                        <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/' : $base_path . '/' . $current_language . '/'; ?>"><?php echo __('home'); ?></a></li>
                        <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/categories' : $base_path . '/' . $current_language . '/categories'; ?>"><?php echo __('categories'); ?></a></li>
                        <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/tools' : $base_path . '/' . $current_language . '/tools'; ?>"><?php echo __('all_tools'); ?></a></li>
                    </ul>
                </nav>

                <div class="header-right">
                    <div class="search-container">
                        <form action="<?php echo ($current_language === $default_language) ? $base_path . '/search' : $base_path . '/' . $current_language . '/search'; ?>" method="get">
                            <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" required>
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>

                    <div class="theme-switcher" id="theme-switcher">
                        <div class="theme-switcher-toggle <?php echo isset($_COOKIE['dark_theme']) && $_COOKIE['dark_theme'] === 'true' ? 'dark' : ''; ?>">
                            <div class="theme-switcher-toggler">
                                <i class="<?php echo isset($_COOKIE['dark_theme']) && $_COOKIE['dark_theme'] === 'true' ? 'fas fa-moon' : 'fas fa-sun'; ?>"></i>
                            </div>
                        </div>
                    </div>

                    <div class="language-switcher">
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <i class="fas fa-globe"></i>
                                <span><?php echo __('language'); ?></span>
                            </button>
                            <div class="dropdown-menu">
                                <?php foreach ($available_languages as $code => $language):
                                    // Get URL for this language (debug for finding issues)
                                    $lang_url = get_language_url($code);
                                    error_log("Language URL for $code: $lang_url (Current: $current_language)");
                                ?>
                                    <a href="<?php echo $lang_url; ?>" class="<?php echo ($current_language === $code) ? 'active' : ''; ?>">
                                        <?php echo $language['native_name']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="user-actions desktop-only">
                        <?php if (is_logged_in()): ?>
                            <div class="dropdown">
                                <button class="dropdown-toggle">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo $_SESSION['username']; ?></span>
                                </button>
                                <div class="dropdown-menu">
                                    <?php if (is_admin()): ?>
                                        <a href="/admin/"><?php echo __('dashboard'); ?></a>
                                    <?php endif; ?>
                                    <a href="<?php echo ($current_language === $default_language) ? $base_path . '/user-profile.php' : $base_path . '/' . $current_language . '/user-profile.php'; ?>"><?php echo __('profile'); ?></a>
                                    <a href="<?php echo ($current_language === $default_language) ? $base_path . '/user-favorites.php' : $base_path . '/' . $current_language . '/user-favorites.php'; ?>"><?php echo __('favorites'); ?></a>
                                    <a href="<?php echo ($current_language === $default_language) ? $base_path . '/logout.php' : $base_path . '/' . $current_language . '/logout.php'; ?>"><?php echo __('logout'); ?></a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo ($current_language === $default_language) ? $base_path . '/login' : $base_path . '/' . $current_language . '/login'; ?>" class="btn btn-login"><?php echo __('login'); ?></a>
                            <a href="<?php echo ($current_language === $default_language) ? $base_path . '/register' : $base_path . '/' . $current_language . '/register'; ?>" class="btn btn-signup"><?php echo __('signup'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mobile-menu-toggle" id="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobile-menu">
        <nav>
            <ul>
                <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/' : $base_path . '/' . $current_language . '/'; ?>"><?php echo __('home'); ?></a></li>
                <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/categories' : $base_path . '/' . $current_language . '/categories'; ?>"><?php echo __('categories'); ?></a></li>
                <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/tools' : $base_path . '/' . $current_language . '/tools'; ?>"><?php echo __('all_tools'); ?></a></li>
                <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/submit' : $base_path . '/' . $current_language . '/submit'; ?>"><?php echo __('submit'); ?></a></li>
                <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/about' : $base_path . '/' . $current_language . '/about'; ?>"><?php echo __('about'); ?></a></li>

                <li class="mobile-theme-switcher">
                    <span><?php echo __('theme'); ?>:</span>
                    <div class="theme-switcher" id="mobile-theme-switcher">
                        <div class="theme-switcher-toggle <?php echo isset($_COOKIE['dark_theme']) && $_COOKIE['dark_theme'] === 'true' ? 'dark' : ''; ?>">
                            <div class="theme-switcher-toggler">
                                <i class="<?php echo isset($_COOKIE['dark_theme']) && $_COOKIE['dark_theme'] === 'true' ? 'fas fa-moon' : 'fas fa-sun'; ?>"></i>
                            </div>
                        </div>
                        <div class="theme-switcher-label">
                            <?php echo isset($_COOKIE['dark_theme']) && $_COOKIE['dark_theme'] === 'true' ? __('dark_mode') : __('light_mode'); ?>
                        </div>
                    </div>
                </li>

                <li class="mobile-language-switcher">
                    <span><?php echo __('language'); ?>:</span>
                    <div class="language-options">
                        <?php foreach ($available_languages as $code => $language): ?>
                            <a href="<?php echo get_language_url($code); ?>" class="<?php echo ($current_language === $code) ? 'active' : ''; ?>">
                                <?php echo $language['native_name']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <?php if (is_logged_in()): ?>
                    <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/user-profile.php' : $base_path . '/' . $current_language . '/user-profile.php'; ?>"><?php echo __('profile'); ?></a></li>
                    <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/user-favorites.php' : $base_path . '/' . $current_language . '/user-favorites.php'; ?>"><?php echo __('favorites'); ?></a></li>
                    <?php if (is_admin()): ?>
                        <li><a href="/admin/"><?php echo __('dashboard'); ?></a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo ($current_language === $default_language) ? $base_path . '/logout.php' : $base_path . '/' . $current_language . '/logout.php'; ?>"><?php echo __('logout'); ?></a></li>
                <?php else: ?>
                    <li class="mobile-auth-buttons">
                        <a href="<?php echo ($current_language === $default_language) ? $base_path . '/login' : $base_path . '/' . $current_language . '/login'; ?>" class="btn btn-login-mobile"><?php echo __('login'); ?></a>
                        <a href="<?php echo ($current_language === $default_language) ? $base_path . '/register' : $base_path . '/' . $current_language . '/register'; ?>" class="btn btn-signup-mobile"><?php echo __('signup'); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <main>
