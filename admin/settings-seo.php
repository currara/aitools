<?php
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
        'meta_title' => [
            'value' => isset($_POST['meta_title']) ? trim($_POST['meta_title']) : '',
            'type' => 'text',
            'is_translatable' => true
        ],
        'meta_description' => [
            'value' => isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '',
            'type' => 'textarea',
            'is_translatable' => true
        ],
        'meta_keywords' => [
            'value' => isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '',
            'type' => 'text',
            'is_translatable' => true
        ],
        'google_analytics_id' => [
            'value' => isset($_POST['google_analytics_id']) ? trim($_POST['google_analytics_id']) : '',
            'type' => 'text',
            'is_translatable' => false
        ],
        'enable_canonical_urls' => [
            'value' => isset($_POST['enable_canonical_urls']) ? '1' : '0',
            'type' => 'boolean',
            'is_translatable' => false
        ],
        'enable_robots_txt' => [
            'value' => isset($_POST['enable_robots_txt']) ? '1' : '0',
            'type' => 'boolean',
            'is_translatable' => false
        ],
        'robots_txt_content' => [
            'value' => isset($_POST['robots_txt_content']) ? trim($_POST['robots_txt_content']) : '',
            'type' => 'textarea',
            'is_translatable' => false
        ],
        'sitemap_frequency' => [
            'value' => isset($_POST['sitemap_frequency']) ? trim($_POST['sitemap_frequency']) : 'weekly',
            'type' => 'select',
            'is_translatable' => false
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
            // Log activity
            log_activity($_SESSION['user_id'], 'update', 'settings', null, 'Zaktualizowano ustawienia SEO');

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Ustawienia SEO zostały zapisane pomyślnie.'
            ];

            // Refresh page to show updated settings
            header('Location: settings-seo.php');
            exit;
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

