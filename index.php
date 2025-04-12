<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

// Set error handling to prevent white screens
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Log error for administrator
    error_log("Error $errno: $errstr in $errfile on line $errline");

    // For fatal errors, display a friendly message
    if ($errno == E_ERROR || $errno == E_PARSE || $errno == E_CORE_ERROR ||
        $errno == E_COMPILE_ERROR || $errno == E_USER_ERROR) {
        echo '<div style="padding: 20px; margin: 20px; border: 1px solid #ddd; background: #f9f9f9;">';
        echo '<h2>Oops! Something went wrong.</h2>';
        echo '<p>We\'re working on fixing this issue. Please try again later.</p>';
        echo '</div>';
        // Don't execute PHP internal error handler
        return true;
    }

    // For non-fatal errors, let PHP handle it
    return false;
});

// Load configuration and functions
require_once 'includes/config.php';
require_once 'includes/tools/tool-functions.php';

// Get featured and new tools
$featured_tools = get_tools(10, 0, null, true, null, 'newest');
$new_tools = get_tools(8, 0, null, null, true, 'newest');

// Include header after all data is retrieved
include_once 'includes/header.php';

// Debug
debug_log('Starting index.php');
debug_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
debug_log('Current Language: ' . $current_language);
debug_log('Default Language: ' . $default_language);
debug_log('SESSION lang: ' . (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'not set'));
debug_log('COOKIE lang: ' . (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'not set'));
debug_log('GET lang: ' . (isset($_GET['lang']) ? $_GET['lang'] : 'not set'));

// Get count of all tools for hero section
$total_tools = count_tools();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1><?php echo __('hero_title'); ?></h1>
            <p><?php echo sprintf(__('hero_description'), number_format($total_tools)); ?></p>

            <div class="search-box">
                <form action="<?php echo ($current_language === $default_language) ? '/search' : '/' . $current_language . '/search'; ?>" method="get">
                    <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i><?php echo __('search'); ?></button>
                </form>
            </div>

            <div class="hero-stats">
                <div class="stat-item">
                    <span class="count"><?php echo number_format($total_tools); ?>+</span>
                    <span class="label"><?php echo __('ai_tools'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="count"><?php echo count($available_languages); ?></span>
                    <span class="label"><?php echo __('ai_categories'); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Tools Section -->
<?php if (count($featured_tools) > 0): ?>
<section class="section section-featured-tools">
    <div class="container">
        <div class="section-header">
            <h2><?php echo __('featured_tools'); ?></h2>
            <p><?php echo __('featured_tools_description'); ?></p>
        </div>

        <div class="tools-grid">
            <?php foreach ($featured_tools as $tool): ?>
                <?php echo render_tool_card($tool); ?>
            <?php endforeach; ?>
        </div>

        <div class="view-all">
            <a href="<?php echo ($current_language === $default_language) ? '/tools' : '/' . $current_language . '/tools'; ?>" class="btn btn-secondary"><?php echo __('view_all_tools'); ?></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- New Tools Section -->
<?php if (count($new_tools) > 0): ?>
<section class="section section-new-tools">
    <div class="container">
        <div class="section-header">
            <h2><?php echo __('new_launches'); ?></h2>
            <p><?php echo __('new_launches_description'); ?></p>
        </div>

        <div class="tools-grid">
            <?php foreach ($new_tools as $tool): ?>
                <?php echo render_tool_card($tool); ?>
            <?php endforeach; ?>
        </div>

        <div class="view-all">
            <a href="<?php echo ($current_language === $default_language) ? '/tools?sort=newest' : '/' . $current_language . '/tools?sort=newest'; ?>" class="btn btn-secondary"><?php echo __('view_all_new_tools'); ?></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Newsletter Section -->
<section class="section newsletter-section">
    <div class="container">
        <div class="newsletter">
            <div class="newsletter-content">
                <h2><?php echo __('newsletter_title'); ?></h2>
                <p><?php echo __('newsletter_description'); ?></p>
                <form>
                    <div class="form-group">
                        <input type="email" placeholder="<?php echo __('newsletter_placeholder'); ?>" required>
                        <button type="submit" class="btn btn-primary"><?php echo __('subscribe'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';
?>
