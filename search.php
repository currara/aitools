<?php
// Load configuration and functions
require_once 'includes/config.php';

// Get the search query
$query = isset($_GET['q']) ? clean_input($_GET['q']) : '';

// Current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Make sure page is at least 1

// Calculate offset for pagination
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Search tools
$tools = [];
$total_tools = 0;

if (!empty($query)) {
    $tools = search_tools($query, ITEMS_PER_PAGE, $offset);

    // Count total tools for pagination
    $sql = "SELECT COUNT(*) as total
            FROM tools
            WHERE name LIKE '%$query%' OR description LIKE '%$query%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total_tools = $row['total'];
}

// Calculate total pages
$total_pages = ceil($total_tools / ITEMS_PER_PAGE);

// Include header after all calculations are done
include_once 'includes/header.php';
?>

<!-- Search Results Section -->
<section class="section section-search">
    <div class="container">
        <div class="section-header">
            <h1><?php echo __('search_results'); ?><?php echo !empty($query) ? " " . sprintf(__('search_results_for'), htmlspecialchars($query)) : ''; ?></h1>
        </div>

        <?php if (empty($query)): ?>
            <div class="search-empty">
                <p><?php echo __('search_empty'); ?></p>
                <form action="/search" method="get" class="hero-search">
                    <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        <?php elseif (empty($tools)): ?>
            <div class="search-empty">
                <p><?php echo sprintf(__('no_results'), htmlspecialchars($query)); ?></p>
                <form action="/search" method="get" class="hero-search">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="<?php echo __('try_another'); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="search-suggestions">
                    <h3><?php echo __('search_suggestions'); ?></h3>
                    <ul>
                        <li><?php echo __('check_spelling'); ?></li>
                        <li><?php echo __('try_general'); ?></li>
                        <li><?php echo __('try_different'); ?></li>
                        <li><?php echo __('browse_by_categories'); ?></li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="search-results-info">
                <p><?php
                   echo $total_tools == 1
                        ? sprintf(__('found_tools'), $total_tools)
                        : sprintf(__('found_tools_plural'), $total_tools);
                   echo " " . sprintf(__('matching'), htmlspecialchars($query));
                ?></p>
                <form action="/search" method="get" class="search-form">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="<?php echo __('refine_search'); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="tools-grid">
                <?php foreach ($tools as $tool): ?>
                    <div class="tool-card" data-category="<?php echo $tool['category_id']; ?>">
                        <?php if ($tool['featured']): ?>
                            <div class="featured-badge"><?php echo __('featured'); ?></div>
                        <?php elseif ($tool['new_launch']): ?>
                            <div class="new-badge"><?php echo __('new'); ?></div>
                        <?php endif; ?>

                        <div class="tool-card-header">
                            <div class="tool-card-logo">
                                <?php if (!empty($tool['logo'])): ?>
                                    <img src="images/<?php echo $tool['logo']; ?>" alt="<?php echo $tool['name']; ?> logo">
                                <?php else: ?>
                                    <img src="images/default-tool-logo.png" alt="<?php echo $tool['name']; ?> logo">
                                <?php endif; ?>
                            </div>
                            <div class="tool-card-title">
                                <h3><a href="/tool/<?php echo $tool['slug']; ?>"><?php echo $tool['name']; ?></a></h3>
                                <div class="category"><?php echo $tool['category_name']; ?></div>
                            </div>
                        </div>

                        <div class="tool-card-body">
                            <div class="tool-card-description">
                                <?php echo substr($tool['description'], 0, 120) . '...'; ?>
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
                                <a href="/tool/<?php echo $tool['slug']; ?>" class="btn btn-secondary"><?php echo __('details'); ?></a>
                                <a href="<?php echo $tool['website_url']; ?>" target="_blank" class="btn btn-primary"><?php echo __('visit'); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <ul>
                        <?php if ($page > 1): ?>
                            <li><a href="/search/<?php echo urlencode($query); ?>/page/<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                        <?php else: ?>
                            <li class="disabled"><span><i class="fas fa-chevron-left"></i></span></li>
                        <?php endif; ?>

                        <?php
                        // Determine the range of pages to show
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);

                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <li class="active"><span><?php echo $i; ?></span></li>
                            <?php else: ?>
                                <li><a href="/search/<?php echo urlencode($query); ?>/page/<?php echo $i; ?>"><?php echo $i; ?></a></li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li><a href="/search/<?php echo urlencode($query); ?>/page/<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                        <?php else: ?>
                            <li class="disabled"><span><i class="fas fa-chevron-right"></i></span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';
?>
