<?php
// Load configuration and functions
require_once 'includes/config.php';
require_once 'includes/tools/tool-functions.php';

// Get category slug from URL
$category_slug = isset($_GET['slug']) ? clean_input($_GET['slug']) : '';

// Get category details
$category = get_category($category_slug);

// Redirect to categories page if category not found
if (!$category) {
    header('Location: /' . $current_language . '/categories');
    exit;
}

// Current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Make sure page is at least 1

// Calculate offset for pagination
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Get tools for this category
$tools = get_tools(ITEMS_PER_PAGE, $offset, $category['id']);

// Count total tools in this category
$total_tools = count_tools($category['id']);

// Calculate total pages
$total_pages = ceil($total_tools / ITEMS_PER_PAGE);

// Include header after all redirections are done
include_once 'includes/header.php';
?>

<!-- Category Header Section -->
<section class="section category-header">
    <div class="container">
        <div class="category-header-content">
            <div class="category-icon">
                <?php if (!empty($category['icon'])): ?>
                    <img src="/images/icons/<?php echo $category['icon']; ?>" alt="<?php echo $category['name']; ?> <?php echo __('icon'); ?>">
                <?php else: ?>
                    <i class="fas fa-cube"></i>
                <?php endif; ?>
            </div>
            <h1><?php echo $category['name']; ?></h1>
            <p><?php echo $category['description']; ?></p>
            <div class="category-stats">
                <div class="stat-item">
                    <span class="count"><?php echo $total_tools; ?></span>
                    <span class="label"><?php echo __('ai_tools'); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tools Section -->
<section class="section section-tools">
    <div class="container">
        <div class="filter-options">
            <div class="filter-group">
                <span><?php echo __('sort_by'); ?></span>
                <select id="sort-filter" class="form-select">
                    <option value="popularity"><?php echo __('popularity'); ?></option>
                    <option value="rating"><?php echo __('rating'); ?></option>
                    <option value="newest"><?php echo __('newest'); ?></option>
                    <option value="oldest"><?php echo __('oldest'); ?></option>
                </select>
            </div>
            <div class="view-options">
                <button class="view-btn grid-view active" title="<?php echo __('grid_view'); ?>">
                    <i class="fas fa-th"></i> <span class="view-text"><?php echo __('grid_view'); ?></span>
                </button>
                <button class="view-btn list-view" title="<?php echo __('list_view'); ?>">
                    <i class="fas fa-list"></i> <span class="view-text"><?php echo __('list_view'); ?></span>
                </button>
            </div>
        </div>

        <?php if (empty($tools)): ?>
            <div class="no-tools">
                <p><?php echo __('no_tools_category'); ?></p>
                <a href="categories.php" class="btn btn-primary"><?php echo __('back_to_categories'); ?></a>
            </div>
        <?php else: ?>
            <div class="tools-grid">
                <?php foreach ($tools as $tool): ?>
                    <?php echo render_tool_card($tool); ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <ul>
                        <?php if ($page > 1): ?>
                            <li><a href="<?php echo get_category_url($category, null, $page - 1); ?>"><i class="fas fa-chevron-left"></i></a></li>
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
                                <li><a href="<?php echo get_category_url($category, null, $i); ?>"><?php echo $i; ?></a></li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li><a href="<?php echo get_category_url($category, null, $page + 1); ?>"><i class="fas fa-chevron-right"></i></a></li>
                        <?php else: ?>
                            <li class="disabled"><span><i class="fas fa-chevron-right"></i></span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Related Categories Section -->
