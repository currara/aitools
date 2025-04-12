<?php
// Strona ulubionych narzędzi użytkownika
require_once 'includes/config.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Pobierz ID użytkownika
$user_id = $_SESSION['user_id'];

// Pobierz ulubione narzędzia użytkownika
$favorites = [];
$sql = "SELECT t.* FROM tools t
        JOIN favorites f ON t.id = f.tool_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

// Obsługa usuwania z ulubionych
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $tool_id = (int)$_GET['remove'];

    $sql = "DELETE FROM favorites WHERE user_id = ? AND tool_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $tool_id);

    if ($stmt->execute()) {
        // Odśwież stronę bez parametru remove
        header('Location: user-favorites.php');
        exit;
    }
}

// Dodajemy brakujące tłumaczenia
if (!isset($lang['favorites_title'])) $lang['favorites_title'] = 'Ulubione narzędzia';
if (!isset($lang['favorites_no_items'])) $lang['favorites_no_items'] = 'Nie masz jeszcze ulubionych narzędzi.';
if (!isset($lang['favorites_browse_tools'])) $lang['favorites_browse_tools'] = 'Przeglądaj narzędzia';
if (!isset($lang['favorites_count'])) $lang['favorites_count'] = 'Znaleziono %d ulubionych narzędzi';
if (!isset($lang['favorites_remove'])) $lang['favorites_remove'] = 'Usuń z ulubionych';
if (!isset($lang['favorites_remove_confirm'])) $lang['favorites_remove_confirm'] = 'Czy na pewno chcesz usunąć to narzędzie z ulubionych?';
if (!isset($lang['view_details'])) $lang['view_details'] = 'Zobacz szczegóły';
if (!isset($lang['visit_website'])) $lang['visit_website'] = 'Odwiedź stronę';
if (!isset($lang['price_free'])) $lang['price_free'] = 'Darmowe';
if (!isset($lang['price_freemium'])) $lang['price_freemium'] = 'Freemium';
if (!isset($lang['price_paid'])) $lang['price_paid'] = 'Płatne';

// Dołącz nagłówek
include_once 'includes/header.php';
?>

<div class="container">
    <h1><?php echo __('favorites_title'); ?></h1>

    <?php if (empty($favorites)): ?>
    <div class="no-favorites">
        <p><?php echo __('favorites_no_items'); ?></p>
        <a href="tools.php" class="btn btn-primary"><?php echo __('favorites_browse_tools'); ?></a>
    </div>
    <?php else: ?>

    <div class="favorites-count">
        <?php echo sprintf(__('favorites_count'), count($favorites)); ?>
    </div>

    <div class="row tools-grid">
        <?php foreach ($favorites as $tool): ?>
        <div class="col-md-4 col-sm-6">
            <div class="tool-card">
                <div class="tool-card-header">
                    <a href="<?php echo ($current_language === $default_language) ? '/tool/' : '/' . $current_language . '/tool/'; ?><?php echo $tool['slug']; ?>" class="tool-link">
                        <div class="tool-logo">
                            <?php if (!empty($tool['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($tool['logo']); ?>" alt="<?php echo htmlspecialchars($tool['name']); ?>">
                            <?php else: ?>
                            <img src="images/default-tool-logo.png" alt="<?php echo htmlspecialchars($tool['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <h3 class="tool-name"><?php echo htmlspecialchars($tool['name']); ?></h3>
                    </a>
                    <div class="tool-actions">
                        <a href="user-favorites.php?remove=<?php echo $tool['id']; ?>" class="btn-remove-favorite" title="<?php echo __('favorites_remove'); ?>" onclick="return confirm('<?php echo __('favorites_remove_confirm'); ?>')">
                            <i class="fas fa-heart-broken"></i>
                        </a>
                    </div>
                </div>
                <div class="tool-card-body">
                    <p class="tool-description"><?php echo mb_substr(htmlspecialchars($tool['description']), 0, 100) . (mb_strlen($tool['description']) > 100 ? '...' : ''); ?></p>
                    <div class="tool-meta">
                        <?php if (!empty($tool['price_type'])): ?>
                        <span class="tool-price <?php echo strtolower($tool['price_type']); ?>">
                            <?php
                            switch($tool['price_type']) {
                                case 'Free':
                                    echo __('price_free');
                                    break;
                                case 'Freemium':
                                    echo __('price_freemium');
                                    break;
                                case 'Paid':
                                    echo __('price_paid');
                                    break;
                                default:
                                    echo htmlspecialchars($tool['price_type']);
                            }
                            ?>
                        </span>
                        <?php endif; ?>

                        <?php if (!empty($tool['category_id'])): ?>
                        <?php
                            $stmt = $mysqli->prepare("SELECT name FROM categories WHERE id = ?");
                            $stmt->bind_param('i', $tool['category_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $category = $result->fetch_assoc();
                        ?>
                        <span class="tool-category">
                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category['name']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tool-card-footer">
                    <a href="<?php echo ($current_language === $default_language) ? '/tool/' : '/' . $current_language . '/tool/'; ?><?php echo $tool['slug']; ?>" class="btn btn-primary btn-sm"><?php echo __('view_details'); ?></a>
                    <?php if (!empty($tool['website_url'])): ?>
                    <a href="<?php echo htmlspecialchars($tool['website_url']); ?>" class="btn btn-outline-primary btn-sm" target="_blank"><?php echo __('visit_website'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.no-favorites {
    text-align: center;
    padding: 50px 0;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 30px 0;
}

.no-favorites p {
    margin-bottom: 20px;
    font-size: 1.2rem;
    color: #6c757d;
}

.favorites-count {
    margin-bottom: 20px;
    color: #6c757d;
}

.tools-grid {
    margin-top: 20px;
}

.tool-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 30px;
    transition: all 0.3s ease;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.tool-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.tool-card-header {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
    position: relative;
}

.tool-link {
    display: block;
    color: inherit;
    text-decoration: none;
}

.tool-logo {
    width: 50px;
    height: 50px;
    margin-bottom: 10px;
    display: inline-block;
    vertical-align: middle;
}

.tool-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.tool-name {
    display: inline-block;
    vertical-align: middle;
    margin-left: 10px;
    margin-bottom: 0;
    font-size: 1.1rem;
    width: calc(100% - 70px);
}

.tool-actions {
    position: absolute;
    top: 15px;
    right: 15px;
}

.btn-remove-favorite {
    color: #dc3545;
    font-size: 1.2rem;
}

.btn-remove-favorite:hover {
    color: #c82333;
}

.tool-card-body {
    padding: 15px;
    flex-grow: 1;
}

.tool-description {
    color: #6c757d;
    margin-bottom: 15px;
}

.tool-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.tool-price {
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.tool-price.free {
    background-color: #d4edda;
    color: #155724;
}

.tool-price.freemium {
    background-color: #fff3cd;
    color: #856404;
}

.tool-price.paid {
    background-color: #f8d7da;
    color: #721c24;
}

.tool-category {
    font-size: 0.8rem;
    color: #6c757d;
}

.tool-card-footer {
    padding: 15px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
}
</style>

<?php include_once 'includes/footer.php'; ?>
