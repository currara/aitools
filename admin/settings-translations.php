<?php
// Include header
include_once 'includes/header.php';

// Sprawdź czy tabela languages istnieje
$table_check = $conn->query("SHOW TABLES LIKE 'languages'");
if (!$table_check || $table_check->num_rows === 0) {
    // Tabela nie istnieje, utwórz ją
    $create_table_sql = "CREATE TABLE IF NOT EXISTS languages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(10) NOT NULL UNIQUE,
        name VARCHAR(50) NOT NULL,
        native_name VARCHAR(50) NOT NULL,
        text_direction ENUM('ltr', 'rtl') NOT NULL DEFAULT 'ltr',
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if ($conn->query($create_table_sql) === TRUE) {
        // Dodaj domyślne języki
        $default_languages = [
            ['en', 'English', 'English', 'ltr'],
            ['pl', 'Polish', 'Polski', 'ltr'],
            ['es', 'Spanish', 'Español', 'ltr'],
            ['ru', 'Russian', 'Русский', 'ltr'],
            ['de', 'German', 'Deutsch', 'ltr']
        ];

        foreach ($default_languages as $lang) {
            $insert_sql = "INSERT IGNORE INTO languages (code, name, native_name, text_direction)
                         VALUES ('$lang[0]', '$lang[1]', '$lang[2]', '$lang[3]')";
            $conn->query($insert_sql);
        }
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Błąd podczas tworzenia tabeli languages: ' . $conn->error
        ];
    }
}

// Only admin can access settings
if (!is_admin()) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Nie masz uprawnień do przeglądania tej strony.'
    ];
    header('Location: index.php');
    exit;
}

// Initialize variables
$errors = [];
$success_message = '';
$languages = $available_languages;