<section class="section section-related">
    <div class="container">
        <div class="section-header">
            <h2><?php echo __('explore_other'); ?></h2>
        </div>

        <div class="related-categories">
            <?php
            // Get all categories for related categories section
            $all_categories = get_categories();

            // Filter out current category
            $related_categories = array_filter($all_categories, function ($cat) use ($category) {
                return $cat['id'] != $category['id'];
            });

            // Get 4 random categories
            shuffle($related_categories);
            $related_categories = array_slice($related_categories, 0, 4);

            foreach ($related_categories as $related):
            ?>
                <div class="related-category-card">
                    <div class="category-icon small">
                        <?php if (!empty($related['icon'])): ?>
                            <img src="/images/icons/<?php echo $related['icon']; ?>" alt="<?php echo $related['name']; ?> <?php echo __('icon'); ?>">
                        <?php else: ?>
                            <i class="fas fa-cube"></i>
                        <?php endif; ?>
                    </div>
                    <div class="related-category-info">
                        <h3><?php echo $related['name']; ?></h3>
                        <span class="tool-count"><?php echo $related['count']; ?> <?php echo __('tools'); ?></span>
                    </div>
                    <a href="<?php echo get_category_url($related); ?>" class="related-category-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
    /* Styles for Category Page */
    .category-hero {
        padding: 60px 0;
        background-color: var(--gray-100);
        text-align: center;
        border-bottom: 1px solid var(--gray-200);
        margin-bottom: 0;
    }

    .category-icon {
        width: 80px;
        height: 80px;
        background-color: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .category-icon img {
        max-width: 50%;
        max-height: 50%;
        filter: brightness(0) invert(1);
    }

    .category-icon i {
        font-size: 32px;
        color: var(--white);
    }

    .category-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 15px;
        color: var(--gray-900);
    }

    .category-description {
        max-width: 800px;
        margin: 0 auto 30px;
        color: var(--gray-600);
        font-size: 1.1rem;
        line-height: 1.6;
    }

    .filter-section {
        padding: 20px 0;
        background-color: var(--white);
        border-bottom: 1px solid var(--gray-200);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
    }

    .filters-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: space-between;
        align-items: center;
    }

    .filter-group {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .filter-label {
        font-weight: 500;
        color: var(--gray-700);
    }

    .filter-select {
        padding: 8px 15px;
        border-radius: var(--border-radius);
        border: 1px solid var(--gray-300);
        background-color: var(--white);
        color: var(--gray-800);
        font-size: 14px;
        min-width: 160px;
    }

    .view-toggle {
        display: flex;
        border: 1px solid var(--gray-300);
        border-radius: var(--border-radius);
        overflow: hidden;
    }

    .view-btn {
        padding: 8px 15px;
        background-color: var(--white);
        border: none;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s;
    }

    .view-btn.active {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .view-btn:first-child {
        border-right: 1px solid var(--gray-300);
    }

    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .tool-count {
        font-size: 14px;
        color: var(--gray-600);
        margin-left: 15px;
    }

    .category-tools-section {
        padding: 40px 0;
        background-color: var(--gray-100);
    }

    .category-tools-section .container {
        padding: 0 20px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin: 40px 0 20px;
    }

    .pagination ul {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pagination li {
        display: flex;
    }

    .pagination li a,
    .pagination li span {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--white);
        color: var(--gray-700);
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid var(--gray-300);
    }

    .pagination li a:hover {
        background-color: var(--gray-200);
        border-color: var(--gray-400);
    }

    .pagination li.active span {
        background-color: var(--primary-color);
        color: var(--white);
        border-color: var(--primary-color);
    }

    .pagination li.disabled span {
        background-color: var(--gray-100);
        color: var(--gray-400);
        cursor: not-allowed;
    }

    .no-results {
        text-align: center;
        padding: 50px 0;
    }

    .no-results p {
        margin-bottom: 20px;
        font-size: 1.2rem;
        color: var(--gray-600);
    }

    /* Ciemny motyw */
    .dark-theme .category-hero {
        background-color: #1f2937;
        border-color: #374151;
    }

    .dark-theme .category-hero h1 {
        color: #f3f4f6;
    }

    .dark-theme .category-description {
        color: #d1d5db;
    }

    .dark-theme .filter-section {
        background-color: #111827;
        border-color: #374151;
    }

    .dark-theme .filter-label {
        color: #d1d5db;
    }

    .dark-theme .filter-select {
        background-color: #1f2937;
        border-color: #374151;
        color: #f3f4f6;
    }

    .dark-theme .view-btn {
        background-color: #1f2937;
        color: #d1d5db;
    }

    .dark-theme .view-btn.active {
        background-color: var(--primary-color);
        color: #f3f4f6;
    }

    .dark-theme .category-tools-section {
        background-color: #111827;
    }

    .dark-theme .pagination li a,
    .dark-theme .pagination li span {
        background-color: #1f2937;
        color: #d1d5db;
        border-color: #374151;
    }

    .dark-theme .pagination li a:hover {
        background-color: #374151;
    }

    .dark-theme .no-results p {
        color: #d1d5db;
    }

    @media screen and (max-width: 768px) {
        .category-hero {
            padding: 40px 0;
        }

        .category-hero h1 {
            font-size: 2rem;
        }

        .filter-section .filters-container {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-group {
            width: 100%;
            justify-content: space-between;
        }

        .filter-select {
            flex-grow: 1;
        }

        .tools-grid {
            gap: 15px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Translation variables for JavaScript
        const translations = {
            popularity: '<?php echo isset($lang['popularity']) ? $lang['popularity'] : 'Popularity'; ?>',
            rating: '<?php echo isset($lang['rating']) ? $lang['rating'] : 'Rating'; ?>',
            newest: '<?php echo isset($lang['newest']) ? $lang['newest'] : 'Newest'; ?>',
            oldest: '<?php echo isset($lang['oldest']) ? $lang['oldest'] : 'Oldest'; ?>',
            grid_view: '<?php echo isset($lang['grid_view']) ? $lang['grid_view'] : 'Grid View'; ?>',
            list_view: '<?php echo isset($lang['list_view']) ? $lang['list_view'] : 'List View'; ?>'
        };

        const gridViewBtn = document.querySelector('.grid-view');
        const listViewBtn = document.querySelector('.list-view');
        const toolsContainer = document.querySelector('.tools-grid');
        const sortFilter = document.getElementById('sort-filter');

        // Update select options with translations
        if (sortFilter) {
            // Preserve selected value if any
            const selectedValue = sortFilter.value;

            // Clear and rebuild options
            sortFilter.innerHTML = '';

            // Add translated options
            const options = [{
                    value: 'popularity',
                    text: translations.popularity
                },
                {
                    value: 'rating',
                    text: translations.rating
                },
                {
                    value: 'newest',
                    text: translations.newest
                },
                {
                    value: 'oldest',
                    text: translations.oldest
                }
            ];

            options.forEach(option => {
                const optElement = document.createElement('option');
                optElement.value = option.value;
                optElement.textContent = option.text;
                if (option.value === selectedValue) {
                    optElement.selected = true;
                }
                sortFilter.appendChild(optElement);
            });
        }

        // Handle view toggle with localStorage
        if (gridViewBtn && listViewBtn && toolsContainer) {
            // Load saved view mode from localStorage
            const savedViewMode = localStorage.getItem('categoryViewMode');

            // Apply saved view mode if exists
            if (savedViewMode === 'list') {
                toolsContainer.classList.remove('tools-grid');
                toolsContainer.classList.add('tools-list');
                listViewBtn.classList.add('active');
                gridViewBtn.classList.remove('active');
            } else {
                // Default is grid view
                toolsContainer.classList.remove('tools-list');
                toolsContainer.classList.add('tools-grid');
                gridViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
            }

            // Set up click handlers
            gridViewBtn.addEventListener('click', function() {
                toolsContainer.classList.remove('tools-list');
                toolsContainer.classList.add('tools-grid');
                gridViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
                localStorage.setItem('categoryViewMode', 'grid');
            });

            listViewBtn.addEventListener('click', function() {
                toolsContainer.classList.remove('tools-grid');
                toolsContainer.classList.add('tools-list');
                listViewBtn.classList.add('active');
                gridViewBtn.classList.remove('active');
                localStorage.setItem('categoryViewMode', 'list');
            });
        }

        // Handle sorting
        if (sortFilter) {
            sortFilter.addEventListener('change', function() {
                const value = this.value;
                const toolCards = Array.from(document.querySelectorAll('.tool-card'));

                toolCards.sort((a, b) => {
                    if (value === 'rating') {
                        const ratingA = parseFloat(a.querySelector('.rating-value').textContent) || 0;
                        const ratingB = parseFloat(b.querySelector('.rating-value').textContent) || 0;
                        return ratingB - ratingA;
                    } else if (value === 'popularity') {
                        const viewsA = parseInt(a.querySelector('.upvotes-count').textContent) || 0;
                        const viewsB = parseInt(b.querySelector('.upvotes-count').textContent) || 0;
                        return viewsB - viewsA;
                    } else if (value === 'newest') {
                        const dateA = new Date(a.dataset.created || 0);
                        const dateB = new Date(b.dataset.created || 0);
                        return dateB - dateA;
                    } else if (value === 'oldest') {
                        const dateA = new Date(a.dataset.created || 0);
                        const dateB = new Date(b.dataset.created || 0);
                        return dateA - dateB;
                    }
                    return 0;
                });

                const parent = toolsContainer;
                toolCards.forEach(card => parent.appendChild(card));
            });
        }
    });
</script>
<?php
// Include footer
include_once 'includes/footer.php';
?>
