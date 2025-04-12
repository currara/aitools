<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

// Include header
include_once 'includes/header.php';

// Only admin can access this page
if (!is_admin()) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Nie masz uprawnień do korzystania z funkcji eksportu/importu.'
    ];
    header('Location: index.php');
    exit;
}

// Inicjalizacja zmiennych
$errors = [];
$success_message = '';

// Przetwarzanie żądania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eksport danych
    if (isset($_POST['export_data'])) {
        $data_type = isset($_POST['export_type']) ? $_POST['export_type'] : '';
        $format = isset($_POST['export_format']) ? $_POST['export_format'] : '';

        if (empty($data_type)) {
            $errors[] = 'Wybierz typ danych do eksportu.';
        }

        if (empty($format)) {
            $errors[] = 'Wybierz format eksportu.';
        }

        if (empty($errors)) {
            // Przygotuj nazwę pliku
            $timestamp = date('Y-m-d_H-i-s');
            $filename = 'export_' . $data_type . '_' . $timestamp . '.' . strtolower($format);

            // Zależnie od typu danych, pobierz odpowiednie dane
            $export_data = [];

            switch ($data_type) {
                case 'tools':
                    $query = "SELECT * FROM tools";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $export_data[] = $row;
                        }
                    }
                    break;

                case 'categories':
                    $query = "SELECT * FROM categories";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $export_data[] = $row;
                        }
                    }
                    break;

                default:
                    $errors[] = 'Nieprawidłowy typ danych.';
                    break;
            }

            if (empty($errors) && !empty($export_data)) {
                // Ustaw odpowiednie nagłówki dla pobierania pliku
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');

                // Generuj plik w odpowiednim formacie
                if ($format === 'json') {
                    // Format JSON
                    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    // Format CSV
                    $output = fopen('php://output', 'w');

                    // Dodaj nagłówki kolumn
                    if (!empty($export_data)) {
                        fputcsv($output, array_keys($export_data[0]));

                        // Dodaj dane
                        foreach ($export_data as $row) {
                            fputcsv($output, $row);
                        }
                    }

                    fclose($output);
                }

                // Log activity
                log_activity($_SESSION['user_id'], 'export', $data_type, null);

                exit;
            }
        }
    }

    // Import danych
    else if (isset($_POST['import_data'])) {
        $data_type = isset($_POST['import_type']) ? $_POST['import_type'] : '';

        if (empty($data_type)) {
            $errors[] = 'Wybierz typ danych do importu.';
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Wystąpił błąd podczas przesyłania pliku.';
        }

        if (empty($errors)) {
            $file_tmp = $_FILES['import_file']['tmp_name'];
            $file_name = $_FILES['import_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Sprawdź format pliku
            if (!in_array($file_ext, ['json', 'csv'])) {
                $errors[] = 'Nieprawidłowy format pliku. Obsługiwane formaty: JSON, CSV.';
            } else {
                // Przetwórz plik
                $import_data = [];

                if ($file_ext === 'json') {
                    // Odczytaj plik JSON
                    $json_content = file_get_contents($file_tmp);
                    $import_data = json_decode($json_content, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $errors[] = 'Nieprawidłowy format pliku JSON: ' . json_last_error_msg();
                    }
                } else {
                    // Odczytaj plik CSV
                    $csv_data = [];
                    if (($handle = fopen($file_tmp, 'r')) !== false) {
                        // Odczytaj nagłówki
                        $headers = fgetcsv($handle);

                        // Odczytaj dane
                        while (($row = fgetcsv($handle)) !== false) {
                            if (count($headers) === count($row)) {
                                $csv_data[] = array_combine($headers, $row);
                            }
                        }

                        fclose($handle);
                        $import_data = $csv_data;
                    } else {
                        $errors[] = 'Nie udało się odczytać pliku CSV.';
                    }
                }

                // Importuj dane
                if (empty($errors) && !empty($import_data)) {
                    $imported_count = 0;

                    if ($data_type === 'tools') {
                        // Importuj narzędzia
                        foreach ($import_data as $tool) {
                            // Sprawdź czy narzędzie już istnieje
                            $check_query = "SELECT id FROM tools WHERE name = ?";
                            $check_stmt = $conn->prepare($check_query);
                            $check_stmt->bind_param('s', $tool['name']);
                            $check_stmt->execute();
                            $check_result = $check_stmt->get_result();

                            if ($check_result->num_rows === 0) {
                                // Narzędzie nie istnieje, dodaj nowe
                                $stmt = $conn->prepare("INSERT INTO tools (name, slug, description, website_url, category_id, pricing_type, featured, new_launch) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                                $name = $tool['name'];
                                $slug = $tool['slug'] ?? create_slug($name);
                                $description = $tool['description'] ?? '';
                                $website_url = $tool['website_url'] ?? '';
                                $category_id = $tool['category_id'] ?? 1;
                                $pricing_type = $tool['pricing_type'] ?? 'free';
                                $featured = $tool['featured'] ?? 0;
                                $new_launch = $tool['new_launch'] ?? 0;

                                $stmt->bind_param('ssssisii', $name, $slug, $description, $website_url, $category_id, $pricing_type, $featured, $new_launch);

                                if ($stmt->execute()) {
                                    $imported_count++;
                                }
                            }
                        }
                    } else if ($data_type === 'categories') {
                        // Importuj kategorie
                        foreach ($import_data as $category) {
                            // Sprawdź czy kategoria już istnieje
                            $check_query = "SELECT id FROM categories WHERE name = ?";
                            $check_stmt = $conn->prepare($check_query);
                            $check_stmt->bind_param('s', $category['name']);
                            $check_stmt->execute();
                            $check_result = $check_stmt->get_result();

                            if ($check_result->num_rows === 0) {
                                // Kategoria nie istnieje, dodaj nową
                                $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, parent_id) VALUES (?, ?, ?, ?)");

                                $name = $category['name'];
                                $slug = $category['slug'] ?? create_slug($name);
                                $description = $category['description'] ?? '';
                                $parent_id = $category['parent_id'] ?? 0;

                                $stmt->bind_param('sssi', $name, $slug, $description, $parent_id);

                                if ($stmt->execute()) {
                                    $imported_count++;
                                }
                            }
                        }
                    }

                    // Log activity
                    log_activity($_SESSION['user_id'], 'import', $data_type, null);

                    $success_message = "Pomyślnie zaimportowano $imported_count " . ($data_type === 'tools' ? 'narzędzi' : 'kategorii') . '.';
                }
            }
        }
    }
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

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>Eksport i Import Danych</h2>
    </div>
    <div class="admin-card-body">
        <div class="admin-tabs">
            <div class="admin-tab active" data-tab="tab-export">Eksport</div>
            <div class="admin-tab" data-tab="tab-import">Import</div>
        </div>

        <!-- Eksport -->
        <div id="tab-export" class="admin-tab-content active">
            <div class="admin-form-row">
                <form method="post">
                    <div class="admin-form-group">
                        <label for="export_type" class="admin-form-label">Typ danych</label>
                        <select id="export_type" name="export_type" class="admin-form-select" required>
                            <option value="">-- Wybierz typ danych --</option>
                            <option value="tools">Narzędzia</option>
                            <option value="categories">Kategorie</option>
                        </select>
                    </div>

                    <div class="admin-form-group">
                        <label for="export_format" class="admin-form-label">Format pliku</label>
                        <select id="export_format" name="export_format" class="admin-form-select" required>
                            <option value="">-- Wybierz format --</option>
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" name="export_data" class="btn btn-primary">
                            <i class="fas fa-download"></i> Eksportuj dane
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import -->
        <div id="tab-import" class="admin-tab-content">
            <div class="admin-form-row">
                <form method="post" enctype="multipart/form-data">
                    <div class="admin-form-group">
                        <label for="import_type" class="admin-form-label">Typ danych</label>
                        <select id="import_type" name="import_type" class="admin-form-select" required>
                            <option value="">-- Wybierz typ danych --</option>
                            <option value="tools">Narzędzia</option>
                            <option value="categories">Kategorie</option>
                        </select>
                    </div>

                    <div class="admin-form-group">
                        <label for="import_file" class="admin-form-label">Plik do importu</label>
                        <input type="file" id="import_file" name="import_file" class="admin-form-file-input" required accept=".json,.csv">
                        <div class="admin-form-help">
                            Obsługiwane formaty: JSON, CSV. Maksymalny rozmiar: 5MB.
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Uwaga!</strong> Import danych może nadpisać istniejące dane lub powodować konflikty. Zalecane jest wykonanie kopii zapasowej przed importem.
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" name="import_data" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Importuj dane
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Obsługa kart
        const tabs = document.querySelectorAll('.admin-tab');
        const tabContents = document.querySelectorAll('.admin-tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');

                // Usuń aktywną klasę ze wszystkich kart
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // Aktywuj wybraną kartę
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    });
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
