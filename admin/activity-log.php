<?php
// Include header
include_once 'includes/header.php';

// Only admin can access activity log
if (!is_admin()) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Nie masz uprawnień do przeglądania tej strony.'
    ];
    header('Location: index.php');
    exit;
}

// Initialize variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$action_type = isset($_GET['action']) ? $_GET['action'] : null;
$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : null;

// Count total activities
$total_query = "SELECT COUNT(*) as total FROM activity_log WHERE 1=1";
if ($user_id !== null) {
    $total_query .= " AND user_id = " . $user_id;
}
if ($action_type !== null) {
    $total_query .= " AND action = '" . $conn->real_escape_string($action_type) . "'";
}
if ($entity_type !== null) {
    $total_query .= " AND entity_type = '" . $conn->real_escape_string($entity_type) . "'";
}
$total_result = $conn->query($total_query);
$total_activities = 0;
if ($total_result && $total_result->num_rows > 0) {
    $row = $total_result->fetch_assoc();
    $total_activities = $row['total'];
}
$total_pages = ceil($total_activities / $limit);

// Get activities with filters
$activities = get_activity_log($limit, $offset, $user_id);

// Get unique action types and entity types for filters
$action_types_query = "SELECT DISTINCT action FROM activity_log";
$action_types_result = $conn->query($action_types_query);
$action_types = [];
if ($action_types_result && $action_types_result->num_rows > 0) {
    while ($row = $action_types_result->fetch_assoc()) {
        if (!empty($row['action'])) {
            $action_types[] = $row['action'];
        }
    }
}

$entity_types_query = "SELECT DISTINCT entity_type FROM activity_log WHERE entity_type IS NOT NULL";
$entity_types_result = $conn->query($entity_types_query);
$entity_types = [];
if ($entity_types_result && $entity_types_result->num_rows > 0) {
    while ($row = $entity_types_result->fetch_assoc()) {
        if (!empty($row['entity_type'])) {
            $entity_types[] = $row['entity_type'];
        }
    }
}

// Get users for filter
$users_query = "SELECT DISTINCT u.id, u.username FROM users u
                JOIN activity_log a ON u.id = a.user_id
                ORDER BY u.username";
$users_result = $conn->query($users_query);
$users = [];
if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!-- Filters and Search -->
<div class="admin-filters" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="filter-actions" style="display: flex; gap: 10px;">
        <div class="filter-dropdown">
            <select id="userFilter" class="admin-form-select" style="min-width: 150px;">
                <option value="">Wszyscy użytkownicy</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $user_id === (int)$user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-dropdown">
            <select id="actionFilter" class="admin-form-select" style="min-width: 150px;">
                <option value="">Wszystkie akcje</option>
                <?php foreach ($action_types as $action): ?>
                    <option value="<?php echo $action; ?>" <?php echo $action_type === $action ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($action); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-dropdown">
            <select id="entityFilter" class="admin-form-select" style="min-width: 150px;">
                <option value="">Wszystkie typy</option>
                <?php foreach ($entity_types as $entity): ?>
                    <option value="<?php echo $entity; ?>" <?php echo $entity_type === $entity ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($entity); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="activitySearch" class="table-search" data-table="#activityTable" placeholder="Szukaj w dzienniku...">
        <i class="fas fa-search"></i>
    </div>
</div>

<!-- Activity Log Table -->
<table class="admin-table" id="activityTable">
    <thead>
        <tr>
            <th width="50">ID</th>
            <th width="160">Data i czas</th>
            <th width="150">Użytkownik</th>
            <th width="120">Akcja</th>
            <th width="120">Typ</th>
            <th>Szczegóły</th>
            <th width="120">Adres IP</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?php echo $activity['id']; ?></td>
                    <td><?php echo date('d.m.Y H:i:s', strtotime($activity['created_at'])); ?></td>
                    <td>
                        <?php if (isset($activity['username'])): ?>
                            <a href="user-edit.php?id=<?php echo $activity['user_id']; ?>">
                                <?php echo htmlspecialchars($activity['username']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">System</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                    <td>
                        <?php if ($activity['entity_type']): ?>
                            <?php echo htmlspecialchars($activity['entity_type']); ?>
                            <?php if ($activity['entity_id']): ?>
                                <span class="badge badge-light">#<?php echo $activity['entity_id']; ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($activity['details'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($activity['ip_address'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">Brak aktywności do wyświetlenia</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="admin-pagination" style="margin-top: 30px; display: flex; justify-content: center;">
        <ul style="display: flex; list-style: none; padding: 0; gap: 5px;">
            <?php if ($page > 1): ?>
                <li>
                    <a href="activity-log.php?page=<?php echo ($page - 1); ?><?php echo $user_id ? '&user_id=' . $user_id : ''; ?><?php echo $action_type ? '&action=' . urlencode($action_type) : ''; ?><?php echo $entity_type ? '&entity_type=' . urlencode($entity_type) : ''; ?>" class="btn btn-secondary btn-sm">
                        &laquo; Poprzednia
                    </a>
                </li>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $start_page + 4);

            if ($end_page - $start_page < 4) {
                $start_page = max(1, $end_page - 4);
            }

            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <li>
                    <a href="activity-log.php?page=<?php echo $i; ?><?php echo $user_id ? '&user_id=' . $user_id : ''; ?><?php echo $action_type ? '&action=' . urlencode($action_type) : ''; ?><?php echo $entity_type ? '&entity_type=' . urlencode($entity_type) : ''; ?>" class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li>
                    <a href="activity-log.php?page=<?php echo ($page + 1); ?><?php echo $user_id ? '&user_id=' . $user_id : ''; ?><?php echo $action_type ? '&action=' . urlencode($action_type) : ''; ?><?php echo $entity_type ? '&entity_type=' . urlencode($entity_type) : ''; ?>" class="btn btn-secondary btn-sm">
                        Następna &raquo;
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- JavaScript for filters -->
<script>
document.getElementById('userFilter').addEventListener('change', function() {
    updateFilters();
});

document.getElementById('actionFilter').addEventListener('change', function() {
    updateFilters();
});

document.getElementById('entityFilter').addEventListener('change', function() {
    updateFilters();
});

function updateFilters() {
    const userId = document.getElementById('userFilter').value;
    const actionType = document.getElementById('actionFilter').value;
    const entityType = document.getElementById('entityFilter').value;
    let url = 'activity-log.php';

    if (userId || actionType || entityType) {
        url += '?';
        if (userId) {
            url += 'user_id=' + userId;
        }
        if (actionType) {
            url += (userId ? '&' : '') + 'action=' + encodeURIComponent(actionType);
        }
        if (entityType) {
            url += ((userId || actionType) ? '&' : '') + 'entity_type=' + encodeURIComponent(entityType);
        }
    }

    window.location.href = url;
}
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
