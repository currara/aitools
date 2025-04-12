<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

// Include header
include_once 'includes/header.php';

// Only admin can access language editor
if (!is_admin()) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Nie masz uprawnień do edycji plików językowych.'
    ];
    header('Location: index.php');
    exit;
}

// Initialize variables
$errors = [];
$success_message = '';
$languages = $available_languages;
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : $default_language;
$lang_file_content = '';
$lang_file_path = '';

// Validate selected language
if (!array_key_exists($current_lang, $languages)) {
    $current_lang = $default_language;
}

// Sprawdź, czy żądane jest przywrócenie kopii zapasowej
if (isset($_GET['restore']) && !empty($_GET['restore'])) {
    $backup_filename = $_GET['restore'];
    $backup_file = '../languages/backups/' . $backup_filename;

    // Zabezpieczenia - sprawdź, czy nazwa pliku jest prawidłowa
    if (preg_match('/^[a-z]{2}_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.php\.bak$/', $backup_filename) && file_exists($backup_file)) {
        // Pobierz kod języka z nazwy pliku
        $lang_code = substr($backup_filename, 0, 2);

        if ($lang_code === $current_lang) {
            $lang_file_to_restore = '../languages/' . $current_lang . '.php';

            // Utwórz kopię zapasową aktualnego pliku przed przywróceniem
            if (file_exists($lang_file_to_restore)) {
                $backup_dir = '../languages/backups/';
                $pre_restore_backup = $backup_dir . $current_lang . '_pre_restore_' . date('Y-m-d_H-i-s') . '.php.bak';

                if (!copy($lang_file_to_restore, $pre_restore_backup)) {
                    $errors[] = 'Nie udało się utworzyć kopii zapasowej aktualnego pliku przed przywróceniem.';
                }
            }

            // Przywróć kopię zapasową
            if (empty($errors)) {
                if (copy($backup_file, $lang_file_to_restore)) {
                    // Log activity
                    log_activity($_SESSION['user_id'], 'restore', 'language_file', $current_lang);

                    $success_message = 'Kopia zapasowa została przywrócona pomyślnie.';
                } else {
                    $errors[] = 'Nie udało się przywrócić kopii zapasowej.';
                }
            }
        } else {
            $errors[] = 'Kod języka w nazwie pliku nie pasuje do wybranego języka.';
        }
    } else {
        $errors[] = 'Nieprawidłowa nazwa pliku kopii zapasowej lub plik nie istnieje.';
    }
}

// Get language file path
$lang_file_path = '../languages/' . $current_lang . '.php';

