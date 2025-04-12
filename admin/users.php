<?php
// Include header
include_once 'includes/header.php';

// Only admin can access user management
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
$limit = 20;
$offset = ($page - 1) * $limit;
$role = isset($_GET['role']) ? $_GET['role'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Handle bulk actions if any
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_ids = isset($_POST['selected']) ? $_POST['selected'] : [];

    if (!empty($selected_ids)) {
        $success_count = 0;

        foreach ($selected_ids as $id) {
            // Skip the current user for certain actions
            if ((int)$id === (int)$_SESSION['user_id'] && in_array($action, ['delete', 'ban'])) {
                continue;
            }

            $user = get_user($id);
            if (!$user) continue;

            if ($action === 'delete') {
                $result = delete_user($id);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'delete', 'user', $id);
                    $success_count++;
                }
            } else if ($action === 'activate') {
                $user['status'] = 'active';
                $result = save_user($user);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'update', 'user', $id, 'Aktywowano użytkownika');
                    $success_count++;
                }
            } else if ($action === 'deactivate') {
                $user['status'] = 'inactive';
                $result = save_user($user);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'update', 'user', $id, 'Dezaktywowano użytkownika');
                    $success_count++;
                }
            } else if ($action === 'ban') {
                $user['status'] = 'banned';
                $result = save_user($user);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'update', 'user', $id, 'Zablokowano użytkownika');
                    $success_count++;
                }
            } else if ($action === 'make_admin') {
                $user['role'] = 'admin';
                $result = save_user($user);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'update', 'user', $id, 'Nadano uprawnienia administratora');
                    $success_count++;
                }
            } else if ($action === 'make_editor') {
                $user['role'] = 'editor';
                $result = save_user($user);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'update', 'user', $id, 'Nadano uprawnienia redaktora');
                    $success_count++;
                }
            } else if ($action === 'make_user') {
                $user['role'] = 'user';
                $result = save_user($user);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'update', 'user', $id, 'Nadano uprawnienia użytkownika');
                    $success_count++;
                }
            }
        }

        if ($success_count > 0) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Wykonano akcję na ' . $success_count . ' użytkownikach.'
            ];
        }

        // Redirect to avoid resubmission
        header('Location: users.php' . (isset($_GET['page']) ? '?page=' . $_GET['page'] : ''));
        exit;
    }
}

// Count total users
$total_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
if ($role !== null) {
    $total_query .= " AND role = '" . $conn->real_escape_string($role) . "'";
}
if ($status !== null) {
    $total_query .= " AND status = '" . $conn->real_escape_string($status) . "'";
}
$total_result = $conn->query($total_query);
$total_users = 0;
if ($total_result && $total_result->num_rows > 0) {
    $row = $total_result->fetch_assoc();
    $total_users = $row['total'];
}
$total_pages = ceil($total_users / $limit);

// Get users with filters
$users = get_users($limit, $offset, $role, $status);
?>

<!-- Filters and Search -->
<div class="admin-filters" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="filter-actions" style="display: flex; gap: 10px;">
        <div class="filter-dropdown">
            <select id="roleFilter" class="admin-form-select" style="min-width: 150px;">
                <option value="">Wszystkie role</option>
                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                <option value="editor" <?php echo $role === 'editor' ? 'selected' : ''; ?>>Redaktor</option>
                <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Użytkownik</option>
            </select>
        </div>

        <div class="filter-dropdown">
            <select id="statusFilter" class="admin-form-select" style="min-width: 150px;">
                <option value="">Wszystkie statusy</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Aktywny</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Nieaktywny</option>
                <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>Zablokowany</option>
            </select>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="userSearch" class="table-search" data-table="#usersTable" placeholder="Szukaj użytkowników...">
        <i class="fas fa-search"></i>
    </div>
</div>

<!-- Users Table -->
<form method="post" action="users.php<?php echo isset($_GET['page']) ? '?page=' . $_GET['page'] : ''; ?>" class="bulk-action-form">
    <table class="admin-table" id="usersTable">
        <thead>
            <tr>
                <th width="30">
                    <input type="checkbox" class="bulk-checkbox-all">
                </th>
                <th width="50">ID</th>
                <th width="200">Nazwa użytkownika</th>
                <th>Email</th>
                <th width="100">Rola</th>
                <th width="100">Status</th>
                <th width="150">Data rejestracji</th>
                <th width="120">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected[]" value="<?php echo $user['id']; ?>" class="bulk-checkbox">
                        </td>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ((int)$user['id'] === (int)$_SESSION['user_id']): ?>
                                <span class="badge badge-primary">Ty</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge badge-danger">Administrator</span>
                            <?php elseif ($user['role'] === 'editor'): ?>
                                <span class="badge badge-warning">Redaktor</span>
                            <?php else: ?>
                                <span class="badge badge-light">Użytkownik</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge badge-success">Aktywny</span>
                            <?php elseif ($user['status'] === 'inactive'): ?>
                                <span class="badge badge-secondary">Nieaktywny</span>
                            <?php elseif ($user['status'] === 'banned'): ?>
                                <span class="badge badge-danger">Zablokowany</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                        <td class="actions">
                            <a href="user-edit.php?id=<?php echo $user['id']; ?>" title="Edytuj">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="#" class="confirm-action" data-confirm="Czy na pewno chcesz usunąć tego użytkownika?" onclick="deleteUser(<?php echo $user['id']; ?>); return false;" title="Usuń">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php else: ?>
                                <i class="fas fa-trash-alt" style="color: #ccc;" title="Nie możesz usunąć swojego konta"></i>
                            <?php endif; ?>
                            <a href="#" onclick="showUserActivity(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>'); return false;" title="Historia aktywności">
                                <i class="fas fa-history"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Brak użytkowników do wyświetlenia</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Bulk Actions -->
    <div class="bulk-actions" style="margin-top: 20px; display: flex; align-items: center;">
        <select name="bulk_action" class="bulk-action-select">
            <option value="">Akcje masowe</option>
            <option value="activate">Aktywuj</option>
            <option value="deactivate">Dezaktywuj</option>
            <option value="ban">Zablokuj</option>
            <option value="make_admin">Nadaj uprawnienia administratora</option>
            <option value="make_editor">Nadaj uprawnienia redaktora</option>
            <option value="make_user">Nadaj uprawnienia użytkownika</option>
            <option value="delete">Usuń</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm" style="margin-left: 10px;">Zastosuj</button>
    </div>
