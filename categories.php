<?php
// Load configuration and functions
require_once 'includes/config.php';
require_once 'includes/tools/category-functions.php';

// Debug
debug_log('Starting categories.php');
debug_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
debug_log('Current Language: ' . $current_language);
debug_log('Default Language: ' . $default_language);
debug_log('SESSION lang: ' . (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'not set'));
debug_log('COOKIE lang: ' . (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'not set'));
debug_log('GET lang: ' . (isset($_GET['lang']) ? $_GET['lang'] : 'not set'));

// Załaduj ponownie język, jeśli został przekazany w URL
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    debug_log('Reloading language from URL parameter: ' . $_GET['lang']);
    $current_language = $_GET['lang'];
    $lang = load_language($current_language);

    // Upewnij się, że zmiany języka są zapisane do sesji
    $_SESSION['lang'] = $current_language;
    setcookie('lang', $current_language, time() + (86400 * 30), '/');
}

// Include funkcji narzędziowych kategorii
require_once 'includes/tools/category-functions.php';

// Get all categories
$categories = get_categories();

// Make sure tool counts are up to date
update_category_counts();

// Include header after all data is retrieved
include_once 'includes/header.php';

// Sprawdź czy język zmienił się po załadowaniu nagłówka
debug_log('Language after header: ' . $current_language);
?>

<!-- Categories Section -->
<section class="section section-categories">
    <div class="container">
        <div class="section-header">
            <h1><?php echo __('browse_categories_title'); ?></h1>
            <p><?php echo __('browse_categories_description'); ?></p>
        </div>

        <div class="categories-grid">
            <?php if (empty($categories)): ?>
                <p><?php echo __('no_categories'); ?></p>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-icon">
                            <?php if (!empty($category['icon'])): ?>
                                <img src="/images/icons/<?php echo $category['icon']; ?>" alt="<?php echo $category['name']; ?> icon">
                            <?php else: ?>
                                <i class="fas fa-cube"></i>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo $category['name']; ?></h3>
                        <p><?php echo $category['description']; ?></p>
                        <div class="category-stats">
                            <span class="tool-count"><?php echo $category['count']; ?> <?php echo __('tools'); ?></span>
                        </div>

                        <?php if (!empty($category['subcategories'])): ?>
                            <div class="subcategories">
                                <h4><?php echo __('subcategories'); ?>:</h4>
                                <ul class="subcategory-list">
                                    <?php foreach ($category['subcategories'] as $subcategory): ?>
                                        <li>
                                            <a href="/<?php echo $current_language === $default_language ? '' : $current_language . '/'; ?>category/<?php echo $subcategory['slug']; ?>">
                                                <?php echo $subcategory['name']; ?>
                                                <span class="subcategory-count">(<?php echo get_category_total_count($subcategory['id']); ?>)</span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <a href="/<?php echo $current_language === $default_language ? '' : $current_language . '/'; ?>category/<?php echo $category['slug']; ?>" class="category-link"><?php echo __('explore'); ?> <i class="fas fa-arrow-right"></i></a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Popular Tags Section -->