<form action="settings-seo.php" method="post">
    <!-- Tabs Navigation -->
    <div class="admin-tabs">
        <div class="admin-tab active" data-tab="tab-meta">Meta Tagi</div>
        <div class="admin-tab" data-tab="tab-sitemap">Sitemap i Robots</div>
        <div class="admin-tab" data-tab="tab-analytics">Analityka</div>
    </div>

    <!-- Tab Content -->
    <div id="tab-meta" class="admin-tab-content active">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="meta_title" class="admin-form-label">Meta Tytuł</label>
                <input type="text" id="meta_title" name="meta_title" class="admin-form-input" value="<?php echo htmlspecialchars(isset($settings['meta_title']['value']) ? $settings['meta_title']['value'] : ''); ?>">
                <div class="admin-form-help">
                    Domyślny tytuł strony używany w metadanych SEO. Jeśli nie podany, użyty zostanie tytuł strony.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="meta_description" class="admin-form-label">Meta Opis</label>
                <textarea id="meta_description" name="meta_description" class="admin-form-textarea" rows="3"><?php echo htmlspecialchars(isset($settings['meta_description']['value']) ? $settings['meta_description']['value'] : ''); ?></textarea>
                <div class="admin-form-help">
                    Opis strony używany w metadanych SEO. Zalecana długość: 150-160 znaków.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="meta_keywords" class="admin-form-label">Meta Słowa kluczowe</label>
                <input type="text" id="meta_keywords" name="meta_keywords" class="admin-form-input" value="<?php echo htmlspecialchars(isset($settings['meta_keywords']['value']) ? $settings['meta_keywords']['value'] : ''); ?>">
                <div class="admin-form-help">
                    Słowa kluczowe oddzielone przecinkami. Uwaga: współczesne wyszukiwarki rzadko korzystają z tego tagu.
                </div>
            </div>

            <div class="admin-form-group" style="display: flex; align-items: center; margin-top: 20px;">
                <input type="checkbox" id="enable_canonical_urls" name="enable_canonical_urls" value="1" <?php echo (isset($settings['enable_canonical_urls']['value']) && $settings['enable_canonical_urls']['value'] == '1') ? 'checked' : ''; ?>>
                <label for="enable_canonical_urls" style="margin-left: 10px; margin-bottom: 0;">Włącz kanoniczne URL-e</label>
            </div>

            <!-- Translations for Meta Tags -->
            <div class="admin-form-section" style="margin-top: 30px;">
                <h3>Tłumaczenia meta tagów</h3>

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
                    $trans_meta_title = '';
                    if (isset($settings['meta_title']) && isset($settings['meta_title']['translations'][$lang_code])) {
                        $trans_meta_title = $settings['meta_title']['translations'][$lang_code];
                    }

                    $trans_meta_description = '';
                    if (isset($settings['meta_description']) && isset($settings['meta_description']['translations'][$lang_code])) {
                        $trans_meta_description = $settings['meta_description']['translations'][$lang_code];
                    }

                    $trans_meta_keywords = '';
                    if (isset($settings['meta_keywords']) && isset($settings['meta_keywords']['translations'][$lang_code])) {
                        $trans_meta_keywords = $settings['meta_keywords']['translations'][$lang_code];
                    }
                ?>
                    <div class="language-content <?php echo $first_lang ? 'active' : ''; ?>" data-lang="<?php echo $lang_code; ?>">
                        <div class="admin-form-row">
                            <div class="admin-form-group">
                                <label for="meta_title_<?php echo $lang_code; ?>" class="admin-form-label">Meta Tytuł (<?php echo $lang_info['native_name']; ?>)</label>
                                <input type="text" id="meta_title_<?php echo $lang_code; ?>" name="meta_title_<?php echo $lang_code; ?>" class="admin-form-input" value="<?php echo htmlspecialchars($trans_meta_title); ?>">
                            </div>

                            <div class="admin-form-group">
                                <label for="meta_description_<?php echo $lang_code; ?>" class="admin-form-label">Meta Opis (<?php echo $lang_info['native_name']; ?>)</label>
                                <textarea id="meta_description_<?php echo $lang_code; ?>" name="meta_description_<?php echo $lang_code; ?>" class="admin-form-textarea" rows="3"><?php echo htmlspecialchars($trans_meta_description); ?></textarea>
                            </div>

                            <div class="admin-form-group">
                                <label for="meta_keywords_<?php echo $lang_code; ?>" class="admin-form-label">Meta Słowa kluczowe (<?php echo $lang_info['native_name']; ?>)</label>
                                <input type="text" id="meta_keywords_<?php echo $lang_code; ?>" name="meta_keywords_<?php echo $lang_code; ?>" class="admin-form-input" value="<?php echo htmlspecialchars($trans_meta_keywords); ?>">
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

    <div id="tab-sitemap" class="admin-tab-content">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="sitemap_frequency" class="admin-form-label">Częstotliwość aktualizacji sitemap</label>
                <select id="sitemap_frequency" name="sitemap_frequency" class="admin-form-select">
                    <option value="always" <?php echo (isset($settings['sitemap_frequency']['value']) && $settings['sitemap_frequency']['value'] == 'always') ? 'selected' : ''; ?>>Zawsze</option>
                    <option value="hourly" <?php echo (isset($settings['sitemap_frequency']['value']) && $settings['sitemap_frequency']['value'] == 'hourly') ? 'selected' : ''; ?>>Co godzinę</option>
                    <option value="daily" <?php echo (isset($settings['sitemap_frequency']['value']) && $settings['sitemap_frequency']['value'] == 'daily') ? 'selected' : ''; ?>>Codziennie</option>
                    <option value="weekly" <?php echo (!isset($settings['sitemap_frequency']['value']) || $settings['sitemap_frequency']['value'] == 'weekly') ? 'selected' : ''; ?>>Co tydzień</option>
                    <option value="monthly" <?php echo (isset($settings['sitemap_frequency']['value']) && $settings['sitemap_frequency']['value'] == 'monthly') ? 'selected' : ''; ?>>Co miesiąc</option>
                    <option value="yearly" <?php echo (isset($settings['sitemap_frequency']['value']) && $settings['sitemap_frequency']['value'] == 'yearly') ? 'selected' : ''; ?>>Co rok</option>
                    <option value="never" <?php echo (isset($settings['sitemap_frequency']['value']) && $settings['sitemap_frequency']['value'] == 'never') ? 'selected' : ''; ?>>Nigdy</option>
                </select>
                <div class="admin-form-help">
                    Określa, jak często strony Twojej witryny mogą się zmieniać.
                </div>
            </div>

            <div class="admin-form-group" style="margin-top: 20px;">
                <div style="display: flex; align-items: center;">
                    <input type="checkbox" id="enable_robots_txt" name="enable_robots_txt" value="1" <?php echo (isset($settings['enable_robots_txt']['value']) && $settings['enable_robots_txt']['value'] == '1') ? 'checked' : ''; ?>>
                    <label for="enable_robots_txt" style="margin-left: 10px; margin-bottom: 0;">Włącz niestandardowy plik robots.txt</label>
                </div>

                <div style="margin-top: 15px;">
                    <label for="robots_txt_content" class="admin-form-label">Zawartość pliku robots.txt</label>
                    <textarea id="robots_txt_content" name="robots_txt_content" class="admin-form-textarea" rows="10"><?php echo htmlspecialchars(isset($settings['robots_txt_content']['value']) ? $settings['robots_txt_content']['value'] : "User-agent: *\nAllow: /\nSitemap: " . (isset($settings['site_url']['value']) ? $settings['site_url']['value'] : SITE_URL) . "/sitemap.xml"); ?></textarea>
                    <div class="admin-form-help">
                        Zawartość pliku robots.txt, który będzie kontrolował dostęp robotów wyszukiwarek.
                    </div>
                </div>
            </div>

            <div class="admin-form-group" style="margin-top: 20px;">
                <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;">
                    <h4>Ręczne generowanie Sitemap</h4>
                    <p>Kliknij przycisk poniżej, aby ręcznie wygenerować plik sitemap.xml.</p>
                    <button type="button" id="generate_sitemap" class="btn btn-secondary">Generuj Sitemap</button>
                    <div id="sitemap_result" style="margin-top: 10px; display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-analytics" class="admin-tab-content">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="google_analytics_id" class="admin-form-label">ID Google Analytics</label>
                <input type="text" id="google_analytics_id" name="google_analytics_id" class="admin-form-input" value="<?php echo htmlspecialchars(isset($settings['google_analytics_id']['value']) ? $settings['google_analytics_id']['value'] : ''); ?>" placeholder="UA-XXXXXXXX-X lub G-XXXXXXXXXX">
                <div class="admin-form-help">
                    Twoje ID Google Analytics. Format: UA-XXXXXXXX-X (Universal Analytics) lub G-XXXXXXXXXX (Google Analytics 4).
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Zapisz ustawienia</button>
    </div>