// Check if file exists
if (!file_exists($lang_file_path)) {
    $errors[] = "Plik języka '$current_lang' nie istnieje.";
} else {
    // Read language file content
    $lang_file_content = file_get_contents($lang_file_path);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save language file content
    if (isset($_POST['save_lang_file'])) {
        $file_content = isset($_POST['lang_file_content']) ? $_POST['lang_file_content'] : '';
        $selected_lang = isset($_POST['selected_lang']) ? $_POST['selected_lang'] : '';

        // Validate language
        if (!array_key_exists($selected_lang, $languages)) {
            $errors[] = 'Nieprawidłowy kod języka.';
        }

        // Validate file content
        if (empty($file_content)) {
            $errors[] = 'Zawartość pliku nie może być pusta.';
        }

        // Basic syntax validation - check for PHP opening tag
        if (strpos($file_content, '<?php') === false) {
            $errors[] = 'Plik musi zawierać tag otwarcia PHP (<?php).';
        }

        // Basic syntax validation - check for $lang array
        if (strpos($file_content, '$lang = [') === false) {
            $errors[] = 'Plik musi zawierać definicję tablicy $lang.';
        }

        // Save file if no errors
        if (empty($errors)) {
            $lang_file_to_save = '../languages/' . $selected_lang . '.php';

            // Utwórz kopię zapasową pliku przed zapisaniem zmian
            if (file_exists($lang_file_to_save)) {
                $backup_dir = '../languages/backups/';

                // Upewnij się, że katalog na kopie zapasowe istnieje
                if (!file_exists($backup_dir)) {
                    if (!mkdir($backup_dir, 0755, true)) {
                        $errors[] = 'Nie udało się utworzyć katalogu na kopie zapasowe.';
                    }
                }

                if (empty($errors)) {
                    // Nazwa kopii zapasowej zawiera datę i czas
                    $backup_filename = $backup_dir . $selected_lang . '_' . date('Y-m-d_H-i-s') . '.php.bak';

                    // Kopiuj oryginalny plik do kopii zapasowej
                    if (!copy($lang_file_to_save, $backup_filename)) {
                        $errors[] = 'Nie udało się utworzyć kopii zapasowej pliku.';
                    }
                }
            }

            // Zapisz nową zawartość pliku
            if (empty($errors)) {
                if (file_put_contents($lang_file_to_save, $file_content)) {
                    // Log activity
                    log_activity($_SESSION['user_id'], 'update', 'language_file', $selected_lang);

                    $success_message = 'Plik języka został zapisany pomyślnie (z kopią zapasową).';

                    // Refresh file content
                    $lang_file_content = $file_content;
                    $current_lang = $selected_lang;
                } else {
                    $errors[] = 'Nie udało się zapisać pliku języka. Sprawdź uprawnienia.';
                }
            }
        }
    }

    // Create new language file
    else if (isset($_POST['create_lang_file'])) {
        $new_lang_code = isset($_POST['new_lang_code']) ? trim($_POST['new_lang_code']) : '';
        $template_lang = isset($_POST['template_lang']) ? trim($_POST['template_lang']) : '';

        // Validate language code
        if (empty($new_lang_code)) {
            $errors[] = 'Kod języka jest wymagany.';
        } else if (strlen($new_lang_code) !== 2) {
            $errors[] = 'Kod języka musi składać się z 2 znaków.';
        }

        // Check if language file already exists
        $new_lang_file_path = '../languages/' . $new_lang_code . '.php';
        if (file_exists($new_lang_file_path)) {
            $errors[] = "Plik języka '$new_lang_code' już istnieje.";
        }

        // Validate template language
        if (empty($template_lang) || !array_key_exists($template_lang, $languages)) {
            $errors[] = 'Wybierz prawidłowy język szablonu.';
        }

        // Create new language file if no errors
        if (empty($errors)) {
            $template_file_path = '../languages/' . $template_lang . '.php';

            if (file_exists($template_file_path)) {
                $template_content = file_get_contents($template_file_path);

                // Update comment in the file
                $native_name = $new_lang_code; // Default to code if no native name available

                // Common language names
                $common_names = [
                    'ar' => 'Arabic',
                    'cs' => 'Czech',
                    'da' => 'Danish',
                    'de' => 'German',
                    'el' => 'Greek',
                    'en' => 'English',
                    'es' => 'Spanish',
                    'fi' => 'Finnish',
                    'fr' => 'French',
                    'he' => 'Hebrew',
                    'hi' => 'Hindi',
                    'hu' => 'Hungarian',
                    'id' => 'Indonesian',
                    'it' => 'Italian',
                    'ja' => 'Japanese',
                    'ko' => 'Korean',
                    'nl' => 'Dutch',
                    'no' => 'Norwegian',
                    'pl' => 'Polish',
                    'pt' => 'Portuguese',
                    'ro' => 'Romanian',
                    'ru' => 'Russian',
                    'sv' => 'Swedish',
                    'th' => 'Thai',
                    'tr' => 'Turkish',
                    'uk' => 'Ukrainian',
                    'vi' => 'Vietnamese',
                    'zh' => 'Chinese'
                ];

                if (isset($common_names[$new_lang_code])) {
                    $native_name = $common_names[$new_lang_code];
                }

                // Replace the comment
                $template_content = preg_replace('/\/\*\*\n \* .+ Language File\n \*\//', "/**\n * $native_name Language File\n */", $template_content);

                if (file_put_contents($new_lang_file_path, $template_content)) {
                    // Log activity
                    log_activity($_SESSION['user_id'], 'create', 'language_file', $new_lang_code);

                    $success_message = "Nowy plik języka '$new_lang_code' został utworzony pomyślnie.";

                    // Switch to the new language file
                    $current_lang = $new_lang_code;
                    $lang_file_content = $template_content;
                } else {
                    $errors[] = 'Nie udało się utworzyć pliku języka. Sprawdź uprawnienia.';
                }
            } else {
                $errors[] = "Plik szablonu '$template_lang' nie istnieje.";
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
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <h2>Edycja plików językowych</h2>
        <button type="button" class="btn btn-primary btn-sm" id="create-lang-file-btn">Utwórz nowy plik języka</button>
    </div>
    <div class="admin-card-body">
        <div class="language-file-selector" style="margin-bottom: 20px;">
            <form method="get" class="d-flex align-items-center">
                <label for="lang" style="margin-right: 10px; margin-bottom: 0;">Wybierz język:</label>
                <select id="lang" name="lang" class="admin-form-select" style="width: 250px; margin-right: 10px;">
                    <?php foreach ($languages as $code => $language): ?>
                        <option value="<?php echo $code; ?>" <?php echo ($code === $current_lang) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language['name']); ?> (<?php echo htmlspecialchars($language['native_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Wczytaj</button>
            </form>
        </div>

        <!-- Display backups if they exist -->
        <?php
        $backup_dir = '../languages/backups/';
        $backup_files = [];
        $pattern = $current_lang . '_*.php.bak';

        if (file_exists($backup_dir)) {
            $backup_files = glob($backup_dir . $pattern);
            rsort($backup_files); // Najnowsze na górze
        }

        if (!empty($backup_files)):
        ?>
        <div class="backup-files-section" style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
            <h4>Kopie zapasowe pliku <?php echo $current_lang; ?>.php:</h4>
            <div class="backup-files-list" style="max-height: 200px; overflow-y: auto; margin-top: 10px;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Data i czas</th>
                            <th>Rozmiar</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backup_files as $backup_file):
                            $filename = basename($backup_file);
                            $datetime_str = str_replace([$current_lang . '_', '.php.bak'], '', $filename);
                            $datetime = DateTime::createFromFormat('Y-m-d_H-i-s', $datetime_str);
                            $formatted_date = $datetime ? $datetime->format('d.m.Y H:i:s') : $datetime_str;
                            $filesize = filesize($backup_file);
                            $filesize_str = $filesize < 1024 ? $filesize . ' B' :
                                            ($filesize < 1048576 ? round($filesize / 1024, 2) . ' KB' :
                                             round($filesize / 1048576, 2) . ' MB');
                        ?>
                        <tr>
                            <td><?php echo $formatted_date; ?></td>
                            <td><?php echo $filesize_str; ?></td>
                            <td>
                                <a href="?lang=<?php echo $current_lang; ?>&amp;restore=<?php echo urlencode($filename); ?>"
                                   onclick="return confirm('Czy na pewno chcesz przywrócić tę kopię zapasową?');"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-undo"></i> Przywróć
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($lang_file_content)): ?>
            <form method="post" id="language-file-form">
                <input type="hidden" name="selected_lang" value="<?php echo htmlspecialchars($current_lang); ?>">

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="lang_file_content">Edycja pliku: <?php echo htmlspecialchars($lang_file_path); ?></label>
                        <div>
                            <span id="line-number" class="text-muted">Linia: 1, Kolumna: 1</span>
                        </div>
                    </div>
                    <textarea id="lang_file_content" name="lang_file_content" class="admin-form-textarea code-editor" style="height: 600px; font-family: monospace;"><?php echo htmlspecialchars($lang_file_content); ?></textarea>
                </div>

                <div class="admin-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='settings-translations.php'">Anuluj</button>
                    <button type="submit" name="save_lang_file" class="btn btn-primary">Zapisz zmiany</button>
                    <div class="admin-form-help mt-2">
                        Przed zapisaniem zmian automatycznie zostanie utworzona kopia zapasowa pliku.
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Create Language File Modal -->
<div id="create-lang-file-modal" class="admin-modal">
    <div class="admin-modal-content">
        <span class="admin-modal-close">&times;</span>
        <h3>Utwórz nowy plik języka</h3>
        <form method="post">
            <div class="admin-form-group">
                <label for="new_lang_code" class="admin-form-label">Kod języka (2 znaki)*</label>
                <input type="text" id="new_lang_code" name="new_lang_code" class="admin-form-input" required maxlength="2" placeholder="np. de, fr, it">
                <div class="admin-form-help">
                    Kod ISO języka (np. de dla niemieckiego, fr dla francuskiego)
                </div>
            </div>

            <div class="admin-form-group">
                <label for="template_lang" class="admin-form-label">Język szablonu*</label>
                <select id="template_lang" name="template_lang" class="admin-form-select" required>
                    <option value="">Wybierz język szablonu</option>
                    <?php foreach ($languages as $code => $language): ?>
                        <option value="<?php echo $code; ?>" <?php echo ($code === $default_language) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($language['name']); ?> (<?php echo htmlspecialchars($language['native_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="admin-form-help">
                    Istniejący plik języka, który zostanie użyty jako szablon dla nowego pliku
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="button" class="btn btn-secondary admin-modal-cancel">Anuluj</button>
                <button type="submit" name="create_lang_file" class="btn btn-primary">Utwórz plik</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal for creating new language file
        const createLangFileBtn = document.getElementById('create-lang-file-btn');
        const createLangFileModal = document.getElementById('create-lang-file-modal');
        const closeButtons = document.querySelectorAll('.admin-modal-close, .admin-modal-cancel');

        if (createLangFileBtn) {
            createLangFileBtn.addEventListener('click', function() {
                createLangFileModal.style.display = 'block';
            });
        }

        closeButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                createLangFileModal.style.display = 'none';
            });
        });

        // Close modal if clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === createLangFileModal) {
                createLangFileModal.style.display = 'none';
            }
        });

        // Track cursor position in textarea
        const textarea = document.getElementById('lang_file_content');
        const lineNumber = document.getElementById('line-number');

        if (textarea && lineNumber) {
            textarea.addEventListener('click', updateLineCol);
            textarea.addEventListener('keyup', updateLineCol);

            function updateLineCol() {
                const textLines = textarea.value.substr(0, textarea.selectionStart).split('\n');
                const currentLineNumber = textLines.length;
                const currentColumnIndex = textLines[textLines.length - 1].length + 1;

                lineNumber.textContent = `Linia: ${currentLineNumber}, Kolumna: ${currentColumnIndex}`;
            }

            // Initial position
            updateLineCol();
        }

        // Add tabulation support
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    e.preventDefault();

                    // Insert 4 spaces at cursor position
                    const start = this.selectionStart;
                    const end = this.selectionEnd;

                    this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);

                    // Move cursor after the inserted tab
                    this.selectionStart = this.selectionEnd = start + 4;

                    // Update line and column display
                    updateLineCol();
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