<section class="section section-tags">
    <div class="container">
        <div class="section-header">
            <h2><?php echo __('popular_topics'); ?></h2>
            <p><?php echo __('explore_trending'); ?></p>
        </div>

        <div class="tags-cloud">
            <a href="/<?php echo $current_language; ?>/search/chatgpt" class="tag-item">ChatGPT</a>
            <a href="/<?php echo $current_language; ?>/search/image+generation" class="tag-item"><?php echo __('tag_image_generation'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/voice+assistant" class="tag-item"><?php echo __('tag_voice_assistant'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/text+to+speech" class="tag-item"><?php echo __('tag_text_to_speech'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/content+writer" class="tag-item"><?php echo __('tag_content_writer'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/code+assistant" class="tag-item"><?php echo __('tag_code_assistant'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/summarization" class="tag-item"><?php echo __('tag_summarization'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/translation" class="tag-item"><?php echo __('tag_translation'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/data+analysis" class="tag-item"><?php echo __('tag_data_analysis'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/video+generation" class="tag-item"><?php echo __('tag_video_generation'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/music" class="tag-item"><?php echo __('tag_music'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/automation" class="tag-item"><?php echo __('tag_automation'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/seo" class="tag-item">SEO</a>
            <a href="/<?php echo $current_language; ?>/search/marketing" class="tag-item"><?php echo __('tag_marketing'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/customer+service" class="tag-item"><?php echo __('tag_customer_service'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/productivity" class="tag-item"><?php echo __('tag_productivity'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/education" class="tag-item"><?php echo __('tag_education'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/healthcare" class="tag-item"><?php echo __('tag_healthcare'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/finance" class="tag-item"><?php echo __('tag_finance'); ?></a>
            <a href="/<?php echo $current_language; ?>/search/business" class="tag-item"><?php echo __('tag_business'); ?></a>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="section cta-section">
    <div class="container">
        <div class="cta-content">
            <h2><?php echo __('cant_find'); ?></h2>
            <p><?php echo __('cant_find_description'); ?></p>
            <div class="cta-buttons">
                <a href="/<?php echo $current_language; ?>/submit" class="btn btn-primary"><?php echo __('submit_tool'); ?></a>
                <a href="/<?php echo $current_language; ?>/contact.php" class="btn btn-secondary"><?php echo __('contact_us'); ?></a>
            </div>
        </div>
    </div>
</section>

<style>
    /* Additional CSS for Categories Page */
    .section-categories {
        padding: 60px 0;
        background-color: var(--white);
    }

    .section-categories .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-categories .section-header p {
        color: var(--gray-600);
        max-width: 700px;
        margin: 0 auto;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .category-card {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        text-align: center;
        transition: var(--transition);
        position: relative;
        border: 1px solid var(--gray-200);
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .category-icon {
        margin-bottom: 20px;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background-color: var(--primary-light);
        color: var(--white);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        margin-left: auto;
        margin-right: auto;
    }

    .category-icon img {
        max-width: 60%;
        max-height: 60%;
    }

    .category-card h3 {
        font-size: 1.4rem;
        margin-bottom: 10px;
        color: var(--gray-900);
    }

    .category-card p {
        color: var(--gray-600);
        font-size: 14px;
        margin-bottom: 20px;
        height: 60px;
        overflow: hidden;
    }

    .category-stats {
        margin-bottom: 20px;
    }

    .tool-count {
        display: inline-block;
        padding: 5px 10px;
        background-color: var(--gray-100);
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-700);
    }

    .subcategory-list {
        list-style: none;
        padding: 0;
        margin: 10px 0;
    }

    .subcategory-list li {
        margin-bottom: 5px;
    }

    .subcategory-count {
        color: var(--gray-500);
        font-size: 12px;
    }

    .category-link {
        display: inline-block;
        padding: 8px 20px;
        background-color: var(--primary-color);
        color: var(--white);
        border-radius: var(--border-radius);
        transition: var(--transition);
        font-weight: 500;
    }

    .category-link:hover {
        background-color: var(--primary-dark);
        color: var(--white);
    }

    .category-link i {
        margin-left: 5px;
    }

    /* Tags Cloud */
    .section-tags {
        background-color: var(--gray-100);
        padding: 60px 0;
    }

    .section-tags .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-tags .section-header p {
        color: var(--gray-600);
    }

    .tags-cloud {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }

    .tag-item {
        display: inline-block;
        padding: 10px 20px;
        background-color: var(--white);
        border-radius: 30px;
        border: 1px solid var(--gray-300);
        color: var(--gray-700);
        font-size: 14px;
        font-weight: 500;
        transition: var(--transition);
    }

    .tag-item:hover {
        background-color: var(--primary-color);
        color: var(--white);
        border-color: var(--primary-color);
    }

    /* CTA Section */
    .cta-section {
        background-color: var(--primary-color);
        color: var(--white);
        text-align: center;
        padding: 80px 0;
    }

    .cta-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .cta-content h2 {
        font-size: 2rem;
        margin-bottom: 20px;
    }

    .cta-content p {
        font-size: 1.1rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    .cta-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .cta-section .btn-primary {
        background-color: var(--white);
        color: var(--primary-color);
    }

    .cta-section .btn-primary:hover {
        background-color: rgba(255, 255, 255, 0.9);
    }

    .cta-section .btn-secondary {
        background-color: transparent;
        border: 2px solid var(--white);
        color: var(--white);
    }

    .cta-section .btn-secondary:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    @media screen and (max-width: 768px) {
        .categories-grid {
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        }

        .cta-buttons {
            flex-direction: column;
            max-width: 200px;
            margin: 0 auto;
        }
    }

    /* Style dla ciemnego motywu - sekcja tagów */
    .dark-theme .section-tags {
        background-color: #222;
    }

    .dark-theme .section-tags .section-header h2,
    .dark-theme .section-tags .section-header p {
        color: #e0e0e0;
    }

    .dark-theme .tags-cloud .tag-item {
        background-color: #2a2a2a;
        color: #e0e0e0;
        border: 1px solid #3a3a3a;
    }

    .dark-theme .tags-cloud .tag-item:hover {
        background-color: #3a3a3a;
        border-color: #4a4a4a;
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
