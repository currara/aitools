<?php
// Include header
include_once 'includes/header.php';

// Tylko admin może usuwać duplikaty
if (!is_admin()) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Nie masz uprawnień do wykonania tej operacji.'
    ];
    header('Location: index.php');
    exit;
}

// Inicjalizacja zmiennych
$removed_count = 0;
$errors = [];
$success = '';
$duplicate_categories = [];
$preview_mode = true; // Domyślnie pokazuj tylko podgląd

// Funkcja do znalezienia duplikatów kategorii
function find_duplicate_categories() {
    global $conn;
    $duplicates = [];

    // Znajdź potencjalne duplikaty - kategorie o tych samych nazwach (po usunięciu białych znaków)
    $sql = "SELECT id, name, TRIM(name) as trimmed_name, parent_id FROM categories ORDER BY name";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $categories = [];
        $trimmed_names = [];

        // Zbierz wszystkie kategorie
        while ($row = $result->fetch_assoc()) {
            $categories[$row['id']] = $row;

            // Grupuj według przyciętej nazwy (bez białych znaków na końcach)
            $trimmed_name = strtolower(trim($row['name']));
            $trimmed_names[$trimmed_name][] = $row['id'];
        }

        // Znajdź grupy z więcej niż jednym ID (duplikaty)
        foreach ($trimmed_names as $name => $ids) {
            if (count($ids) > 1) {
                // Pobierz szczegóły każdego duplikatu
                $group = [];
                foreach ($ids as $id) {
                    $category = $categories[$id];

                    // Pobierz nazwę kategorii nadrzędnej
                    $parent_name = "—";
                    if (!empty($category['parent_id'])) {
                        $parent_id = (int)$category['parent_id'];
                        if (isset($categories[$parent_id])) {
                            $parent_name = $categories[$parent_id]['name'];
                        } else {
                            // Pobierz z bazy jeśli nie jest w kolekcji
                            $parent_query = "SELECT name FROM categories WHERE id = " . $parent_id;
                            $parent_result = $conn->query($parent_query);
                            if ($parent_result && $parent_result->num_rows > 0) {
                                $parent_row = $parent_result->fetch_assoc();
                                $parent_name = $parent_row['name'];
                            }
                        }
                    }

                    // Pobierz liczbę narzędzi w kategorii
                    $tools_query = "SELECT COUNT(*) as count FROM tools WHERE category_id = " . (int)$category['id'];
                    $tools_result = $conn->query($tools_query);
                    $tools_count = 0;
                    if ($tools_result && $tools_result->num_rows > 0) {
                        $tools_row = $tools_result->fetch_assoc();
                        $tools_count = $tools_row['count'];
                    }

                    $group[] = [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'trimmed_name' => $category['trimmed_name'],
                        'parent_id' => $category['parent_id'],
                        'parent_name' => $parent_name,
                        'tools_count' => $tools_count
                    ];
                }

                // Sortuj grupę według ID (najniższy najpierw)
                usort($group, function($a, $b) {
                    return $a['id'] - $b['id'];
                });

                $duplicates[] = $group;
            }
        }
    }

    return $duplicates;
}

// Funkcja do usunięcia duplikatów kategorii
function remove_duplicate_categories($duplicate_groups) {
    global $conn;
    $removed_count = 0;
    $results = [];

    foreach ($duplicate_groups as $group) {
        if (count($group) <= 1) continue;

        // Zachowaj pierwszą kategorię (najniższe ID)
        $keep = array_shift($group);
        $results[] = [
            'group_name' => $keep['trimmed_name'],
            'kept' => $keep,
            'removed' => []
        ];
        $current_group_index = count($results) - 1;

        // Usuń pozostałe duplikaty
        foreach ($group as $duplicate) {
            $dup_id = (int)$duplicate['id'];
            $keep_id = (int)$keep['id'];

            // Przenieś narzędzia z duplikatu do oryginalnej kategorii
            $update_tools = "UPDATE tools SET category_id = $keep_id WHERE category_id = $dup_id";
            $conn->query($update_tools);

            // Przenieś podkategorie
            $update_subcats = "UPDATE categories SET parent_id = $keep_id WHERE parent_id = $dup_id";
            $conn->query($update_subcats);

            // Usuń duplikat
            $delete_dup = "DELETE FROM categories WHERE id = $dup_id";
            if ($conn->query($delete_dup)) {
                $removed_count++;
                $results[$current_group_index]['removed'][] = $duplicate;
            }
        }
    }

    return [
        'count' => $removed_count,
        'details' => $results
    ];
}

// Funkcja do poprawy nazw kategorii (usunięcie zbędnych białych znaków)
function fix_category_whitespace() {
    global $conn;
    $fixed_count = 0;

    $sql = "SELECT id, name FROM categories";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $original_name = $row['name'];
            $trimmed_name = trim($original_name);

            // Jeśli nazwa różni się po przycięciu, zaktualizuj ją
            if ($original_name !== $trimmed_name) {
                $update_sql = "UPDATE categories SET name = '" .
                              $conn->real_escape_string($trimmed_name) .
                              "' WHERE id = " . (int)$row['id'];

                if ($conn->query($update_sql)) {
                    $fixed_count++;
                }
            }
        }
    }

    return $fixed_count;
}

