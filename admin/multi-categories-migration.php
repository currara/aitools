<?php
/**
 * Skrypt migracyjny dla obsługi wielu kategorii dla narzędzi
 */

// Include header
include_once 'includes/header.php';
require_once 'tool-categories-functions.php';

// Uruchom migrację
$migration_result = false;
$migration_errors = [];

if (isset($_POST['run_migration']) && $_POST['run_migration'] === '1') {
    // Próba utworzenia tabeli
    if (!ensure_tool_categories_table_exists()) {
        $migration_errors[] = 'Nie udało się utworzyć tabeli tool_categories. Sprawdź uprawnienia bazy danych.';
    } else {
        // Próba migracji danych
        if (migrate_single_to_multi_categories()) {
            $migration_result = true;
        } else {
            $migration_errors[] = 'Nie udało się zmigrować danych z pojedynczej kategorii do wielu kategorii.';
        }
    }
}

// Sprawdź liczbę narzędzi i kategorii
$tools_count = 0;
$categories_count = 0;
$tool_categories_count = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM tools");
if ($result) {
    $row = $result->fetch_assoc();
    $tools_count = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM categories");
if ($result) {
    $row = $result->fetch_assoc();
    $categories_count = $row['count'];
}

// Sprawdź czy tabela tool_categories już istnieje
$result = $conn->query("SHOW TABLES LIKE 'tool_categories'");
$table_exists = ($result && $result->num_rows > 0);

if ($table_exists) {
    $result = $conn->query("SELECT COUNT(*) as count FROM tool_categories");
    if ($result) {
        $row = $result->fetch_assoc();
        $tool_categories_count = $row['count'];
    }
}
?>

<div class="admin-content">
    <h1>Migracja do wielu kategorii dla narzędzi</h1>

    <div class="admin-card">
        <h2>Informacje o migracji</h2>
        <p>
            Ten skrypt umożliwia migrację z systemu pojedynczej kategorii dla narzędzia do systemu wielu kategorii.
            Pozwoli to na przypisywanie narzędzi do wielu kategorii jednocześnie.
        </p>

        <?php if ($migration_result): ?>
            <div class="alert alert-success">
                <p><strong>Migracja zakończona pomyślnie!</strong></p>
                <p>Wszystkie narzędzia zostały zmigrowane do nowego systemu wielu kategorii.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($migration_errors)): ?>
            <div class="alert alert-danger">
                <p><strong>Wystąpiły błędy podczas migracji:</strong></p>
                <ul>
                    <?php foreach ($migration_errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="migration-status">
            <h3>Status systemu</h3>
            <ul>
                <li>Liczba narzędzi: <strong><?php echo $tools_count; ?></strong></li>
                <li>Liczba kategorii: <strong><?php echo $categories_count; ?></strong></li>
                <li>Tabela wielu kategorii: <strong><?php echo $table_exists ? 'Istnieje' : 'Nie istnieje'; ?></strong></li>
                <?php if ($table_exists): ?>
                    <li>Powiązania narzędzi z kategoriami: <strong><?php echo $tool_categories_count; ?></strong></li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if (!$migration_result): ?>
            <form method="post" class="migration-form">
                <input type="hidden" name="run_migration" value="1">

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Uruchom migrację</button>
                </div>
            </form>
        <?php else: ?>
            <div class="form-actions">
                <a href="tools.php" class="btn btn-secondary">Wróć do listy narzędzi</a>
                <a href="tool-edit.php" class="btn btn-primary">Dodaj nowe narzędzie</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <h2>Szczegóły techniczne</h2>
        <p>
            Migracja wykona następujące działania:
        </p>
        <ol>
            <li>Utworzy nową tabelę <code>tool_categories</code> w bazie danych</li>
            <li>Przeniesie istniejące powiązania kategorii z tabeli <code>tools</code> do nowej tabeli</li>
            <li>Umożliwi przypisywanie narzędzi do wielu kategorii w formularzu edycji</li>
        </ol>
        <p>
            <strong>Uwaga:</strong> Istniejące kategorie narzędzi nie zostaną utracone podczas migracji.
            Wszystkie aktualne powiązania zostaną zachowane.
        </p>
    </div>
</div>

<style>
    .migration-status {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .migration-status ul {
        list-style-type: none;
        padding: 0;
    }

    .migration-status li {
        margin-bottom: 8px;
        font-size: 15px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }

    .alert-success {
        background-color: #d4edda;
        border-left: 5px solid #28a745;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        border-left: 5px solid #dc3545;
        color: #721c24;
    }

    .form-actions {
        margin-top: 20px;
    }

    .dark-theme .migration-status {
        background-color: #2a2a2a;
        color: #e0e0e0;
    }

    .dark-theme .alert-success {
        background-color: #1e3a2d;
        color: #8fd4a5;
        border-color: #28a745;
    }

    .dark-theme .alert-danger {
        background-color: #3a1e1e;
        color: #e09999;
        border-color: #dc3545;
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
