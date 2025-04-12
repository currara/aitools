<?php
// Include header
include_once 'includes/header.php';

// Initialize variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$featured = isset($_GET['featured']) ? ($_GET['featured'] === '1' ? true : false) : null;
$new_launch = isset($_GET['new']) ? ($_GET['new'] === '1' ? true : false) : null;

// Handle bulk actions if any
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_ids = isset($_POST['selected']) ? $_POST['selected'] : [];

    if (!empty($selected_ids)) {
        if ($action === 'delete') {
            foreach ($selected_ids as $id) {
                $result = delete_tool($id);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'delete', 'tool', $id);
                }
            }

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Wybrane narzędzia zostały usunięte.'
            ];
        } else if ($action === 'feature') {
            foreach ($selected_ids as $id) {
                $tool = get_tool($id);
                if ($tool) {
                    $tool['featured'] = true;
                    save_tool($tool);
                    log_activity($_SESSION['user_id'], 'update', 'tool', $id, 'Oznaczono jako wyróżnione');
                }
            }

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Wybrane narzędzia zostały oznaczone jako wyróżnione.'
            ];
        } else if ($action === 'unfeature') {
            foreach ($selected_ids as $id) {
                $tool = get_tool($id);
                if ($tool) {
                    $tool['featured'] = false;
                    save_tool($tool);
                    log_activity($_SESSION['user_id'], 'update', 'tool', $id, 'Usunięto wyróżnienie');
                }
            }

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Wybrane narzędzia nie są już wyróżnione.'
            ];
        } else if ($action === 'new') {
            foreach ($selected_ids as $id) {
                $tool = get_tool($id);
                if ($tool) {
                    $tool['new_launch'] = true;
                    save_tool($tool);
                    log_activity($_SESSION['user_id'], 'update', 'tool', $id, 'Oznaczono jako nowe');
                }
            }

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Wybrane narzędzia zostały oznaczone jako nowe.'
            ];
        } else if ($action === 'not_new') {
            foreach ($selected_ids as $id) {
                $tool = get_tool($id);
                if ($tool) {
                    $tool['new_launch'] = false;
                    save_tool($tool);
                    log_activity($_SESSION['user_id'], 'update', 'tool', $id, 'Usunięto oznaczenie jako nowe');
                }
            }

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Wybrane narzędzia nie są już oznaczone jako nowe.'
            ];
        }

        // Redirect to avoid resubmission
        header('Location: tools.php' . (isset($_GET['page']) ? '?page=' . $_GET['page'] : ''));
        exit;
    }
}

// Get tools with filters
$tools = get_tools($limit, $offset, $category_id, $featured, $new_launch);
$total_tools = count_tools($category_id);
$total_pages = ceil($total_tools / $limit);

// Get all categories for filter
$categories = get_categories();
?>

<!-- Filters and Search -->
<div class="admin-filters" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="filter-actions" style="display: flex; gap: 10px;">
        <div class="filter-dropdown">
            <select id="categoryFilter" class="admin-form-select" style="min-width: 200px;">
                <option value="">Wszystkie kategorie</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_id === (int)$cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-buttons" style="display: flex; gap: 5px;">
            <a href="tools.php" class="btn btn-sm <?php echo (!$featured && !$new_launch && !$category_id) ? 'btn-primary' : 'btn-secondary'; ?>">
                Wszystkie
            </a>
            <a href="tools.php?featured=1" class="btn btn-sm <?php echo $featured ? 'btn-primary' : 'btn-secondary'; ?>">
                Wyróżnione
            </a>
            <a href="tools.php?new=1" class="btn btn-sm <?php echo $new_launch ? 'btn-primary' : 'btn-secondary'; ?>">
                Nowe
            </a>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="toolSearch" class="table-search" data-table="#toolsTable" placeholder="Szukaj narzędzi...">
        <i class="fas fa-search"></i>
    </div>
</div>

