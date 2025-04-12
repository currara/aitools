<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

// Include header
include_once 'includes/header.php';

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
$settings = get_settings();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_to_save = [
        'site_title' => [
            'value' => isset($_POST['site_title']) ? trim($_POST['site_title']) : '',
            'type' => 'text',
            'is_translatable' => true
        ],
        'site_description' => [
            'value' => isset($_POST['site_description']) ? trim($_POST['site_description']) : '',
            'type' => 'textarea',
            'is_translatable' => true
        ],
        'site_url' => [
            'value' => isset($_POST['site_url']) ? trim($_POST['site_url']) : '',
            'type' => 'url',
            'is_translatable' => false
        ],
        'contact_email' => [
            'value' => isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '',
            'type' => 'email',
            'is_translatable' => false
        ],
        'items_per_page' => [
            'value' => isset($_POST['items_per_page']) ? (int)$_POST['items_per_page'] : 10,
            'type' => 'number',
            'is_translatable' => false
        ],
        'enable_upvotes' => [
            'value' => isset($_POST['enable_upvotes']) ? '1' : '0',
            'type' => 'boolean',
            'is_translatable' => false
        ],
        'enable_ratings' => [
            'value' => isset($_POST['enable_ratings']) ? '1' : '0',
            'type' => 'boolean',
            'is_translatable' => false
        ],
        'enable_comments' => [
            'value' => isset($_POST['enable_comments']) ? '1' : '0',
            'type' => 'boolean',
            'is_translatable' => false
        ],
        'enable_submissions' => [
            'value' => isset($_POST['enable_submissions']) ? '1' : '0',
            'type' => 'boolean',
            'is_translatable' => false
        ],
        'footer_text' => [
            'value' => isset($_POST['footer_text']) ? trim($_POST['footer_text']) : '',
            'type' => 'textarea',
            'is_translatable' => true
        ]
    ];

    // Get translations for translatable settings
    $translations = [];
    foreach ($available_languages as $lang_code => $lang_info) {
        if ($lang_code === $default_language) continue; // Skip default language

        $translations[$lang_code] = [];

        foreach ($settings_to_save as $key => $setting) {
            if ($setting['is_translatable']) {
                $translations[$lang_code][$key] = isset($_POST[$key . '_' . $lang_code]) ? trim($_POST[$key . '_' . $lang_code]) : '';
            }
        }
    }

    // Validate required fields
    if (empty($settings_to_save['site_title']['value'])) {
        $errors[] = 'Tytuł strony jest wymagany.';
    }

    if (empty($settings_to_save['site_url']['value'])) {
        $errors[] = 'URL strony jest wymagany.';
    } else if (!filter_var($settings_to_save['site_url']['value'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Wprowadź prawidłowy adres URL strony.';
    }

    if (!empty($settings_to_save['contact_email']['value']) && !filter_var($settings_to_save['contact_email']['value'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Wprowadź prawidłowy adres e-mail.';
    }

    // If no errors, save settings
    if (empty($errors)) {
        foreach ($settings_to_save as $key => $setting) {
            $is_translatable = $setting['is_translatable'];
            $trans_data = [];

            if ($is_translatable) {
                foreach ($translations as $lang_code => $trans_values) {
                    if (isset($trans_values[$key])) {
                        $trans_data[$lang_code] = $trans_values[$key];
                    }
                }
            }

            $result = save_setting($key, $setting['value'], $trans_data, $setting['type'], $is_translatable);

            if (!$result['success']) {
                $errors[] = 'Błąd podczas zapisywania ustawienia ' . $key . ': ' . $result['message'];
            }
        }

        if (empty($errors)) {
            log_activity($_SESSION['user_id'], 'update', 'settings', 'general');

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Ustawienia zostały zapisane pomyślnie.'
            ];

            // Upewnij się, że nie było wcześniejszego outputu i wyczyść bufory
            if (!headers_sent()) {
                ob_end_clean(); // Czyści bufor wyjścia
                session_write_close(); // Zapisuje sesję
                header('Location: settings-general.php');
                exit;
            } else {
                // Jeśli nagłówki już zostały wysłane, użyj JavaScript do przekierowania
                echo '<script>window.location.href = "settings-general.php";</script>';
                exit;
            }
        }
    }
}

// Display success message if settings were saved
$settings_saved = isset($_GET['saved']) && $_GET['saved'] == '1';
?>

<!-- Page Content -->
<?php if ($settings_saved): ?>
    <div class="alert alert-success">
        Ustawienia zostały zapisane pomyślnie.
        <button type="button" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

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

<form action="settings-general.php" method="post">
    <!-- Tabs Navigation -->
    <div class="admin-tabs">
        <div class="admin-tab active" data-tab="tab-general">Ogólne</div>
        <div class="admin-tab" data-tab="tab-content">Treść</div>
        <div class="admin-tab" data-tab="tab-features">Funkcje</div>
    </div>

    <!-- Tab Content -->
    <div id="tab-general" class="admin-tab-content active">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="site_title" class="admin-form-label">Tytuł strony *</label>
                <input type="text" id="site_title" name="site_title" class="admin-form-input" value="<?php echo htmlspecialchars(isset($settings['site_title']['value']) ? $settings['site_title']['value'] : ''); ?>" required>
                <div class="admin-form-help">
                    Tytuł strony wyświetlany w nagłówku przeglądarki i wynikach wyszukiwania.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="site_description" class="admin-form-label">Opis strony</label>
                <textarea id="site_description" name="site_description" class="admin-form-textarea"><?php echo htmlspecialchars(isset($settings['site_description']['value']) ? $settings['site_description']['value'] : ''); ?></textarea>
                <div class="admin-form-help">
                    Krótki opis strony wyświetlany w wynikach wyszukiwania.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="site_url" class="admin-form-label">URL strony *</label>
                <input type="url" id="site_url" name="site_url" class="admin-form-input" value="<?php echo htmlspecialchars(isset($settings['site_url']['value']) ? $settings['site_url']['value'] : ''); ?>" required>
                <div class="admin-form-help">
                    Pełny adres URL strony (np. https://example.com).
                </div>
            </div>

            <div class="admin-form-group">
                <label for="contact_email" class="admin-form-label">E-mail kontaktowy</label>
                <input type="email" id="contact_email" name="contact_email" class="admin-form-input" value="<?php echo htmlspecialchars(isset($settings['contact_email']['value']) ? $settings['contact_email']['value'] : ''); ?>">
                <div class="admin-form-help">
                    Adres e-mail używany do kontaktu i powiadomień systemowych.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="items_per_page" class="admin-form-label">Elementy na stronę</label>
                <input type="number" id="items_per_page" name="items_per_page" class="admin-form-input" value="<?php echo (int)(isset($settings['items_per_page']['value']) ? $settings['items_per_page']['value'] : 12); ?>" min="1" max="100">
                <div class="admin-form-help">
                    Liczba narzędzi wyświetlanych na jednej stronie.
                </div>
            </div>
        </div>
    </div>

    <div id="tab-content" class="admin-tab-content">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="footer_text" class="admin-form-label">Tekst stopki</label>
                <textarea id="footer_text" name="footer_text" class="admin-form-textarea"><?php echo htmlspecialchars(isset($settings['footer_text']['value']) ? $settings['footer_text']['value'] : ''); ?></textarea>
                <div class="admin-form-help">
                    Tekst wyświetlany w stopce strony. Można używać podstawowych tagów HTML.
                </div>
            </div>

            <!-- Translations for Content -->
            <div class="admin-form-section" style="margin-top: 30px;">
                <h3>Tłumaczenia treści</h3>

                <div class="language-tabs">
                    <?php
                    $first_lang = true;
                    foreach ($available_languages as $lang_code => $lang_info):
                        if ($lang_code === $default_language) continue; // Skip default language
                    ?>
                        <div class="language-tab <?php echo $first_lang ? 'active' : ''; ?>" data-lang="<?php echo $lang_code; ?>">
                            <?php echo $lang_info['native_name']; ?>
                        </div>
                    <?php
                        $first_lang = false;
                    endforeach;
                    ?>
                </div>

                <?php
                $first_lang = true;
                foreach ($available_languages as $lang_code => $lang_info):
                    if ($lang_code === $default_language) continue; // Skip default language

                    // Get translated values
                    $trans_site_title = '';
                    if (isset($settings['site_title']) && isset($settings['site_title']['translations'][$lang_code])) {
                        $trans_site_title = $settings['site_title']['translations'][$lang_code];
                    }

                    $trans_site_description = '';
                    if (isset($settings['site_description']) && isset($settings['site_description']['translations'][$lang_code])) {
                        $trans_site_description = $settings['site_description']['translations'][$lang_code];
                    }

                    $trans_footer_text = '';
                    if (isset($settings['footer_text']) && isset($settings['footer_text']['translations'][$lang_code])) {
                        $trans_footer_text = $settings['footer_text']['translations'][$lang_code];
                    }
                ?>
                    <div class="language-content <?php echo $first_lang ? 'active' : ''; ?>" data-lang="<?php echo $lang_code; ?>">
                        <div class="admin-form-row">
                            <div class="admin-form-group">
                                <label for="site_title_<?php echo $lang_code; ?>" class="admin-form-label">Tytuł strony (<?php echo $lang_info['native_name']; ?>)</label>
                                <input type="text" id="site_title_<?php echo $lang_code; ?>" name="site_title_<?php echo $lang_code; ?>" class="admin-form-input" value="<?php echo htmlspecialchars($trans_site_title); ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="site_description_<?php echo $lang_code; ?>" class="admin-form-label">Opis strony (<?php echo $lang_info['native_name']; ?>)</label>
                                <textarea id="site_description_<?php echo $lang_code; ?>" name="site_description_<?php echo $lang_code; ?>" class="admin-form-textarea"><?php echo htmlspecialchars($trans_site_description); ?></textarea>
                            </div>

                            <div class="admin-form-group">
                                <label for="footer_text_<?php echo $lang_code; ?>" class="admin-form-label">Tekst stopki (<?php echo $lang_info['native_name']; ?>)</label>
                                <textarea id="footer_text_<?php echo $lang_code; ?>" name="footer_text_<?php echo $lang_code; ?>" class="admin-form-textarea"><?php echo htmlspecialchars($trans_footer_text); ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php
                    $first_lang = false;
                endforeach;
                ?>
            </div>
        </div>
    </div>

    <div id="tab-features" class="admin-tab-content">
        <div class="admin-form-row">
            <div class="admin-form-group" style="display: flex; align-items: center;">
                <input type="checkbox" id="enable_upvotes" name="enable_upvotes" value="1" <?php echo (isset($settings['enable_upvotes']['value']) && $settings['enable_upvotes']['value'] == '1') ? 'checked' : ''; ?>>
                <label for="enable_upvotes" style="margin-left: 10px; margin-bottom: 0;">Włącz polubienia narzędzi</label>
            </div>

            <div class="admin-form-group" style="display: flex; align-items: center;">
                <input type="checkbox" id="enable_ratings" name="enable_ratings" value="1" <?php echo (isset($settings['enable_ratings']['value']) && $settings['enable_ratings']['value'] == '1') ? 'checked' : ''; ?>>
                <label for="enable_ratings" style="margin-left: 10px; margin-bottom: 0;">Włącz oceny narzędzi</label>
            </div>

            <div class="admin-form-group" style="display: flex; align-items: center;">
                <input type="checkbox" id="enable_comments" name="enable_comments" value="1" <?php echo (isset($settings['enable_comments']['value']) && $settings['enable_comments']['value'] == '1') ? 'checked' : ''; ?>>
                <label for="enable_comments" style="margin-left: 10px; margin-bottom: 0;">Włącz komentarze</label>
            </div>

            <div class="admin-form-group" style="display: flex; align-items: center;">
                <input type="checkbox" id="enable_submissions" name="enable_submissions" value="1" <?php echo (isset($settings['enable_submissions']['value']) && $settings['enable_submissions']['value'] == '1') ? 'checked' : ''; ?>>
                <label for="enable_submissions" style="margin-left: 10px; margin-bottom: 0;">Włącz zgłaszanie narzędzi przez użytkowników</label>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Zapisz ustawienia</button>
    </div>
</form>

<?php
// Include footer
include_once 'includes/footer.php';
?>
