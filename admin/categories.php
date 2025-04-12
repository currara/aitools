<?php
// Include header
include_once 'includes/header.php';

// Handle bulk actions if any
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_ids = isset($_POST['selected']) ? $_POST['selected'] : [];

    if (!empty($selected_ids) && $action === 'delete') {
        foreach ($selected_ids as $id) {
            $result = delete_category($id);
            if ($result['success']) {
                log_activity($_SESSION['user_id'], 'delete', 'category', $id);
            }
        }

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Wybrane kategorie zostały usunięte.'
        ];

        // Redirect to avoid resubmission
        header('Location: categories.php');
        exit;
    }
}

// Get all categories (including subcategories)
$categories = get_categories('all');
?>

<!-- Search and Filter -->
<div class="admin-filters">
    <div class="search-box">
        <input type="text" id="categorySearch" class="table-search" data-table="#categoriesTable" placeholder="Szukaj kategorii...">
        <i class="fas fa-search"></i>
    </div>

    <div class="admin-buttons">
        <a href="category-edit.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Dodaj kategorię
        </a>
        <a href="remove-duplicate-categories.php" class="btn btn-warning btn-sm">
            <i class="fas fa-broom"></i> Usuń duplikaty
        </a>
    </div>
</div>

<!-- Categories Table -->
<form method="post" action="categories.php" class="bulk-action-form">
    <table class="admin-table" id="categoriesTable">
        <thead>
            <tr>
                <th width="30">
                    <input type="checkbox" class="bulk-checkbox-all">
                </th>
                <th width="50">ID</th>
                <th width="50">Ikona</th>
                <th>Nazwa</th>
                <th>Slug</th>
                <th>Kategoria nadrzędna</th>
                <th>Narzędzia</th>
                <th width="150">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categories)): ?>
                <?php
                // Funkcja rekurencyjna do wyświetlania kategorii i podkategorii
                function display_categories($categories, $level = 0)
                {
                    foreach ($categories as $category):
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                        $parent_name = '';
                        if (isset($category['parent_id']) && $category['parent_id'] > 0) {
                            // Pobierz nazwę kategorii nadrzędnej
                            global $conn;
                            $parent_query = "SELECT name FROM categories WHERE id = " . (int)$category['parent_id'];
                            $parent_result = $conn->query($parent_query);
                            if ($parent_result && $parent_result->num_rows > 0) {
                                $parent_row = $parent_result->fetch_assoc();
                                $parent_name = $parent_row['name'];
                            }
                        }
                ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected[]" value="<?php echo $category['id']; ?>" class="bulk-checkbox">
                            </td>
                            <td><?php echo $category['id']; ?></td>
                            <td>
                                <?php if (!empty($category['icon'])): ?>
                                    <img src="../images/icons/<?php echo htmlspecialchars($category['icon']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 24px; height: 24px;">
                                <?php else: ?>
                                    <i class="fas fa-folder"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $indent . htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                            <td><?php echo $parent_name ? htmlspecialchars($parent_name) : '—'; ?></td>
                            <td>
                                <?php
                                $tool_count = count_tools($category['id']);
                                echo $tool_count;
                                ?>
                            </td>
                            <td class="actions">
                                <a href="category-edit.php?id=<?php echo $category['id']; ?>" title="Edytuj">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../category.php?slug=<?php echo $category['slug']; ?>" target="_blank" title="Podgląd">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="confirm-action" data-confirm="Czy na pewno chcesz usunąć tę kategorię? Wszystkie narzędzia w tej kategorii zostaną oznaczone jako 'bez kategorii'." onclick="deleteCategory(<?php echo $category['id']; ?>); return false;" title="Usuń">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                <?php
                        // Wyświetl podkategorie (jeśli istnieją)
                        if (!empty($category['subcategories'])) {
                            display_categories($category['subcategories'], $level + 1);
                        }
                    endforeach;
                }

                // Wyświetl wszystkie kategorie
                display_categories($categories);
                ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Brak kategorii do wyświetlenia</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Bulk Actions -->
    <div class="bulk-actions" style="margin-top: 20px; display: flex; align-items: center;">
        <select name="bulk_action" class="bulk-action-select">
            <option value="">Akcje masowe</option>
            <option value="delete">Usuń wybrane</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm" style="margin-left: 10px;">Zastosuj</button>
    </div>
</form>

<!-- JavaScript for delete functionality -->
<script>
    function deleteCategory(id) {
        if (confirm('Czy na pewno chcesz usunąć tę kategorię? Wszystkie narzędzia w tej kategorii zostaną oznaczone jako "bez kategorii".')) {
            // Create a form and submit it
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'category-delete.php';

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
<script>
    // Sortowanie tabeli kategorii
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('categoriesTable');
        const headers = table.querySelectorAll('thead th:not(:first-child):not(:last-child)'); // Pomijamy pierwszą i ostatnią kolumnę

        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const index = Array.from(this.parentNode.children).indexOf(this);
                sortTable(index);
            });
            // Dodaj ikonę sortowania
            header.innerHTML += ' <i class="fas fa-sort"></i>';
        });

        function sortTable(columnIndex) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isNumeric = columnIndex === 0 || columnIndex === 5; // ID lub liczba narzędzi

            // Możliwe kierunki sortowania: ascending, descending, none
            const currentDir = headers[columnIndex - 1].getAttribute('data-sort') || 'none';
            const newDir = currentDir === 'ascending' ? 'descending' : 'ascending';

            // Resetuj kierunek sortowania na wszystkich kolumnach
            headers.forEach(h => {
                h.setAttribute('data-sort', 'none');
                h.querySelector('i').className = 'fas fa-sort';
            });

            // Ustaw nowy kierunek sortowania na klikniętej kolumnie
            headers[columnIndex - 1].setAttribute('data-sort', newDir);
            headers[columnIndex - 1].querySelector('i').className = newDir === 'ascending' ? 'fas fa-sort-up' : 'fas fa-sort-down';

            // Sortuj wiersze
            rows.sort((a, b) => {
                let valA = a.children[columnIndex].textContent.trim();
                let valB = b.children[columnIndex].textContent.trim();

                if (isNumeric) {
                    valA = parseInt(valA) || 0;
                    valB = parseInt(valB) || 0;
                    return newDir === 'ascending' ? valA - valB : valB - valA;
                } else {
                    return newDir === 'ascending' ?
                        valA.localeCompare(valB, undefined, {
                            sensitivity: 'base'
                        }) :
                        valB.localeCompare(valA, undefined, {
                            sensitivity: 'base'
                        });
                }
            });

            // Wstaw posortowane wiersze z powrotem do tabeli
            rows.forEach(row => tbody.appendChild(row));
        }
    });
</script>
<?php
// Include footer
include_once 'includes/footer.php';
?>
