<?php
// Get tool information first
require_once 'includes/config.php';

// Check if slug is provided
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: /' . $current_language);
    exit;
}

$slug = $_GET['slug'];
$tool = get_tool($slug);

// If tool not found, redirect to home
if (!$tool) {
    header('Location: /' . $current_language);
    exit;
}

// Increment views count
$conn->query("UPDATE tools SET views = views + 1 WHERE id = " . $tool['id']);

// Get related tools (same category)
$related_tools = get_tools(3, 0, $tool['category_id']);
// Remove current tool from related tools
$related_tools = array_filter($related_tools, function($related_tool) use ($tool) {
    return $related_tool['id'] != $tool['id'];
});

// Include header after all redirections are done
include_once 'includes/header.php';

echo '<script>
console.log("Tool data:", ' . json_encode($tool) . ');
console.log("Related tools:", ' . json_encode($related_tools) . ');
</script>';
?>

<!-- Tool Detail Section -->
<section class="section tool-detail-section">
    <div class="container">
        <div class="tool-hero">
            <div class="container">
                <div class="breadcrumbs">
                    <div class="breadcrumb-item"><a href="/<?php echo $current_language !== $default_language ? $current_language : ''; ?>"><?php echo __('home'); ?></a></div>
                    <div class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></div>
                    <div class="breadcrumb-item"><a href="/<?php echo $current_language !== $default_language ? $current_language . '/' : ''; ?>category/<?php echo $tool['category_slug']; ?>"><?php echo $tool['category_name']; ?></a></div>
                    <div class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></div>
                    <div class="breadcrumb-item active"><?php echo $tool['name']; ?></div>
                </div>

                <div class="tool-detail-header">
                    <div class="tool-detail-logo">
                        <?php
                        // Wybierz właściwy obrazek w zależności od typu obrazu
                        $image_to_display = !empty($tool['logo']) ? $tool['logo'] : 'default-tool-logo.png';

                        // Jeśli typ obrazu to 'screenshot', użyj zrzutu ekranu (jeśli istnieje)
                        if (isset($tool['image_type']) && $tool['image_type'] === 'screenshot' && !empty($tool['screenshot'])) {
                            $image_to_display = $tool['screenshot'];
                        }

                        echo optimized_image('/images/' . $image_to_display, htmlspecialchars($tool['name']) . ' logo', [
                            'class' => 'tool-detail-logo-img',
                            'width' => '150',
                            'height' => '150'
                        ]);
                        ?>
                    </div>
                    <div class="tool-detail-info">
                        <h1><?php echo $tool['name']; ?></h1>
                        <div class="tool-meta">
                            <div class="tool-category">
                                <a href="/<?php echo $current_language; ?>/category/<?php echo $tool['category_slug'] ?? ''; ?>" class="category-tag">
                                    <?php echo $tool['category_name'] ?? ''; ?>
                                </a>
                            </div>
                            <div class="tool-tags">
                                <?php
                                $tags = get_tool_tags($tool['id']);
                                foreach ($tags as $tag):
                                ?>
                                    <a href="/<?php echo $current_language; ?>/tag/<?php echo $tag['slug']; ?>" class="tag">
                                        #<?php echo $tag['name']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="tool-stats">
                                <div class="rating">
                                    <div class="stars" data-rating="<?php echo $tool['rating']; ?>"></div>
                                    <span><?php echo number_format($tool['rating'], 1); ?></span>
                                </div>
                                <div class="upvotes">
                                    <i class="fas fa-thumbs-up"></i>
                                    <span><?php echo $tool['upvotes']; ?></span>
                                </div>
                                <div class="views">
                                    <i class="fas fa-eye"></i>
                                    <span><?php echo $tool['views']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="tool-action-buttons">
                            <a href="<?php echo $tool['website_url']; ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> <?php echo __('visit_website'); ?>
                            </a>
                            <button class="btn btn-secondary upvote-button" data-id="<?php echo $tool['id']; ?>">
                                <i class="fas fa-thumbs-up"></i> <span class="label"><?php echo __('upvote'); ?></span> <span class="count"><?php echo $tool['upvotes']; ?></span>
                            </button>
                            <button class="btn btn-secondary favorite-button" data-id="<?php echo $tool['id']; ?>">
                                <i class="far fa-heart"></i> <span class="label"><?php echo __('add_to_favorites'); ?></span>
                            </button>
                            <button class="btn btn-secondary share-btn">
                                <i class="fas fa-share-alt"></i> <?php echo __('share'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="breadcrumbs">
            <ul>
                <li><a href="/<?php echo $current_language; ?>"><?php echo __('home'); ?></a></li>
                <li><a href="/<?php echo $current_language; ?>/category/<?php echo $tool['category_slug'] ?? ''; ?>">
                    <?php echo $tool['category_name'] ?? __('category'); ?>
                </a></li>
                <li><?php echo $tool['name']; ?></li>
            </ul>
        </div>

        <div class="tool-detail-content">
            <div class="tool-detail-main">
                <div class="tool-detail-section">
                    <h2><?php echo __('description'); ?></h2>
                    <div class="tool-description">
                        <p><?php echo nl2br($tool['description']); ?></p>
                    </div>
                </div>

                <div class="tool-detail-section">
                    <h2><?php echo __('key_features'); ?></h2>
                    <div class="tool-features">
                        <ul>
                            <?php
                            // Generate some placeholder features if none are in the database
                            $features = isset($tool['features']) ? explode(',', $tool['features']) : [
                                __('feature_placeholder_1'),
                                __('feature_placeholder_2'),
                                __('feature_placeholder_3')
                            ];

                            foreach ($features as $feature):
                            ?>
                                <li><i class="fas fa-check"></i> <?php echo trim($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <?php if (!empty($tool['pricing_type'])): ?>
                <div class="tool-detail-section">
                    <h2><?php echo __('pricing'); ?></h2>
                    <div class="tool-pricing">
                        <?php
                        switch($tool['pricing_type']) {
                            case 'free':
                                echo '<div class="pricing-badge free">' . __('free') . '</div>';
                                break;
                            case 'freemium':
                                echo '<div class="pricing-badge freemium">' . __('freemium') . '</div>';
                                break;
                            case 'paid':
                                echo '<div class="pricing-badge paid">' . __('paid') . '</div>';
                                break;
                            case 'contact':
                                echo '<div class="pricing-badge contact">' . __('contact_for_pricing') . '</div>';
                                break;
                            default:
                                echo '<div class="pricing-badge">' . __('unknown') . '</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="tool-detail-section">
                    <h2><?php echo __('reviews_and_ratings'); ?></h2>
                    <div class="tool-rating-reviews">
                        <div class="rating-summary">
                            <div class="average-rating">
                                <div class="rating-number"><?php echo number_format($tool['rating'], 1); ?></div>
                                <div class="rating-stars">
                                    <div class="stars" data-rating="<?php echo $tool['rating']; ?>"></div>
                                    <div class="rating-count">
                                        <span id="rating-count-display">0</span> <?php echo __('ratings'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="your-rating">
                                <p><?php echo __('rate_this_tool'); ?></p>
                                <div class="rating-input">
                                    <div class="rating-stars-input">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <span class="rating-message" id="rating-message"></span>
                                </div>
                            </div>
                        </div>

                        <div class="reviews-section">
                            <h3><?php echo __('user_reviews'); ?></h3>
                            <div class="reviews-container">
                                <div class="reviews-list" id="reviews-list">
                                    <!-- Reviews will be loaded here -->
                                </div>
                                <div class="loading-reviews">
                                    <i class="fas fa-spinner fa-spin"></i> <?php echo __('loading_reviews'); ?>
                                </div>
                                <div class="no-reviews hidden">
                                    <p><?php echo __('no_reviews_yet'); ?></p>
                                </div>
                            </div>

                            <div class="add-review">
                                <h4><?php echo __('write_review'); ?></h4>
                                <?php if (is_logged_in()): ?>
                                <form id="review-form" data-tool-id="<?php echo $tool['id']; ?>">
                                    <div class="form-group">
                                        <label for="review-title"><?php echo __('review_title'); ?></label>
                                        <input type="text" id="review-title" name="title" placeholder="<?php echo __('review_title_placeholder'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="review-content"><?php echo __('review_content'); ?></label>
                                        <textarea id="review-content" name="content" rows="4" placeholder="<?php echo __('review_content_placeholder'); ?>" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><?php echo __('submit_review'); ?></button>
                                </form>
                                <?php else: ?>
                                <div class="login-to-review">
                                    <p><?php echo __('login_to_review'); ?></p>
                                    <a href="/<?php echo $current_language; ?>/login" class="btn btn-secondary"><?php echo __('login'); ?></a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tool-detail-section">
                    <h2><?php echo __('share_this_tool'); ?></h2>
                    <div class="tool-share">
                        <div class="social-share-buttons">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/' . $current_language . '/tool/' . $tool['slug']); ?>&text=<?php echo urlencode($tool['name'] . ' - ' . substr($tool['description'], 0, 100) . '...'); ?>" target="_blank" class="social-share twitter">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/' . $current_language . '/tool/' . $tool['slug']); ?>" target="_blank" class="social-share facebook">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(SITE_URL . '/' . $current_language . '/tool/' . $tool['slug']); ?>&title=<?php echo urlencode($tool['name']); ?>&summary=<?php echo urlencode(substr($tool['description'], 0, 100) . '...'); ?>" target="_blank" class="social-share linkedin">
                                <i class="fab fa-linkedin-in"></i> LinkedIn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Tools Section -->
<?php if (!empty($related_tools)): ?>
<section class="section section-related">
    <div class="container">
        <div class="section-header">
            <h2><?php echo __('related_tools'); ?></h2>
            <a href="/<?php echo $current_language; ?>/category/<?php echo $tool['category_slug'] ?? ''; ?>">
                <?php echo __('view_all'); ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="tools-grid">
            <?php foreach ($related_tools as $related_tool): ?>
                <div class="tool-card" data-category="<?php echo $related_tool['category_id']; ?>">
                    <?php if ($related_tool['featured']): ?>
                        <div class="featured-badge"><?php echo __('featured'); ?></div>
                    <?php endif; ?>
                    <?php if ($related_tool['new_launch']): ?>
                        <div class="new-badge"><?php echo __('new'); ?></div>
                    <?php endif; ?>

                    <div class="tool-card-header">
                        <div class="tool-card-logo">
                            <?php if (!empty($related_tool['logo'])): ?>
                                <img src="/images/<?php echo $related_tool['logo']; ?>" alt="<?php echo $related_tool['name']; ?> logo">
                            <?php else: ?>
                                <img src="/images/default-tool-logo.png" alt="<?php echo $related_tool['name']; ?> logo">
                            <?php endif; ?>
                        </div>
                        <div class="tool-card-title">
                            <h3><a href="/<?php echo $current_language; ?>/tool/<?php echo $related_tool['slug']; ?>"><?php echo $related_tool['name']; ?></a></h3>
                            <div class="category"><?php echo $related_tool['category_name']; ?></div>
                        </div>
                    </div>

                    <div class="tool-card-body">
                        <div class="tool-card-description">
                            <?php echo substr($related_tool['description'], 0, 120) . '...'; ?>
                        </div>
                    </div>

                    <div class="tool-card-footer">
                        <div class="tool-card-stats">
                            <div class="rating">
                                <div class="stars" data-rating="<?php echo $related_tool['rating']; ?>"></div>
                                <span><?php echo number_format($related_tool['rating'], 1); ?></span>
                            </div>
                            <div class="upvotes">
                                <i class="fas fa-thumbs-up"></i>
                                <span><?php echo $related_tool['upvotes']; ?></span>
                            </div>
                        </div>
                        <div class="tool-card-actions">
                            <a href="/<?php echo $current_language; ?>/tool/<?php echo $related_tool['slug']; ?>" class="btn btn-secondary"><?php echo __('details'); ?></a>
                            <a href="<?php echo $related_tool['website_url']; ?>" target="_blank" class="btn btn-primary"><?php echo __('visit'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Tool Detail Styles -->
<style>
/* Breadcrumbs */
.breadcrumbs {
    margin-bottom: 30px;
}

.breadcrumbs ul {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
}

.breadcrumbs ul li {
    position: relative;
    padding-right: 20px;
    margin-right: 10px;
}

.breadcrumbs ul li:not(:last-child)::after {
    content: '/';
    position: absolute;
    right: 0;
    color: var(--gray-500);
}

.breadcrumbs ul li a {
    color: var(--gray-600);
}

.breadcrumbs ul li a:hover {
    color: var(--primary-color);
}

/* Tool Detail Header */
.tool-detail-header {
    display: flex;
    gap: 30px;
    margin-bottom: 40px;
}

.tool-detail-logo {
    flex: 0 0 150px;
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--white);
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--box-shadow);
}

.tool-detail-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.tool-detail-info {
    flex: 1;
}

.tool-detail-info h1 {
    font-size: 2.2rem;
    margin-bottom: 15px;
    color: var(--gray-900);
}

.tool-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.tool-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tool-tags .tag {
    background-color: var(--gray-200);
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9rem;
    color: var(--gray-700);
}

.tool-tags .tag:hover {
    background-color: var(--primary-color);
    color: white;
}

.tool-stats {
    display: flex;
    align-items: center;
    gap: 20px;
}

.tool-stats .views {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--gray-600);
}

.tool-action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

/* Tool Detail Content */
.tool-detail-content {
    display: flex;
    gap: 30px;
}

.tool-detail-main {
    flex: 1;
}

.tool-detail-section {
    margin-bottom: 40px;
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 30px;
}

.tool-detail-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.tool-detail-section h2 {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: var(--gray-800);
}

.tool-description p {
    margin-bottom: 15px;
    line-height: 1.7;
    color: var(--gray-700);
}

.tool-features ul {
    list-style: none;
    padding: 0;
}

.tool-features ul li {
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
}

.tool-features ul li i {
    color: var(--primary-color);
    margin-right: 10px;
    margin-top: 4px;
}

.pricing-badge {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.pricing-badge.free {
    background-color: #e6f7ee;
    color: #1bae58;
}

.pricing-badge.freemium {
    background-color: #e2f4fd;
    color: #1184c5;
}

.pricing-badge.paid {
    background-color: #f9e8e8;
    color: #d51f1f;
}

.pricing-badge.contact {
    background-color: #f1f1f1;
    color: #666;
}

.social-share-buttons {
    display: flex;
    gap: 10px;
}

.social-share {
    display: inline-flex;
    align-items: center;
    padding: 8px 15px;
    border-radius: var(--border-radius);
    font-size: 0.9rem;
    text-decoration: none;
    transition: var(--transition);
}

.social-share i {
    margin-right: 8px;
}

.social-share.twitter {
    background-color: #1da1f2;
    color: white;
}

.social-share.facebook {
    background-color: #4267B2;
    color: white;
}

.social-share.linkedin {
    background-color: #0077b5;
    color: white;
}

.social-share:hover {
    opacity: 0.9;
    color: white;
}

.tool-card-actions {
    display: flex;
    gap: 10px;
}

/* Tool Rating & Reviews */
.tool-rating-reviews {
    margin-top: 20px;
}

.rating-summary {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e5e5;
}

.average-rating {
    display: flex;
    align-items: center;
}

.rating-number {
    font-size: 48px;
    font-weight: bold;
    margin-right: 15px;
    color: #333;
}

.rating-stars {
    display: flex;
    flex-direction: column;
}

.rating-stars .stars {
    display: flex;
    margin-bottom: 5px;
}

.rating-stars .stars i {
    color: #f9bc00;
    font-size: 24px;
    margin-right: 3px;
}

.rating-count {
    font-size: 14px;
    color: #666;
}

.your-rating {
    background-color: #f8f8f8;
    padding: 15px;
    border-radius: 8px;
    width: 40%;
}

.your-rating p {
    margin-top: 0;
    margin-bottom: 10px;
    font-weight: 500;
}

.rating-stars-input {
    display: flex;
    margin-bottom: 10px;
}

.rating-stars-input i {
    color: #f9bc00;
    font-size: 28px;
    margin-right: 5px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.rating-stars-input i:hover {
    transform: scale(1.2);
}

.rating-message {
    display: block;
    margin-top: 5px;
    font-size: 14px;
}

.rating-message.success {
    color: #28a745;
}

.rating-message.error {
    color: #dc3545;
}

.reviews-section {
    margin-top: 30px;
}

.reviews-container {
    margin-top: 20px;
}

.review-item {
    background-color: #f8f8f8;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.review-user {
    display: flex;
    align-items: center;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
}

.user-info .username {
    font-weight: 500;
}

.review-date {
    font-size: 12px;
    color: #666;
}

.review-content h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.review-content p {
    margin-top: 0;
}

.add-review {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e5e5;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.login-to-review {
    background-color: #f8f8f8;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.loading-reviews {
    text-align: center;
    padding: 20px;
    color: #666;
}

.hidden {
    display: none;
}

.no-reviews {
    text-align: center;
    padding: 20px;
    color: #666;
    background-color: #f8f8f8;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .rating-summary {
        flex-direction: column;
    }

    .your-rating {
        width: 100%;
        margin-top: 20px;
    }
}

/* Responsive Styles */
@media screen and (max-width: 768px) {
    .tool-detail-header {
        flex-direction: column;
        gap: 20px;
    }

    .tool-detail-logo {
        margin: 0 auto;
    }

    .tool-detail-info h1 {
        text-align: center;
    }

    .tool-meta {
        justify-content: center;
    }

    .tool-action-buttons {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<!-- Scripts -->
<script>
// Initialize star ratings
document.addEventListener('DOMContentLoaded', function() {
    // Generate stars based on rating
    const starsElements = document.querySelectorAll('.stars');
    starsElements.forEach(el => {
        const rating = parseFloat(el.getAttribute('data-rating'));
        let starsHtml = '';

        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                starsHtml += '<i class="fas fa-star"></i>';
            } else if (i - 0.5 <= rating) {
                starsHtml += '<i class="fas fa-star-half-alt"></i>';
            } else {
                starsHtml += '<i class="far fa-star"></i>';
            }
        }

        el.innerHTML = starsHtml;
    });

    // Function to load existing vote status
    function loadExistingVote(toolId) {
        const currentLang = document.documentElement.lang;
        fetch(`/includes/upvote.php?check=1&tool_id=${toolId}&lang=${currentLang}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.already_upvoted) {
                const upvoteBtn = document.querySelector('.upvote-button');
                if (upvoteBtn) {
                    upvoteBtn.classList.add('upvoted');
                    upvoteBtn.disabled = true;
                    upvoteBtn.innerHTML = '<i class="fas fa-thumbs-up"></i> <span class="label"><?php echo __("upvoted"); ?></span> <span class="count">' + data.upvotes + '</span>';
                }
            }
        })
        .catch(error => {
            console.error('Error checking upvote status:', error);
        });
    }

    // Function to load existing favorite status
    function loadExistingFavorite(toolId) {
        const currentLang = document.documentElement.lang;
        fetch(`/includes/favorite.php?tool_id=${toolId}&lang=${currentLang}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.in_favorites) {
                const favoriteBtn = document.querySelector('.favorite-button');
                if (favoriteBtn) {
                    favoriteBtn.querySelector('i').classList.remove('far');
                    favoriteBtn.querySelector('i').classList.add('fas');
                    favoriteBtn.classList.add('favorited');
                    favoriteBtn.querySelector('.label').textContent = '<?php echo __('remove_from_favorites'); ?>';
                }
            }
        })
        .catch(error => {
            console.error('Error checking favorite status:', error);
        });
    }

    // Handle upvote button
    const upvoteBtn = document.querySelector('.upvote-button');
    if (upvoteBtn) {
        const toolId = upvoteBtn.getAttribute('data-id');

        loadExistingVote(toolId);

        upvoteBtn.addEventListener('click', function() {
            const toolId = this.getAttribute('data-id');

            // AJAX request to upvote.php
            fetch('/includes/upvote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'tool_id=' + toolId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update upvote count
                    document.querySelector('.upvotes span').textContent = data.upvotes;
                    upvoteBtn.classList.add('upvoted');
                    upvoteBtn.innerHTML = '<i class="fas fa-thumbs-up"></i> <span class="label"><?php echo __("upvoted"); ?></span> <span class="count">' + data.upvotes + '</span>';

                    // Show success message
                    showMessage('<?php echo __("upvote_recorded"); ?>', 'success');
                } else {
                    // Check if user already upvoted
                    if (data.message.includes('<?php echo __("already_upvoted"); ?>')) {
                        // Mark button as already voted
                        upvoteBtn.classList.add('upvoted');
                        upvoteBtn.disabled = true;
                        upvoteBtn.innerHTML = '<i class="fas fa-thumbs-up"></i> <span class="label"><?php echo __("upvoted"); ?></span> <span class="count">' + data.upvotes + '</span>';

                        // Show message about already voted
                        showMessage('<?php echo __("already_upvoted"); ?>', 'info');
                    }
                    else if (data.redirect) {
                        // Redirect to login page
                        window.location.href = data.redirect;
                    } else {
                        showMessage(data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('<?php echo __("vote_process_error"); ?>', 'error');
            });
        });
    }

    // Handle favorite button
    const favoriteBtn = document.querySelector('.favorite-button');
    if (favoriteBtn) {
        const toolId = favoriteBtn.getAttribute('data-id');

        loadExistingFavorite(toolId);

        favoriteBtn.addEventListener('click', function() {
            const toolId = this.getAttribute('data-id');

            toggleFavorite(toolId);
        });
    }

    // Function to toggle favorite status
    function toggleFavorite(toolId) {
        const currentLang = document.documentElement.lang;
        const isDefaultLang = currentLang === 'en';
        const favoriteBtn = document.querySelector('.favorite-button');

        // Determine if we're adding or removing
        const action = favoriteBtn.classList.contains('favorited') ? 'remove' : 'add';

        fetch('/includes/favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `tool_id=${toolId}&action=${action}&lang=${currentLang}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (action === 'add') {
                    // Added to favorites
                    favoriteBtn.querySelector('i').classList.remove('far');
                    favoriteBtn.querySelector('i').classList.add('fas');
                    favoriteBtn.classList.add('favorited');
                    favoriteBtn.querySelector('.label').textContent = '<?php echo __('remove_from_favorites'); ?>';
                    showMessage(data.message || '<?php echo __('added_to_favorites'); ?>', 'success');
                } else {
                    // Removed from favorites
                    favoriteBtn.querySelector('i').classList.remove('fas');
                    favoriteBtn.querySelector('i').classList.add('far');
                    favoriteBtn.classList.remove('favorited');
                    favoriteBtn.querySelector('.label').textContent = '<?php echo __('add_to_favorites'); ?>';
                    showMessage(data.message || '<?php echo __('removed_from_favorites'); ?>', 'success');
                }
            } else {
                if (data.redirect) {
                    // Redirect to login page with current language (handle default language case)
                    if (isDefaultLang) {
                        // For default language (en), don't add language prefix
                        window.location.href = data.redirect.replace(/^\/[a-z]{2}\//, '/');
                    } else {
                        window.location.href = data.redirect;
                    }
                } else {
                    showMessage(data.message || '<?php echo __("favorites_error_generic"); ?>', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('<?php echo __("favorites_error_generic"); ?>', 'error');
        });
    }

    // Function to submit rating
    function submitRating(toolId, rating) {
        const currentLang = document.documentElement.lang;
        const isDefaultLang = currentLang === 'en';
        const ratingMessage = document.getElementById('rating-message');

        fetch('/includes/rate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `tool_id=${toolId}&rating=${rating}&lang=${currentLang}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Pobierz przetłumaczony komunikat o ocenie
                fetch(`/includes/language.php?get_translation=your_rating&lang=${currentLang}`, {
                    method: 'GET'
                })
                .then(response => response.text())
                .then(translatedMsg => {
                    const msg = translatedMsg || 'Your rating';
                    ratingMessage.textContent = `${msg}: ${rating}`;
                    ratingMessage.className = 'success';

                    // Aktualizacja wyświetlania oceny
                    const avgRatingElement = document.querySelector('.tool-rating .rating-value');
                    if (avgRatingElement) {
                        avgRatingElement.textContent = data.average_rating;
                    }

                    const starsElement = document.querySelector('.tool-rating .stars');
                    if (starsElement) {
                        starsElement.setAttribute('data-rating', data.average_rating);
                        regenerateStars(starsElement, data.average_rating);
                    }

                    // Pokaż komunikat sukcesu
                    showMessage(data.message, 'success');
                })
                .catch(error => {
                    console.error('Error fetching translation:', error);
                    ratingMessage.textContent = `Your rating: ${rating}`;
                    ratingMessage.className = 'success';
                });
            } else {
                ratingMessage.textContent = data.message;
                ratingMessage.className = 'error';
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error submitting rating:', error);
            fetch(`/includes/language.php?get_translation=rating_error&lang=${currentLang}`, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(errorMsg => {
                const msg = errorMsg || 'Error submitting rating';
                ratingMessage.textContent = msg;
                ratingMessage.className = 'error';
                showMessage(msg, 'error');
            })
            .catch(() => {
                ratingMessage.textContent = 'Error submitting rating';
                ratingMessage.className = 'error';
                showMessage('Error submitting rating', 'error');
            });
        });
    }

    // Handle share button
    const shareBtn = document.querySelector('.share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            // Scroll to share section
            document.querySelector('.tool-share').scrollIntoView({
                behavior: 'smooth'
            });
        });
    }

    const toolId = <?php echo $tool['id']; ?>;

    // Load reviews
    loadReviews(toolId);
    loadRatings(toolId);

    // Rating stars input
    const ratingStarsInput = document.querySelectorAll('.rating-stars-input i');
    const ratingMessage = document.querySelector('.rating-message');

    // Handle hovering over stars
    ratingStarsInput.forEach(star => {
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            highlightStars(rating);
        });

        star.addEventListener('mouseout', function() {
            resetStars();
        });

        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            submitRating(toolId, rating);
        });
    });

    // Function to highlight stars on hover
    function highlightStars(rating) {
        ratingStarsInput.forEach(star => {
            const starRating = parseInt(star.getAttribute('data-rating'));
            if (starRating <= rating) {
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }

    // Function to reset stars after hover
    function resetStars() {
        ratingStarsInput.forEach(star => {
            const starRating = parseInt(star.getAttribute('data-rating'));
            if (star.classList.contains('selected')) {
                star.classList.remove('far');
                star.classList.add('fas');
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
            }
        });
    }

    // Function to submit review
    function submitReview(toolId, title, content) {
        const currentLang = document.documentElement.lang;
        const isDefaultLang = currentLang === 'en';
        const submitButton = document.querySelector('#review-form button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo __("submitting"); ?>';

        fetch('/includes/review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `tool_id=${toolId}&title=${encodeURIComponent(title)}&content=${encodeURIComponent(content)}&lang=${currentLang}`
        })
        .then(response => response.json())
        .then(data => {
            submitButton.disabled = false;
            submitButton.textContent = '<?php echo __("submit_review"); ?>';

            if (data.success) {
                showMessage(data.message, 'success');

                // Clear form
                document.getElementById('review-title').value = '';
                document.getElementById('review-content').value = '';
                loadReviews(toolId);
            } else {
                if (data.redirect) {
                    // Redirect to login page with current language (handle default language case)
                    if (isDefaultLang) {
                        // For default language (en), don't add language prefix
                        window.location.href = data.redirect.replace(/^\/[a-z]{2}\//, '/');
                    } else {
                        window.location.href = data.redirect;
                    }
                } else {
                    showMessage(data.message, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitButton.disabled = false;
            submitButton.textContent = '<?php echo __("submit_review"); ?>';
            showMessage('<?php echo __("review_submission_error"); ?>', 'error');
        });
    }

    // Function to load reviews
    function loadReviews(toolId) {
        const currentLang = document.documentElement.lang;
        const reviewsList = document.getElementById('reviews-list');
        const loadingReviews = document.querySelector('.loading-reviews');
        const noReviews = document.querySelector('.no-reviews');

        fetch(`/includes/review.php?tool_id=${toolId}&lang=${currentLang}`)
        .then(response => response.json())
        .then(data => {
            loadingReviews.classList.add('hidden');

            if (data.success && data.reviews && data.reviews.length > 0) {
                reviewsList.innerHTML = '';
                data.reviews.forEach(review => {
                    const reviewEl = document.createElement('div');
                    reviewEl.className = 'review-item';

                    reviewEl.innerHTML = `
                        <div class="review-header">
                            <div class="review-user">
                                <img src="${review.avatar}" alt="${review.username || '<?php echo __("anonymous"); ?>'}" class="user-avatar">
                                <div class="user-info">
                                    <div class="username">${review.username || '<?php echo __("anonymous"); ?>'}</div>
                                    <div class="review-date">${review.formatted_date}</div>
                                </div>
                            </div>
                        </div>
                        <div class="review-content">
                            ${review.title ? `<h4>${review.title}</h4>` : ''}
                            <p>${review.content}</p>
                        </div>
                    `;

                    reviewsList.appendChild(reviewEl);
                });
            } else {
                noReviews.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            loadingReviews.classList.add('hidden');
            noReviews.classList.remove('hidden');
            noReviews.querySelector('p').textContent = '<?php echo __("error_loading_reviews"); ?>';
        });
    }

    // Function to load ratings
    function loadRatings(toolId) {
        const currentLang = document.documentElement.lang;
        // Get overall ratings and count
        fetch(`/includes/rate.php?tool_id=${toolId}&count_only=1&lang=${currentLang}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('rating-count-display').textContent = data.count;
                    const starsElement = document.querySelector('.tool-rating .stars');
                    starsElement.setAttribute('data-rating', data.average);
                    regenerateStars(starsElement, data.average);
                }
            })
            .catch(error => console.error('Error loading ratings:', error));

        // Get user's existing rating if available
        fetch(`/includes/rate.php?tool_id=${toolId}&lang=${currentLang}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.user_rating) {
                    const userRating = data.user_rating;
                    const stars = document.querySelectorAll('.rating-input label');
                    for (let i = 0; i < stars.length; i++) {
                        if (i < userRating) {
                            stars[i].classList.add('selected');
                        }
                    }
                    ratingMessage.textContent = '<?php echo __("your_rating"); ?>: ' + userRating;
                    ratingMessage.classList.add('success');
                }
            })
            .catch(error => console.error('Error loading user rating:', error));
    }

    // Function to show message
    function showMessage(message, type = 'info') {
        // Check if message container exists, if not create it
        let messageContainer = document.querySelector('.message-container');

        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            document.body.appendChild(messageContainer);
        }

        // Create message element
        const messageElement = document.createElement('div');
        messageElement.className = `message message-${type}`;
        messageElement.textContent = message;

        // Add close button
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '&times;';
        closeButton.className = 'message-close';
        closeButton.addEventListener('click', function() {
            messageElement.remove();
        });

        messageElement.appendChild(closeButton);
        messageContainer.appendChild(messageElement);

        // Auto remove after 5 seconds
        setTimeout(() => {
            messageElement.remove();
        }, 5000);
    }
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