</form>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="admin-pagination" style="margin-top: 30px; display: flex; justify-content: center;">
        <ul style="display: flex; list-style: none; padding: 0; gap: 5px;">
            <?php if ($page > 1): ?>
                <li>
                    <a href="users.php?page=<?php echo ($page - 1); ?><?php echo $role ? '&role=' . $role : ''; ?><?php echo $status ? '&status=' . $status : ''; ?>" class="btn btn-secondary btn-sm">
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
                    <a href="users.php?page=<?php echo $i; ?><?php echo $role ? '&role=' . $role : ''; ?><?php echo $status ? '&status=' . $status : ''; ?>" class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li>
                    <a href="users.php?page=<?php echo ($page + 1); ?><?php echo $role ? '&role=' . $role : ''; ?><?php echo $status ? '&status=' . $status : ''; ?>" class="btn btn-secondary btn-sm">
                        Następna &raquo;
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- User Activity Modal (placeholder) -->
<div id="userActivityModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; overflow: auto;">
    <div class="modal-content" style="background-color: #fff; margin: 10% auto; padding: 20px; width: 80%; max-width: 800px; border-radius: var(--admin-border-radius); box-shadow: var(--admin-shadow);">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--admin-border);">
            <h2 id="userActivityTitle" style="margin: 0; font-size: 1.5rem;">Historia aktywności użytkownika</h2>
            <span class="modal-close" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>
        <div id="userActivityContent" class="modal-body" style="max-height: 400px; overflow-y: auto;">
            <p>Ładowanie historii aktywności...</p>
        </div>
    </div>
</div>

<!-- JavaScript for interactions -->
<script>
// Role filter redirect
document.getElementById('roleFilter').addEventListener('change', function() {
    const role = this.value;
    const status = new URLSearchParams(window.location.search).get('status');
    let url = 'users.php';

    if (role || status) {
        url += '?';
        if (role) {
            url += 'role=' + role;
        }
        if (status) {
            url += (role ? '&' : '') + 'status=' + status;
        }
    }

    window.location.href = url;
});

// Status filter redirect
document.getElementById('statusFilter').addEventListener('change', function() {
    const status = this.value;
    const role = new URLSearchParams(window.location.search).get('role');
    let url = 'users.php';

    if (role || status) {
        url += '?';
        if (role) {
            url += 'role=' + role;
        }
        if (status) {
            url += (role ? '&' : '') + 'status=' + status;
        }
    }

    window.location.href = url;
});

// Delete user function
function deleteUser(id) {
    if (confirm('Czy na pewno chcesz usunąć tego użytkownika?')) {
        // Create a form and submit it
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'user-delete.php';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = id;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Show user activity modal
function showUserActivity(userId, username) {
    // Get the modal and set the title
    const modal = document.getElementById('userActivityModal');
    const title = document.getElementById('userActivityTitle');
    const content = document.getElementById('userActivityContent');

    title.textContent = 'Historia aktywności: ' + username;
    content.innerHTML = '<p>Ładowanie historii aktywności...</p>';

    // Show the modal
    modal.style.display = 'block';

    // Fetch user activity
    fetch('ajax/user-activity.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';

                if (data.activities.length > 0) {
                    html = '<table class="admin-table">';
                    html += '<thead><tr><th>Data</th><th>Akcja</th><th>Szczegóły</th></tr></thead>';
                    html += '<tbody>';

                    data.activities.forEach(activity => {
                        html += '<tr>';
                        html += '<td>' + activity.date + '</td>';
                        html += '<td>' + activity.action + '</td>';
                        html += '<td>' + activity.details + '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                } else {
                    html = '<p>Brak aktywności dla tego użytkownika.</p>';
                }

                content.innerHTML = html;
            } else {
                content.innerHTML = '<p class="error">Błąd: ' + data.message + '</p>';
            }
        })
        .catch(error => {
            content.innerHTML = '<p class="error">Błąd podczas ładowania danych: ' + error.message + '</p>';
        });
}

// Close the modal when the close button is clicked
document.querySelector('.modal-close').addEventListener('click', function() {
    document.getElementById('userActivityModal').style.display = 'none';
});

// Close the modal when clicking outside of it
window.addEventListener('click', function(event) {
    const modal = document.getElementById('userActivityModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