</form>

<script>
    // Handle sitemap generation
    document.addEventListener('DOMContentLoaded', function() {
        const generateSitemapBtn = document.getElementById('generate_sitemap');
        const sitemapResult = document.getElementById('sitemap_result');

        if (generateSitemapBtn) {
            generateSitemapBtn.addEventListener('click', function() {
                generateSitemapBtn.disabled = true;
                generateSitemapBtn.textContent = 'Generowanie...';
                sitemapResult.style.display = 'block';
                sitemapResult.innerHTML = '<div class="alert alert-info">Trwa generowanie sitemap.xml...</div>';

                // AJAX call to generate sitemap
                fetch('ajax/generate-sitemap.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            sitemapResult.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                        } else {
                            sitemapResult.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        sitemapResult.innerHTML = '<div class="alert alert-danger">Wystąpił błąd podczas generowania sitemap: ' + error + '</div>';
                    })
                    .finally(() => {
                        generateSitemapBtn.disabled = false;
                        generateSitemapBtn.textContent = 'Generuj Sitemap';
                    });
            });
        }

        // Toggle robots.txt content field based on checkbox
        const enableRobotsTxt = document.getElementById('enable_robots_txt');
        const robotsTxtContent = document.getElementById('robots_txt_content');

        function toggleRobotsTxtContent() {
            if (enableRobotsTxt.checked) {
                robotsTxtContent.parentElement.style.display = 'block';
            } else {
                robotsTxtContent.parentElement.style.display = 'none';
            }
        }

        if (enableRobotsTxt && robotsTxtContent) {
            toggleRobotsTxtContent(); // Initial state
            enableRobotsTxt.addEventListener('change', toggleRobotsTxtContent);
        }
    });
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