// Znajdź duplikaty do wyświetlenia
$duplicate_categories = find_duplicate_categories();

// Akcje formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Usuwanie duplikatów
    if (isset($_POST['remove_duplicates']) && isset($_POST['confirm_removal'])) {
        $preview_mode = false;
        $result = remove_duplicate_categories($duplicate_categories);
        $removed_count = $result['count'];

        if ($removed_count > 0) {
            $success = "Usunięto pomyślnie $removed_count duplikatów kategorii.";
            // Log activity
            log_activity($_SESSION['user_id'], 'clean', 'categories', 'duplicates');

            // Odśwież listę duplikatów
            $duplicate_categories = find_duplicate_categories();
        } else {
            $errors[] = "Nie znaleziono duplikatów kategorii do usunięcia.";
        }
    }

    // Naprawa białych znaków
    if (isset($_POST['fix_whitespace'])) {
        $fixed_count = fix_category_whitespace();

        if ($fixed_count > 0) {
            $success = "Naprawiono $fixed_count kategorii z nadmiarowymi białymi znakami.";
            // Log activity
            log_activity($_SESSION['user_id'], 'fix', 'categories', 'whitespace');

            // Odśwież listę duplikatów
            $duplicate_categories = find_duplicate_categories();
        } else {
            $errors[] = "Nie znaleziono kategorii z nadmiarowymi białymi znakami.";
        }
    }
}

// Pobierz statystyki duplikatów kategorii
$duplicate_count = count($duplicate_categories);
$total_duplicates = 0;
foreach ($duplicate_categories as $group) {
    $total_duplicates += count($group) - 1; // odejmij 1, bo jedna kategoria zostanie zachowana
}
?>

<!-- Page Content -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin-bottom: 0;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>Usuwanie duplikatów kategorii</h2>
    </div>
    <div class="admin-card-body">
        <p>To narzędzie pomaga w utrzymaniu czystości systemu kategorii, wykrywając i usuwając duplikaty (kategorie o takich samych nazwach).</p>

        <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #4e73df; border-radius: 4px;">
            <strong>Statystyki:</strong>
            <?php if ($duplicate_count > 0): ?>
                <p>Znaleziono <?php echo $duplicate_count; ?> <?php echo $duplicate_count == 1 ? 'grupę' : ($duplicate_count < 5 ? 'grupy' : 'grup'); ?> duplikatów, łącznie <?php echo $total_duplicates; ?> <?php echo $total_duplicates == 1 ? 'duplikat' : ($total_duplicates < 5 ? 'duplikaty' : 'duplikatów'); ?>.</p>
            <?php else: ?>
                <p>Nie znaleziono duplikatów kategorii.</p>
            <?php endif; ?>
        </div>

        <!-- Narzędzia do naprawy kategorii -->
        <div style="margin-bottom: 20px;">
            <form method="post">
                <button type="submit" name="fix_whitespace" class="btn btn-primary">
                    <i class="fas fa-eraser"></i> Napraw białe znaki w nazwach kategorii
                </button>
            </form>
        </div>

        <?php if ($duplicate_count > 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <strong>Uwaga!</strong> Przy usuwaniu duplikatów obowiązują następujące zasady:
                <ul style="margin-top: 10px;">
                    <li>Kategoria o najniższym ID zostanie zachowana, a pozostałe usunięte.</li>
                    <li>Wszystkie narzędzia z usuniętych kategorii zostaną przypisane do zachowanej kategorii.</li>
                    <li>Wszystkie podkategorie zachowają relacje (zostaną przypisane do zachowanej kategorii).</li>
                </ul>
            </div>

            <h3>Znalezione duplikaty kategorii:</h3>

            <div class="table-responsive" style="margin-bottom: 20px;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Grupa</th>
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Kategoria nadrzędna</th>
                            <th>Narzędzia</th>
                            <th>Akcja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $group_number = 1;
                        foreach ($duplicate_categories as $group):
                            $first_row = true;
                            foreach ($group as $index => $category):
                                $is_kept = ($index === 0); // Pierwszy element w grupie zostanie zachowany
                        ?>
                            <tr class="<?php echo $is_kept ? 'bg-light' : ''; ?>">
                                <?php if ($first_row): $first_row = false; ?>
                                    <td rowspan="<?php echo count($group); ?>" style="vertical-align:middle; text-align:center; font-weight:bold;">
                                        <?php echo $group_number; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php echo $category['id']; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    <?php if ($is_kept): ?>
                                        <span class="badge bg-success">Zachowana</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Do usunięcia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($category['parent_name']); ?>
                                </td>
                                <td>
                                    <?php echo $category['tools_count']; ?>
                                </td>
                                <td>
                                    <a href="category-edit.php?id=<?php echo $category['id']; ?>" target="_blank" title="Edytuj kategorię">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php
                            endforeach;
                            $group_number++;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>

            <form method="post" onsubmit="return confirm('Czy na pewno chcesz usunąć duplikaty kategorii? Ta operacja jest nieodwracalna.')">
                <input type="hidden" name="confirm_removal" value="1">
                <div class="admin-form-actions">
                    <a href="categories.php" class="btn btn-secondary">Anuluj</a>
                    <button type="submit" name="remove_duplicates" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Usuń duplikaty
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="admin-form-actions">
                <a href="categories.php" class="btn btn-primary">Powrót do kategorii</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
