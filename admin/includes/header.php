<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin or editor
if (!is_logged_in() || (!is_admin() && !is_editor())) {
    header('Location: ../login.php?redirect=admin');
    exit;
}

// Log admin page access
log_activity($_SESSION['user_id'], 'page_view', 'admin', null, 'Odwiedzono stronę: ' . $_SERVER['REQUEST_URI']);

// Get current admin user
$current_user = get_user($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Panel Administratora</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>/css/tool-category-multi.css">

    <!-- Inline CSS na wypadek problemów z wczytaniem zewnętrznych plików -->
    <style>
    /* Podstawowe style dla admina */
    /* Add your inline styles here if needed */
    </style>

    <!-- JS -->
    <script src="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>/js/admin.js" defer></script>
    <script src="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/'); ?>/js/tool-category-multi.js" defer></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="admin-body">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-inner">
            <div class="admin-logo">
                <a href="index.php">
                    <img src="../images/logo.png" alt="<?php echo SITE_TITLE; ?> Logo">
                    <span>Panel Administracyjny</span>
                </a>
            </div>

            <div class="admin-header-actions">
                <div class="language-switcher">
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <i class="fas fa-globe"></i>
                            <span><?php echo __('language'); ?></span>
                        </button>
                        <div class="dropdown-menu">
                            <?php foreach ($available_languages as $code => $language): ?>
                                <a href="<?php echo get_language_url($code); ?>" class="<?php echo ($current_language === $code) ? 'active' : ''; ?>">
                                    <?php echo $language['native_name']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="admin-user-dropdown">
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <div class="admin-user-info">
                                <?php if (!empty($current_user['avatar'])): ?>
                                    <img src="<?php echo $current_user['avatar']; ?>" alt="<?php echo $current_user['username']; ?>">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <span><?php echo $current_user['username']; ?></span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php">
                                <i class="fas fa-user"></i> Mój profil
                            </a>
                            <a href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Podgląd strony
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Wyloguj się
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Container -->
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <ul>
                    <li>
                        <a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="tools.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'tools.php' || basename($_SERVER['PHP_SELF']) === 'tool-edit.php') ? 'active' : ''; ?>">
                            <i class="fas fa-tools"></i>
                            <span>Narzędzia</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'categories.php' || basename($_SERVER['PHP_SELF']) === 'category-edit.php') ? 'active' : ''; ?>">
                            <i class="fas fa-folder"></i>
                            <span>Kategorie</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="has-submenu <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'settings') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Ustawienia</span>
                            <i class="fas fa-chevron-down submenu-toggle"></i>
                        </a>
                        <ul class="submenu <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'settings') !== false) ? 'open' : ''; ?>">
                            <li>
                                <a href="settings-general.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'settings-general.php') ? 'active' : ''; ?>">
                                    <i class="fas fa-sliders-h"></i>
                                    <span>Ogólne</span>
                                </a>
                            </li>
                            <li>
                                <a href="settings-seo.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'settings-seo.php') ? 'active' : ''; ?>">
                                    <i class="fas fa-search"></i>
                                    <span>SEO</span>
                                </a>
                            </li>
                            <li>
                                <a href="settings-translations.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'settings-translations.php') ? 'active' : ''; ?>">
                                    <i class="fas fa-language"></i>
                                    <span>Tłumaczenia</span>
                                </a>
                            </li>
                            <li>
                                <a href="language-editor.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'language-editor.php') ? 'active' : ''; ?>">
                                    <i class="fas fa-file-code"></i>
                                    <span>Edytor Plików Językowych</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php if (is_admin()): ?>
                    <li>
                        <a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'users.php' || basename($_SERVER['PHP_SELF']) === 'user-edit.php') ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Użytkownicy</span>
                        </a>
                    </li>
                    <li>
                        <a href="activity-log.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'activity-log.php') ? 'active' : ''; ?>">
                            <i class="fas fa-history"></i>
                            <span>Dziennik aktywności</span>
                        </a>
                    </li>
                    <li>
                        <a href="export-import.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'export-import.php') ? 'active' : ''; ?>">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Eksport / Import</span>
                        </a>
                    </li>
                    <li>
                        <a href="fix-descriptions.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'fix-descriptions.php') ? 'active' : ''; ?>">
                            <i class="fas fa-wrench"></i>
                            <span>Napraw opisy HTML</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Breadcrumbs -->
            <div class="admin-breadcrumbs">
                <ul>
                    <li>
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <?php
                    // Dynamically generate breadcrumbs based on current page
                    $current_page = basename($_SERVER['PHP_SELF'], '.php');
                    $title = '';

                    switch ($current_page) {
                        case 'index':
                            $title = 'Dashboard';
                            break;
                        case 'tools':
                            $title = 'Narzędzia';
                            break;
                        case 'tool-edit':
                            echo '<li><a href="tools.php">Narzędzia</a></li>';
                            $title = isset($_GET['id']) ? 'Edycja narzędzia' : 'Nowe narzędzie';
                            break;
                        case 'categories':
                            $title = 'Kategorie';
                            break;
                        case 'category-edit':
                            echo '<li><a href="categories.php">Kategorie</a></li>';
                            $title = isset($_GET['id']) ? 'Edycja kategorii' : 'Nowa kategoria';
                            break;
                        case 'users':
                            $title = 'Użytkownicy';
                            break;
                        case 'user-edit':
                            echo '<li><a href="users.php">Użytkownicy</a></li>';
                            $title = isset($_GET['id']) ? 'Edycja użytkownika' : 'Nowy użytkownik';
                            break;
                        case 'activity-log':
                            $title = 'Dziennik aktywności';
                            break;
                        case 'settings-general':
                            $title = 'Ustawienia ogólne';
                            break;
                        case 'settings-seo':
                            $title = 'Ustawienia SEO';
                            break;
                        case 'settings-translations':
                            $title = 'Tłumaczenia';
                            break;
                        case 'language-editor':
                            $title = 'Edytor Plików Językowych';
                            break;
                        case 'remove-duplicate-categories':
                            echo '<li><a href="categories.php">Kategorie</a></li>';
                            $title = 'Usuwanie Duplikatów';
                            break;
                        case 'profile':
                            $title = 'Mój profil';
                            break;
                        default:
                            $title = ucfirst(str_replace('-', ' ', $current_page));
                    }
                    ?>
                    <li><?php echo $title; ?></li>
                </ul>
            </div>

            <!-- Page Header -->
            <div class="admin-page-header">
                <h1><?php echo $title; ?></h1>
                <div class="admin-page-actions">
                    <?php if ($current_page === 'tools'): ?>
                        <a href="tool-edit.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Dodaj narzędzie
                        </a>
                    <?php elseif ($current_page === 'categories'): ?>
                        <a href="category-edit.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Dodaj kategorię
                        </a>
                    <?php elseif ($current_page === 'users' && is_admin()): ?>
                        <a href="user-edit.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Dodaj użytkownika
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="admin-content"><?php
                // Display alert/flash messages if any
                if (isset($_SESSION['alert'])) {
                    $alert = $_SESSION['alert'];
                    $alert_type = isset($alert['type']) ? $alert['type'] : 'info';
                    $alert_message = isset($alert['message']) ? $alert['message'] : '';

                    echo '<div class="alert alert-' . $alert_type . '">';
                    echo $alert_message;
                    echo '<button type="button" class="alert-close"><i class="fas fa-times"></i></button>';
                    echo '</div>';

                    // Clear the message
                    unset($_SESSION['alert']);
                }
            ?>
