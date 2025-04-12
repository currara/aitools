<?php
// Load configuration and functions
require_once 'includes/config.php';
require_once 'includes/tools/tool-functions.php';

// Debug
debug_log('Starting tools.php');
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

// Include header
include_once 'includes/header.php';

// Sprawdź czy język zmienił się po załadowaniu nagłówka
debug_log('Language after header: ' . $current_language);

// Get all tools (limit 50 per page)
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get filter params
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$pricing_type = isset($_GET['pricing_type']) ? $_GET['pricing_type'] : null;

// Debug
debug_log('Filter parameters: sort=' . $sort . ', pricing_type=' . ($pricing_type ? $pricing_type : 'all'));

// Get total count for pagination - Force debug to see the real count
$total_tools = count_tools(null, $pricing_type);
$total_pages = ceil($total_tools / $limit);
debug_log('Total tools found: ' . $total_tools . ', Pages: ' . $total_pages);

// Debug - log the raw contents of the database directly
$debug_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$debug_result = $debug_conn->query("SELECT COUNT(*) AS tool_count FROM tools");
if ($debug_result) {
    $row = $debug_result->fetch_assoc();
    debug_log('DEBUG - Raw count from database: ' . $row['tool_count'] . ' tools');
}

// Debug all tools query - set limit to very high value to get all tools for debugging
$all_tools_debug = get_tools(1000, 0, null, null, null, 'newest', null);
debug_log('DEBUG - Total tools available in database: ' . count($all_tools_debug) . ' (ignoring filters)');

// IMPORTANT FIX: Pass null for featured and new_launch instead of false
// This ensures we get ALL tools, not just non-featured or non-new ones
$tools = get_tools($limit, $offset, null, null, null, $sort, $pricing_type);
debug_log('Tools retrieved for this page: ' . count($tools));

// Get categories for filter
$categories = get_categories();
?>

