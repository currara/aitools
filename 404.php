<?php
// Load configuration and functions
require_once 'includes/config.php';

// Detect language from URL path
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', trim($request_uri, '/'));

// If first part is a valid language code, use it
if (!empty($uri_parts[0]) && array_key_exists($uri_parts[0], $available_languages)) {
    $lang = $uri_parts[0];
    $_SESSION['lang'] = $lang;
} else {
    // Otherwise use the detected language
    $lang = $current_language;
}

// Set HTTP response code
http_response_code(404);

// Include header after language detection
include_once 'includes/header.php';
?>

<!-- 404 Error Page -->
<section class="section section-404">
    <div class="container">
        <div class="error-content">
            <h1>404</h1>
            <h2><?php echo __('page_not_found'); ?></h2>
            <p><?php echo __('page_not_found_message'); ?></p>

            <div class="error-actions">
                <a href="/<?php echo $current_language; ?>/" class="btn btn-primary">
                    <i class="fas fa-home"></i> <?php echo __('back_to_home'); ?>
                </a>
                <a href="/<?php echo $current_language; ?>/categories" class="btn btn-secondary">
                    <i class="fas fa-search"></i> <?php echo __('browse_categories'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
    .section-404 {
        padding: 80px 0;
        text-align: center;
    }

    .error-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .error-content h1 {
        font-size: 8rem;
        color: var(--primary-color);
        margin-bottom: 0;
        line-height: 1;
    }

    .error-content h2 {
        font-size: 2rem;
        margin-bottom: 20px;
        color: var(--gray-800);
    }

    .error-content p {
        font-size: 1.1rem;
        color: var(--gray-600);
        margin-bottom: 30px;
    }

    .error-actions {
        display: flex;
        gap: 20px;
        justify-content: center;
    }

    @media screen and (max-width: 768px) {
        .error-content h1 {
            font-size: 6rem;
        }

        .error-content h2 {
            font-size: 1.5rem;
        }

        .error-actions {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
