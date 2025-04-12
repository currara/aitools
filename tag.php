<?php
// Get tool information first
require_once 'includes/config.php';

// Check if slug is provided
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: index.php');
    exit;
}

$slug = $_GET['slug'];
$tag = get_tag_by_slug($slug);

// If tag not found, redirect to home
if (!$tag) {
    header('Location: index.php');
    exit;
}

// Calculate pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($current_page - 1) * $per_page;

// Get tools for the tag
$tools = get_tools_by_tag($slug, $per_page, $offset);

// Count total tools for pagination
$total_tools = count_tools_by_tag($slug);
$total_pages = ceil($total_tools / $per_page);

// Include header after all redirections are done
include_once 'includes/header.php';
?>

<!-- Tag Tools Section -->
<section class="section tag-tools-section">
    <div class="container">
        <div class="breadcrumbs">
            <ul>
                <li><a href="/<?php echo $current_language; ?>/"><?php echo __('home'); ?></a></li>
                <li><?php echo __('tag'); ?>: <?php echo $tag['name']; ?></li>
            </ul>
        </div>

        <div class="section-header">
            <h1><?php echo __('tools_tagged_with'); ?> #<?php echo $tag['name']; ?></h1>
            <p><?php echo sprintf(__('found_tools_count'), $total_tools); ?></p>
        </div>

        <?php if (!empty($tools)): ?>
        <div class="tools-grid">
            <?php foreach ($tools as $tool): ?>
                <div class="tool-card" data-category="<?php echo $tool['category_id']; ?>">
                    <?php if ($tool['featured']): ?>
                        <div class="featured-badge"><?php echo __('featured'); ?></div>
                    <?php endif; ?>
                    <?php if ($tool['new_launch']): ?>
                        <div class="new-badge"><?php echo __('new'); ?></div>
                    <?php endif; ?>

                    <div class="tool-card-header">
                        <div class="tool-card-logo">
                            <?php if (!empty($tool['logo'])): ?>
                                <img src="/images/<?php echo $tool['logo']; ?>" alt="<?php echo $tool['name']; ?> logo">
                            <?php else: ?>
                                <img src="/images/default-tool-logo.png" alt="<?php echo $tool['name']; ?> logo">
                            <?php endif; ?>
                        </div>
                        <div class="tool-card-title">
                            <h3><a href="/<?php echo $current_language; ?>/tool/<?php echo $tool['slug']; ?>"><?php echo $tool['name']; ?></a></h3>
                            <div class="category">
                                <a href="/<?php echo $current_language; ?>/category/<?php echo $tool['category_slug']; ?>"><?php echo $tool['category_name']; ?></a>
                            </div>
                        </div>
                    </div>

                    <div class="tool-card-body">
                        <div class="tool-card-description">
                            <?php echo substr($tool['description'], 0, 120) . '...'; ?>
                        </div>
                        <div class="tool-card-tags">
                            <?php foreach ($tool['tags'] as $tag_item): ?>
                                <a href="/<?php echo $current_language; ?>/tag/<?php echo $tag_item['slug']; ?>" class="tag">
                                    #<?php echo $tag_item['name']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tool-card-footer">
                        <div class="tool-card-stats">
                            <div class="rating">
                                <div class="stars" data-rating="<?php echo $tool['rating']; ?>"></div>
                                <span><?php echo number_format($tool['rating'], 1); ?></span>
                            </div>
                            <div class="upvotes">
                                <i class="fas fa-thumbs-up"></i>
                                <span><?php echo $tool['upvotes']; ?></span>
                            </div>
                        </div>
                        <div class="tool-card-actions">
                            <a href="/<?php echo $current_language; ?>/tool/<?php echo $tool['slug']; ?>" class="btn btn-secondary"><?php echo __('details'); ?></a>
                            <a href="<?php echo $tool['website_url']; ?>" target="_blank" class="btn btn-primary"><?php echo __('visit'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="/<?php echo $current_language; ?>/tag/<?php echo $slug; ?>?page=<?php echo $current_page - 1; ?>" class="pagination-prev">
                    <i class="fas fa-chevron-left"></i> <?php echo __('previous'); ?>
                </a>
            <?php endif; ?>

            <div class="pagination-numbers">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="/<?php echo $current_language; ?>/tag/<?php echo $slug; ?>?page=<?php echo $i; ?>" class="<?php echo $i === $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if ($current_page < $total_pages): ?>
                <a href="/<?php echo $current_language; ?>/tag/<?php echo $slug; ?>?page=<?php echo $current_page + 1; ?>" class="pagination-next">
                    <?php echo __('next'); ?> <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="no-tools-found">
            <p><?php echo __('no_tools_found_for_tag'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Additional Tag styles -->
<style>
.tag-tools-section .section-header {
    margin-bottom: 30px;
}

.tag-tools-section .section-header h1 {
    font-size: 2rem;
    margin-bottom: 10px;
}

.tag-tools-section .section-header p {
    color: var(--gray-600);
}

.tool-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 15px;
}

.tool-card-tags .tag {
    background-color: var(--gray-200);
    padding: 4px 8px;
    border-radius: 15px;
    font-size: 0.8rem;
    color: var(--gray-700);
}

.tool-card-tags .tag:hover {
    background-color: var(--primary-color);
    color: white;
}

.no-tools-found {
    text-align: center;
    padding: 50px 0;
    color: var(--gray-600);
}
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