<!-- All Tools Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1><?php echo __('all_tools'); ?></h1>
            <p><?php echo sprintf(__('hero_description'), number_format($total_tools)); ?></p>

            <div class="search-box">
                <form action="<?php echo ($current_language === $default_language) ? '/search' : '/' . $current_language . '/search'; ?>" method="get">
                    <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" required>
                    <button type="submit"><i class="fas fa-search"></i><?php echo __('search'); ?></button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="filter-section">
    <div class="container">
        <div class="filter-options">
            <div class="filter-group">
                <label for="sort"><?php echo __('sort_by'); ?></label>
                <select id="sort" class="filter-select">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>><?php echo __('newest'); ?></option>
                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>><?php echo __('highest_rated'); ?></option>
                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>><?php echo __('most_popular'); ?></option>
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>><?php echo __('name'); ?></option>
                </select>
            </div>

            <div class="filter-group">
                <label for="pricing_type"><?php echo __('pricing'); ?></label>
                <select id="pricing_type" class="filter-select">
                    <option value=""><?php echo __('all'); ?></option>
                    <option value="free" <?php echo $pricing_type === 'free' ? 'selected' : ''; ?>><?php echo __('free'); ?></option>
                    <option value="freemium" <?php echo $pricing_type === 'freemium' ? 'selected' : ''; ?>><?php echo __('freemium'); ?></option>
                    <option value="paid" <?php echo $pricing_type === 'paid' ? 'selected' : ''; ?>><?php echo __('paid'); ?></option>
                    <option value="contact" <?php echo $pricing_type === 'contact' ? 'selected' : ''; ?>><?php echo __('contact_for_pricing'); ?></option>
                </select>
            </div>

            <div class="filter-group">
                <div class="view-toggle">
                    <button type="button" class="view-btn grid-view active">
                        <i class="fas fa-th"></i> <?php echo __('grid_view'); ?>
                    </button>
                    <button type="button" class="view-btn list-view">
                        <i class="fas fa-list"></i> <?php echo __('list_view'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- All Tools Section -->
<section class="section tools-section">
    <div class="container">
        <?php if (count($tools) > 0): ?>
            <div class="tools-grid" id="tools-container">
                <?php foreach ($tools as $tool): ?>
                    <?php echo render_tool_card($tool); ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <ul>
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $sort ? '&sort=' . $sort : ''; ?><?php echo $pricing_type ? '&pricing_type=' . $pricing_type : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="disabled">
                                <span><i class="fas fa-chevron-left"></i></span>
                            </li>
                        <?php endif; ?>

                        <?php
                        // Show limited pagination links
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);

                        if ($start_page > 1): ?>
                            <li><a href="?page=1<?php echo $sort ? '&sort=' . $sort : ''; ?><?php echo $pricing_type ? '&pricing_type=' . $pricing_type : ''; ?>">1</a></li>
                            <?php if ($start_page > 2): ?>
                                <li class="disabled"><span>...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                                <?php if ($i === $page): ?>
                                    <span><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $sort ? '&sort=' . $sort : ''; ?><?php echo $pricing_type ? '&pricing_type=' . $pricing_type : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="disabled"><span>...</span></li>
                            <?php endif; ?>
                            <li>
                                <a href="?page=<?php echo $total_pages; ?><?php echo $sort ? '&sort=' . $sort : ''; ?><?php echo $pricing_type ? '&pricing_type=' . $pricing_type : ''; ?>">
                                    <?php echo $total_pages; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $sort ? '&sort=' . $sort : ''; ?><?php echo $pricing_type ? '&pricing_type=' . $pricing_type : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="disabled">
                                <span><i class="fas fa-chevron-right"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <p><?php echo __('no_tools_category'); ?></p>
                <a href="<?php echo ($current_language === $default_language) ? '/categories' : '/' . $current_language . '/categories'; ?>" class="btn btn-primary">
                    <?php echo __('explore_other'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
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
    /* Styles for Tools Page */
    .filter-section {
        padding: 30px 0;
        background-color: var(--gray-100);
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

    /* Poprawiony styl dla list-view */
    .tools-grid.list-view-enabled .tool-card {
        flex-direction: row;
        height: auto;
        margin-bottom: 15px;
    }

    .tools-grid.list-view-enabled .tool-card.has-screenshot .tool-screenshot {
        display: none; /* Ukrywamy główny zrzut ekranu w widoku listy */
    }

    .tools-grid.list-view-enabled .tool-card-inner {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 15px;
        width: 100%;
    }

    .tools-grid.list-view-enabled .tool-info {
        flex-grow: 1;
        margin-bottom: 0;
        margin-right: 15px;
    }

    .tools-grid.list-view-enabled .tool-actions {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
        margin-left: auto;
    }

    /* Style dla miniatur w widoku listy */
    .tools-grid.list-view-enabled .tool-card.has-screenshot .tool-logo {
        display: flex;
        padding: 10px;
        margin-right: 0;
    }

    .tools-grid.list-view-enabled .tool-card.has-screenshot .tool-logo img {
        width: 80px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
    }

    .tools-grid.list-view-enabled .tool-card.has-favicon .tool-logo {
        padding: 15px;
    }

    .tools-section {
        padding: 50px 0;
    }

    .tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
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

    /* CTA Section */
    .cta-section {
        background-color: var(--primary-color);
        color: var(--white);
        text-align: center;
        padding: 80px 0;
        margin-top: 50px;
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
        .cta-buttons {
            flex-direction: column;
            max-width: 200px;
            margin: 0 auto;
        }
    }

    /* Dostosowanie dla ciemnego motywu */
    .dark-theme .cta-section {
        background-color: #1f2937;
    }

    /* Responsywność dla widoku listy */
    @media screen and (max-width: 768px) {
        .tools-grid.list-view-enabled {
            grid-template-columns: 1fr;
        }

        .tools-grid.list-view-enabled .tool-card-inner {
            flex-direction: column;
            align-items: flex-start;
        }

        .tools-grid.list-view-enabled .tool-card.has-screenshot .tool-logo img {
            width: 100%;
            height: auto;
            max-height: 120px;
        }

        .tools-grid.list-view-enabled .tool-info {
            margin-right: 0;
            margin-bottom: 15px;
            width: 100%;
        }

        .tools-grid.list-view-enabled .tool-actions {
            width: 100%;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-300);
        }
    }
</style>

<!-- JavaScript for Filter Handling -->
<script>
    // Function to update filters and redirect with parameters
    function updateFilters() {
        const sort = document.getElementById('sort').value;
        const pricingType = document.getElementById('pricing_type').value;

        // Build URL with parameters
        let url = window.location.pathname + '?';
        if (sort) url += `sort=${sort}&`;
        if (pricingType) url += `pricing_type=${pricingType}&`;

        // Remove trailing '&' if any
        url = url.endsWith('&') ? url.slice(0, -1) : url;

        // Debug
        console.log('Redirecting to: ' + url);

        // Redirect
        window.location.href = url;
    }

    // Function to set view mode (grid or list)
    function setViewMode(mode) {
        const container = document.getElementById('tools-container');
        const gridBtn = document.querySelector('.grid-view');
        const listBtn = document.querySelector('.list-view');

        if (!container || !gridBtn || !listBtn) {
            console.error('Required elements not found for view mode switching');
            return;
        }

        console.log('Setting view mode to: ' + mode);

        if (mode === 'grid') {
            container.classList.remove('list-view-enabled');
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
            localStorage.setItem('view_mode', 'grid');
        } else {
            container.classList.add('list-view-enabled');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
            localStorage.setItem('view_mode', 'list');
        }
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing filters and view mode');

        // Load saved view mode on page load
        const savedMode = localStorage.getItem('view_mode');
        if (savedMode === 'list') {
            setViewMode('list');
        }

        // Initialize event listeners for filters
        const sortSelect = document.getElementById('sort');
        const pricingTypeSelect = document.getElementById('pricing_type');
        const gridBtn = document.querySelector('.grid-view');
        const listBtn = document.querySelector('.list-view');

        if (sortSelect) {
            sortSelect.addEventListener('change', updateFilters);
        }

        if (pricingTypeSelect) {
            pricingTypeSelect.addEventListener('change', updateFilters);
        }

        if (gridBtn) {
            gridBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setViewMode('grid');
            });
        }

        if (listBtn) {
            listBtn.addEventListener('click', function(e) {
                e.preventDefault();
                setViewMode('list');
            });
        }
    });
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