// Handle language action (add, edit, disable, enable, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new language
    if (isset($_POST['add_language'])) {
        $language_code = isset($_POST['language_code']) ? trim($_POST['language_code']) : '';
        $language_name = isset($_POST['language_name']) ? trim($_POST['language_name']) : '';
        $native_name = isset($_POST['native_name']) ? trim($_POST['native_name']) : '';
        $text_direction = isset($_POST['text_direction']) ? trim($_POST['text_direction']) : 'ltr';

        // Validate required fields
        if (empty($language_code)) {
            $errors[] = 'Kod języka jest wymagany.';
        } else if (strlen($language_code) !== 2) {
            $errors[] = 'Kod języka musi składać się z 2 znaków.';
        }

        if (empty($language_name)) {
            $errors[] = 'Nazwa języka jest wymagana.';
        }

        if (empty($native_name)) {
            $errors[] = 'Nazwa natywna języka jest wymagana.';
        }

        // Check if language already exists
        if (!empty($language_code) && isset($languages[$language_code])) {
            $errors[] = 'Język o podanym kodzie już istnieje.';
        }

        // Add language if no errors
        if (empty($errors)) {
            $result = add_language($language_code, $language_name, $native_name, $text_direction);

            if ($result['success']) {
                log_activity($_SESSION['user_id'], 'create', 'language', $language_code);
                $success_message = 'Nowy język został dodany pomyślnie.';

                // Refresh languages list
                $languages = get_available_languages();
            } else {
                $errors[] = $result['message'];
            }
        }
    }

    // Update language
    else if (isset($_POST['update_language'])) {
        $language_code = isset($_POST['edit_language_code']) ? trim($_POST['edit_language_code']) : '';
        $language_name = isset($_POST['edit_language_name']) ? trim($_POST['edit_language_name']) : '';
        $native_name = isset($_POST['edit_native_name']) ? trim($_POST['edit_native_name']) : '';
        $text_direction = isset($_POST['edit_text_direction']) ? trim($_POST['edit_text_direction']) : 'ltr';

        // Validate required fields
        if (empty($language_code)) {
            $errors[] = 'Kod języka jest wymagany.';
        }

        if (empty($language_name)) {
            $errors[] = 'Nazwa języka jest wymagana.';
        }

        if (empty($native_name)) {
            $errors[] = 'Nazwa natywna języka jest wymagana.';
        }

        // Update language if no errors
        if (empty($errors)) {
            $result = update_language($language_code, $language_name, $native_name, $text_direction);

            if ($result['success']) {
                log_activity($_SESSION['user_id'], 'update', 'language', $language_code);
                $success_message = 'Język został zaktualizowany pomyślnie.';

                // Refresh languages list
                $languages = get_available_languages();
            } else {
                $errors[] = $result['message'];
            }
        }
    }

    // Enable/Disable language
    else if (isset($_POST['toggle_language'])) {
        $language_code = isset($_POST['language_code']) ? trim($_POST['language_code']) : '';
        $action = isset($_POST['action']) ? trim($_POST['action']) : '';

        if (!empty($language_code) && in_array($action, ['enable', 'disable'])) {
            $result = toggle_language($language_code, $action === 'enable');

            if ($result['success']) {
                log_activity($_SESSION['user_id'], $action, 'language', $language_code);
                $success_message = 'Status języka został zmieniony pomyślnie.';

                // Refresh languages list
                $languages = get_available_languages();
            } else {
                $errors[] = $result['message'];
            }
        }
    }

    // Delete language
    else if (isset($_POST['delete_language'])) {
        $language_code = isset($_POST['language_code']) ? trim($_POST['language_code']) : '';

        // Cannot delete default language
        if ($language_code === $default_language) {
            $errors[] = 'Nie można usunąć domyślnego języka.';
        }

        if (empty($errors) && !empty($language_code)) {
            $result = delete_language($language_code);

            if ($result['success']) {
                log_activity($_SESSION['user_id'], 'delete', 'language', $language_code);
                $success_message = 'Język został usunięty pomyślnie.';

                // Refresh languages list
                $languages = get_available_languages();
            } else {
                $errors[] = $result['message'];
            }
        }
    }

    // Update default language
    else if (isset($_POST['update_default'])) {
        $new_default = isset($_POST['default_language']) ? trim($_POST['default_language']) : '';

        if (!empty($new_default) && isset($languages[$new_default])) {
            $result = set_default_language($new_default);

            if ($result['success']) {
                log_activity($_SESSION['user_id'], 'update', 'setting', 'default_language');
                $success_message = 'Domyślny język został zmieniony pomyślnie.';

                // Refresh variables
                $default_language = $new_default;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

// Get available languages
$languages = get_available_languages(true); // Include disabled languages
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

<!-- Tabs Navigation -->
<div class="admin-tabs">
    <div class="admin-tab active" data-tab="tab-languages">Języki</div>
    <div class="admin-tab" data-tab="tab-translations">Teksty tłumaczeń</div>
    <div class="admin-tab" data-tab="tab-settings">Ustawienia</div>
</div>

<!-- Tab Content -->
<div id="tab-languages" class="admin-tab-content active">
    <div style="margin-bottom: 20px;">
        <button type="button" class="btn btn-primary" id="add-language-btn">Dodaj nowy język</button>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Kod</th>
                <th>Nazwa</th>
                <th>Nazwa natywna</th>
                <th>Kierunek tekstu</th>
                <th>Status</th>
                <th>Domyślny</th>
                <th width="150">Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($languages as $code => $language): ?>
                <tr>
                    <td><?php echo htmlspecialchars($code); ?></td>
                    <td><?php echo htmlspecialchars($language['name']); ?></td>
                    <td><?php echo htmlspecialchars($language['native_name']); ?></td>
                    <td><?php echo isset($language['text_direction']) && $language['text_direction'] === 'rtl' ? 'Od prawej do lewej' : 'Od lewej do prawej'; ?></td>
                    <td>
                        <?php if (isset($language['active']) && !$language['active']): ?>
                            <span class="badge bg-danger">Nieaktywny</span>
                        <?php else: ?>
                            <span class="badge bg-success">Aktywny</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($code === $default_language): ?>
                            <span class="badge bg-primary">Domyślny</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <button type="button" class="action-icon edit-language"
                            data-code="<?php echo $code; ?>"
                            data-name="<?php echo htmlspecialchars($language['name']); ?>"
                            data-native="<?php echo htmlspecialchars($language['native_name']); ?>"
                            data-dir="<?php echo isset($language['text_direction']) ? $language['text_direction'] : 'ltr'; ?>"
                            title="Edytuj">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($code !== $default_language): // Cannot toggle default language
                        ?>
                            <?php if (isset($language['active']) && !$language['active']): ?>
                                <form method="post" style="display: inline-block;">
                                    <input type="hidden" name="language_code" value="<?php echo $code; ?>">
                                    <input type="hidden" name="action" value="enable">
                                    <button type="submit" name="toggle_language" class="action-icon" title="Włącz">
                                        <i class="fas fa-toggle-off"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display: inline-block;">
                                    <input type="hidden" name="language_code" value="<?php echo $code; ?>">
                                    <input type="hidden" name="action" value="disable">
                                    <button type="submit" name="toggle_language" class="action-icon" title="Wyłącz">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="post" style="display: inline-block;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten język? Wszystkie tłumaczenia zostaną utracone.');">
                                <input type="hidden" name="language_code" value="<?php echo $code; ?>">
                                <button type="submit" name="delete_language" class="action-icon" title="Usuń">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add Language Modal (hidden by default) -->
    <div id="add-language-modal" class="admin-modal">
        <div class="admin-modal-content">
            <span class="admin-modal-close">&times;</span>
            <h3>Dodaj nowy język</h3>
            <form method="post">
                <div class="admin-form-group">
                    <label for="language_code" class="admin-form-label">Kod języka (2 znaki)*</label>
                    <input type="text" id="language_code" name="language_code" class="admin-form-input" required maxlength="2" placeholder="np. en, pl, de">
                </div>

                <div class="admin-form-group">
                    <label for="language_name" class="admin-form-label">Nazwa języka*</label>
                    <input type="text" id="language_name" name="language_name" class="admin-form-input" required placeholder="np. Angielski, Polski, Niemiecki">
                </div>

                <div class="admin-form-group">
                    <label for="native_name" class="admin-form-label">Nazwa natywna*</label>
                    <input type="text" id="native_name" name="native_name" class="admin-form-input" required placeholder="np. English, Polski, Deutsch">
                </div>

                <div class="admin-form-group">
                    <label for="text_direction" class="admin-form-label">Kierunek tekstu</label>
                    <select id="text_direction" name="text_direction" class="admin-form-select">
                        <option value="ltr">Od lewej do prawej (LTR)</option>
                        <option value="rtl">Od prawej do lewej (RTL)</option>
                    </select>
                </div>

                <div class="admin-form-actions">
                    <button type="button" class="btn btn-secondary admin-modal-cancel">Anuluj</button>
                    <button type="submit" name="add_language" class="btn btn-primary">Dodaj język</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Language Modal (hidden by default) -->
    <div id="edit-language-modal" class="admin-modal">
        <div class="admin-modal-content">
            <span class="admin-modal-close">&times;</span>
            <h3>Edytuj język</h3>
            <form method="post">
                <input type="hidden" id="edit_language_code" name="edit_language_code">

                <div class="admin-form-group">
                    <label for="edit_language_name" class="admin-form-label">Nazwa języka*</label>
                    <input type="text" id="edit_language_name" name="edit_language_name" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label for="edit_native_name" class="admin-form-label">Nazwa natywna*</label>
                    <input type="text" id="edit_native_name" name="edit_native_name" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label for="edit_text_direction" class="admin-form-label">Kierunek tekstu</label>
                    <select id="edit_text_direction" name="edit_text_direction" class="admin-form-select">
                        <option value="ltr">Od lewej do prawej (LTR)</option>
                        <option value="rtl">Od prawej do lewej (RTL)</option>
                    </select>
                </div>

                <div class="admin-form-actions">
                    <button type="button" class="btn btn-secondary admin-modal-cancel">Anuluj</button>
                    <button type="submit" name="update_language" class="btn btn-primary">Zapisz zmiany</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="tab-translations" class="admin-tab-content">
    <div class="admin-form-row">
        <div class="admin-form-group">
            <label for="translation_language" class="admin-form-label">Wybierz język</label>
            <select id="translation_language" class="admin-form-select">
                <?php foreach ($languages as $code => $language): ?>
                    <?php if (isset($language['active']) && !$language['active']) continue; ?>
                    <option value="<?php echo $code; ?>"><?php echo htmlspecialchars($language['name']); ?> (<?php echo htmlspecialchars($language['native_name']); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="admin-form-group">
            <label for="translation_search" class="admin-form-label">Wyszukaj tekst</label>
            <input type="text" id="translation_search" class="admin-form-input" placeholder="Szukaj...">
        </div>

        <div class="admin-form-group">
            <a href="language-editor.php" class="btn btn-primary">Edytor plików językowych</a>
            <div class="admin-form-help">
                Użyj edytora plików językowych, aby bezpośrednio edytować pliki językowe (.php) w systemie.
            </div>
        </div>
    </div>

    <div id="translation-editor">
        <div class="translation-loading">Ładowanie tekstów tłumaczeń...</div>
        <form method="post" id="translation-form" style="display: none;">
            <div class="translation-fields"></div>

            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Zapisz tłumaczenia</button>
            </div>
        </form>
    </div>
</div>

<div id="tab-settings" class="admin-tab-content">
    <div class="admin-form-row">
        <form method="post">
            <div class="admin-form-group">
                <label for="default_language" class="admin-form-label">Domyślny język</label>
                <select id="default_language" name="default_language" class="admin-form-select">
                    <?php foreach ($languages as $code => $language): ?>
                        <?php if (isset($language['active']) && !$language['active']) continue; ?>
                        <option value="<?php echo $code; ?>" <?php echo ($code === $default_language) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language['name']); ?> (<?php echo htmlspecialchars($language['native_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="admin-form-help">
                    Język, który będzie używany jako domyślny dla strony.
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" name="update_default" class="btn btn-primary">Zapisz ustawienia</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add Language Modal
        const addLanguageBtn = document.getElementById('add-language-btn');
        const addLanguageModal = document.getElementById('add-language-modal');
        const editLanguageModal = document.getElementById('edit-language-modal');
        const closeButtons = document.querySelectorAll('.admin-modal-close, .admin-modal-cancel');

        // Open Add Language Modal
        addLanguageBtn.addEventListener('click', function() {
            addLanguageModal.style.display = 'block';
        });

        // Close Modals
        closeButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                addLanguageModal.style.display = 'none';
                editLanguageModal.style.display = 'none';
            });
        });

        // Edit Language Button Handlers
        const editButtons = document.querySelectorAll('.edit-language');
        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                const name = this.getAttribute('data-name');
                const native = this.getAttribute('data-native');
                const dir = this.getAttribute('data-dir');

                document.getElementById('edit_language_code').value = code;
                document.getElementById('edit_language_name').value = name;
                document.getElementById('edit_native_name').value = native;
                document.getElementById('edit_text_direction').value = dir;

                editLanguageModal.style.display = 'block';
            });
        });

        // Close modal if clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === addLanguageModal) {
                addLanguageModal.style.display = 'none';
            }
            if (event.target === editLanguageModal) {
                editLanguageModal.style.display = 'none';
            }
        });

        // Translation UI Logic
        const translationLanguage = document.getElementById('translation_language');
        const translationSearch = document.getElementById('translation_search');
        const translationForm = document.getElementById('translation-form');
        const translationFields = document.querySelector('.translation-fields');
        const translationLoading = document.querySelector('.translation-loading');

        // Load translations when language changes
        if (translationLanguage) {
            translationLanguage.addEventListener('change', loadTranslations);

            // Initial load
            loadTranslations();
        }

        // Search in translations
        if (translationSearch) {
            translationSearch.addEventListener('input', filterTranslations);
        }

        function loadTranslations() {
            const language = translationLanguage.value;
            translationForm.style.display = 'none';
            translationLoading.style.display = 'block';

            // In a real implementation, this would be an AJAX call to get translations
            // For now, we'll simulate it with a timeout
            setTimeout(function() {
                // In a real implementation, this would populate the form with actual translations
                translationFields.innerHTML = `
                    <div class="translation-notice">
                        <p>Funkcja edycji tekstów tłumaczeń jest w trakcie implementacji.</p>
                        <p>W przyszłej wersji umożliwi to edycję wszystkich tekstów interfejsu w wybranym języku.</p>
                    </div>
                `;

                translationLoading.style.display = 'none';
                translationForm.style.display = 'block';
            }, 1000);
        }

        function filterTranslations() {
            const searchText = translationSearch.value.toLowerCase();
            // In a real implementation, this would filter the displayed translations
        }
    });
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
