<?php
// Include header
include_once 'includes/header.php';

// Get statistics
$total_tools = count_tools();
$total_categories = custom_count("SELECT COUNT(*) as total FROM categories")[0]['total'];
$total_users = custom_count("SELECT COUNT(*) as total FROM users")[0]['total'];
$recent_activity = get_activity_log(5);

// A helper function to count records from simple queries
function custom_count($sql) {
    global $conn;
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [['total' => 0]];
}
?>

<style>
    /* Style dla dashboardu admina */
    .admin-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .admin-card {
        background-color: var(--admin-card-bg, #fff);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 20px;
        position: relative;
        border: 1px solid var(--admin-border, #e5e7eb);
    }

    .admin-card-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        margin-bottom: 15px;
    }

    .admin-card-icon.primary {
        background-color: #3b82f6;
    }

    .admin-card-icon.success {
        background-color: #10b981;
    }

    .admin-card-icon.info {
        background-color: #0ea5e9;
    }

    .admin-card-icon.warning {
        background-color: #f59e0b;
    }

    .admin-card-title {
        font-size: 1rem;
        color: var(--admin-text, #4b5563);
        margin-bottom: 5px;
    }

    .admin-card-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--admin-dark, #111827);
    }

    .admin-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .admin-col {
        flex: 1;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table th {
        padding: 12px 15px;
        text-align: left;
        background-color: var(--admin-table-header-bg, #f9fafb);
        color: var(--admin-table-header-text, #4b5563);
        font-weight: 600;
        border-bottom: 1px solid var(--admin-border, #e5e7eb);
    }

    .admin-table td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--admin-border, #e5e7eb);
    }

    .admin-table tr:last-child td {
        border-bottom: none;
    }

    .admin-table a {
        color: #3b82f6;
        text-decoration: none;
    }

    .admin-table a:hover {
        text-decoration: underline;
    }

    .activity-item {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--admin-border, #e5e7eb);
    }

    .activity-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .activity-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .activity-item .username {
        font-weight: 500;
        color: var(--admin-dark, #111827);
    }

    .activity-item .timestamp {
        font-size: 0.9rem;
        color: var(--admin-light-text, #6b7280);
    }

    .activity-text {
        color: var(--admin-text, #4b5563);
    }

    .activity-text a {
        color: #3b82f6;
        text-decoration: none;
    }

    .activity-text a:hover {
        text-decoration: underline;
    }

    .admin-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.875rem;
    }

    /* Dark Theme */
    .dark-theme .admin-card {
        background-color: #222;
        border-color: #444;
    }

    .dark-theme .admin-card-title {
        color: #ccc;
    }

    .dark-theme .admin-card-value {
        color: #fff;
    }

    .dark-theme .admin-table th {
        background-color: #333;
        color: #eee;
        border-bottom-color: #444;
    }

    .dark-theme .admin-table td {
        border-bottom-color: #444;
        color: #ddd;
    }

    .dark-theme .activity-item {
        border-bottom-color: #444;
    }

    .dark-theme .activity-item .username {
        color: #eee;
    }

    .dark-theme .activity-item .timestamp {
        color: #999;
    }

    .dark-theme .activity-text {
        color: #ccc;
    }

    @media (max-width: 768px) {
        .admin-row {
            flex-direction: column;
        }

        .admin-cards {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Dashboard Cards -->
<div class="admin-cards">
    <div class="admin-card">
        <div class="admin-card-icon primary">
            <i class="fas fa-tools"></i>
        </div>
        <div class="admin-card-title">Wszystkie narzędzia</div>
        <div class="admin-card-value"><?php echo $total_tools; ?></div>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon success">
            <i class="fas fa-folder"></i>
        </div>
        <div class="admin-card-title">Kategorie</div>
        <div class="admin-card-value"><?php echo $total_categories; ?></div>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon info">
            <i class="fas fa-users"></i>
        </div>
        <div class="admin-card-title">Użytkownicy</div>
        <div class="admin-card-value"><?php echo $total_users; ?></div>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon warning">
            <i class="fas fa-eye"></i>
        </div>
        <div class="admin-card-title">Wyświetlenia strony</div>
        <div class="admin-card-value"><?php echo custom_count("SELECT SUM(views) as total FROM tools")[0]['total'] ?: 0; ?></div>
    </div>
</div>

<!-- Latest Tools and Recent Activity -->
<div class="admin-row">
    <div class="admin-col">
        <div class="admin-card" style="height: 100%;">
            <h2 style="margin-top: 0; font-size: 1.2rem; color: var(--admin-dark, #111827);">Ostatnio dodane narzędzia</h2>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nazwa</th>
                        <th>Kategoria</th>
                        <th>Data dodania</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $latest_tools = get_tools(5);
                    if (!empty($latest_tools)):
                        foreach ($latest_tools as $tool):
                    ?>
                    <tr>
                        <td>
                            <a href="tool-edit.php?id=<?php echo $tool['id']; ?>">
                                <?php echo htmlspecialchars($tool['name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($tool['category_name'] ?? 'Brak kategorii'); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($tool['created_at'])); ?></td>
                    </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">Brak narzędzi do wyświetlenia</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 15px; text-align: right;">
                <a href="tools.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-list"></i> Zobacz wszystkie
                </a>
            </div>
        </div>
    </div>

    <div class="admin-col">
        <div class="admin-card" style="height: 100%;">
            <h2 style="margin-top: 0; font-size: 1.2rem; color: var(--admin-dark, #111827);">Ostatnia aktywność</h2>

            <div class="activity-list">
                <?php if (!empty($recent_activity)): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-header">
                                <div class="username">
                                    <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?>
                                </div>
                                <div class="timestamp">
                                    <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                            <div class="activity-text">
                                <?php
                                $action = htmlspecialchars($activity['action']);
                                $entity_type = $activity['entity_type'] ? htmlspecialchars($activity['entity_type']) : null;
                                $entity_id = $activity['entity_id'] ? (int)$activity['entity_id'] : null;

                                // Create a human-readable activity description
                                if ($action === 'create' && $entity_type === 'tool') {
                                    echo 'Dodano nowe narzędzie';
                                } else if ($action === 'update' && $entity_type === 'tool') {
                                    echo 'Zaktualizowano narzędzie';
                                } else if ($action === 'delete' && $entity_type === 'tool') {
                                    echo 'Usunięto narzędzie';
                                } else if ($action === 'create' && $entity_type === 'category') {
                                    echo 'Dodano nową kategorię';
                                } else if ($action === 'update' && $entity_type === 'category') {
                                    echo 'Zaktualizowano kategorię';
                                } else if ($action === 'delete' && $entity_type === 'category') {
                                    echo 'Usunięto kategorię';
                                } else if ($action === 'login') {
                                    echo 'Zalogowano do systemu';
                                } else if ($action === 'logout') {
                                    echo 'Wylogowano z systemu';
                                } else if ($action === 'page_view') {
                                    echo $activity['details'] ? htmlspecialchars($activity['details']) : 'Odwiedzono stronę';
                                } else {
                                    echo htmlspecialchars($action);
                                    if ($entity_type) {
                                        echo ' - ' . htmlspecialchars($entity_type);
                                    }
                                }

                                // Add link to the entity if applicable
                                if ($entity_id && $entity_type === 'tool') {
                                    echo ' <a href="tool-edit.php?id=' . $entity_id . '">(#' . $entity_id . ')</a>';
                                } else if ($entity_id && $entity_type === 'category') {
                                    echo ' <a href="category-edit.php?id=' . $entity_id . '">(#' . $entity_id . ')</a>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Brak aktywności do wyświetlenia.</p>
                <?php endif; ?>
            </div>

            <div style="margin-top: 15px; text-align: right;">
                <a href="activity-log.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-history"></i> Zobacz wszystkie
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <h2 style="margin-top: 0; font-size: 1.2rem; color: var(--admin-dark, #111827);">Szybkie akcje</h2>

    <div class="admin-quick-actions">
        <a href="tool-edit.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Dodaj narzędzie
        </a>
        <a href="category-edit.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Dodaj kategorię
        </a>
        <?php if (is_admin()): ?>
        <a href="user-edit.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Dodaj użytkownika
        </a>
        <a href="settings-general.php" class="btn btn-secondary">
            <i class="fas fa-cog"></i> Ustawienia
        </a>
        <?php endif; ?>
        <a href="../index.php" target="_blank" class="btn btn-secondary">
            <i class="fas fa-external-link-alt"></i> Podgląd strony
        </a>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