<!-- Tools Table -->
<form method="post" action="tools.php<?php echo isset($_GET['page']) ? '?page=' . $_GET['page'] : ''; ?>" class="bulk-action-form">
    <table class="admin-table" id="toolsTable">
        <thead>
            <tr>
                <th width="30">
                    <input type="checkbox" class="bulk-checkbox-all">
                </th>
                <th width="50">ID</th>
                <th width="50">Logo</th>
                <th>Nazwa</th>
                <th>Kategoria</th>
                <th width="100">Wyróżnione</th>
                <th width="100">Nowe</th>
                <th width="100">Ocena</th>
                <th width="150">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tools)): ?>
                <?php foreach ($tools as $tool): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected[]" value="<?php echo $tool['id']; ?>" class="bulk-checkbox">
                        </td>
                        <td><?php echo $tool['id']; ?></td>
                        <td>
                            <?php
                            // Wybierz właściwy obrazek w zależności od typu obrazu
                            $image_to_display = '../images/default-tool-logo.png';

                            // Określ typ obrazu
                            $image_type = isset($tool['image_type']) ? $tool['image_type'] : 'favicon';

                            // Wybierz odpowiednie źródło obrazu w zależności od typu
                            if ($image_type == 'screenshot' && !empty($tool['screenshot'])) {
                                $image_to_display = '../images/' . htmlspecialchars($tool['screenshot']);
                            } elseif (!empty($tool['logo'])) {
                                $image_to_display = '../images/' . htmlspecialchars($tool['logo']);
                            }
                            ?>
                            <img src="<?php echo $image_to_display; ?>" alt="<?php echo htmlspecialchars($tool['name']); ?>" style="width: 24px; height: 24px; object-fit: contain;">
                        </td>
                        <td><?php echo htmlspecialchars($tool['name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($tool['category_name'] ?? 'Brak kategorii'); ?>
                            <div style="font-size: 0.7em; color: gray;">(slug: '<?php echo $tool['category_slug'] ?? "NULL"; ?>')</div>
                        </td>
                        <td>
                            <?php if ($tool['featured']): ?>
                                <span class="badge badge-primary">Tak</span>
                            <?php else: ?>
                                <span class="badge badge-light">Nie</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tool['new_launch']): ?>
                                <span class="badge badge-success">Tak</span>
                            <?php else: ?>
                                <span class="badge badge-light">Nie</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <div style="color: #f1c40f; margin-right: 5px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= round($tool['rating'])): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span><?php echo number_format($tool['rating'], 1); ?></span>
                            </div>
                        </td>
                        <td class="actions">
                            <a href="tool-edit.php?id=<?php echo $tool['id']; ?>" title="Edytuj">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="../tool.php?slug=<?php echo $tool['slug']; ?>" target="_blank" title="Podgląd">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="#" class="confirm-action" data-confirm="Czy na pewno chcesz usunąć to narzędzie?" onclick="deleteTool(<?php echo $tool['id']; ?>); return false;" title="Usuń">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center;">Brak narzędzi do wyświetlenia</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Bulk Actions -->
    <div class="bulk-actions" style="margin-top: 20px; display: flex; align-items: center;">
        <select name="bulk_action" class="bulk-action-select">
            <option value="">Akcje masowe</option>
            <option value="feature">Oznacz jako wyróżnione</option>
            <option value="unfeature">Usuń wyróżnienie</option>
            <option value="new">Oznacz jako nowe</option>
            <option value="not_new">Usuń oznaczenie jako nowe</option>
            <option value="delete">Usuń wybrane</option>
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
                    <a href="tools.php?page=<?php echo ($page - 1); ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $featured !== null ? '&featured=' . ($featured ? '1' : '0') : ''; ?><?php echo $new_launch !== null ? '&new=' . ($new_launch ? '1' : '0') : ''; ?>" class="btn btn-secondary btn-sm">
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
                    <a href="tools.php?page=<?php echo $i; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $featured !== null ? '&featured=' . ($featured ? '1' : '0') : ''; ?><?php echo $new_launch !== null ? '&new=' . ($new_launch ? '1' : '0') : ''; ?>" class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li>
                    <a href="tools.php?page=<?php echo ($page + 1); ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $featured !== null ? '&featured=' . ($featured ? '1' : '0') : ''; ?><?php echo $new_launch !== null ? '&new=' . ($new_launch ? '1' : '0') : ''; ?>" class="btn btn-secondary btn-sm">
                        Następna &raquo;
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- JavaScript for interactions -->
<script>
// Category filter redirect
document.getElementById('categoryFilter').addEventListener('change', function() {
    const categoryId = this.value;
    if (categoryId) {
        window.location.href = 'tools.php?category=' + categoryId;
    } else {
        window.location.href = 'tools.php';
    }
});

// Delete tool function
function deleteTool(id) {
    if (confirm('Czy na pewno chcesz usunąć to narzędzie?')) {
        // Create a form and submit it
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tool-delete.php';

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = id;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
