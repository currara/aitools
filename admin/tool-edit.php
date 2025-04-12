<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

require_once 'includes/tool-categories-functions.php';

// Include header
include_once 'includes/header.php';

// Initialize variables
$tool = [
    'id' => '',
    'name' => '',
    'slug' => '',
    'description' => '',
    'logo' => '',
    'website_url' => '',
    'category_id' => '',
    'featured' => false,
    'new_launch' => false,
    'pricing_type' => 'free',
    'rating' => 0,
    'upvotes' => 0,
    'image_type' => 'screenshot', // Dodane - domyślny typ obrazu
    'thumio_width' => 800, // Dodane - szerokość zrzutu thum.io
    'thumio_format' => 'png', // Dodane - format zrzutu thum.io
    'thumio_viewport' => 'desktop', // Dodane - viewport thum.io
    'generate_thumbnails' => true // Dodane - generowanie miniatur
];

$translations = [];
$errors = [];
$tags = [];

// Get all categories for dropdown (including subcategories)
$categories = get_categories('all');

// Ensure the tool_categories table exists
ensure_tool_categories_table_exists();

// Get all available tags
$all_tags = get_all_tags();

// Check if we're editing an existing tool
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $tool_id = (int)$_GET['id'];
    $tool_data = get_tool($tool_id);

    if ($tool_data) {
        $tool = $tool_data;
        $translations = get_tool_translations($tool_id);

        // Get tags for this tool
        $tags = get_tool_tags($tool_id);

        // Get categories for this tool
        $selected_categories = get_tool_categories($tool_id);

    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Narzędzie nie zostało znalezione.'
        ];
        header('Location: tools.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $tool['name'] = isset($_POST['name']) ? trim($_POST['name']) : '';
    $tool['slug'] = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $tool['description'] = isset($_POST['description']) ? trim($_POST['description']) : '';
    $tool['website_url'] = isset($_POST['website_url']) ? trim($_POST['website_url']) : '';
    $tool['category_id'] = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $tool['featured'] = isset($_POST['featured']) ? true : false;
    $tool['new_launch'] = isset($_POST['new_launch']) ? true : false;
    $tool['pricing_type'] = isset($_POST['pricing_type']) ? trim($_POST['pricing_type']) : 'free';
    $tool['logo'] = isset($_POST['logo']) ? trim($_POST['logo']) : '';

    // Obsługujemy nowe parametry
    $tool['image_type'] = isset($_POST['image_type']) ? trim($_POST['image_type']) : 'screenshot';
    $tool['thumio_width'] = isset($_POST['thumio_width']) ? (int)$_POST['thumio_width'] : 800;
    $tool['thumio_format'] = isset($_POST['thumio_format']) ? trim($_POST['thumio_format']) : 'png';
    $tool['thumio_viewport'] = isset($_POST['thumio_viewport']) ? trim($_POST['thumio_viewport']) : 'desktop';
    $tool['generate_thumbnails'] = isset($_POST['generate_thumbnails']) ? true : false;

    // Pobierz wybrane kategorie
    $selected_categories_json = isset($_POST['selected_categories']) ? $_POST['selected_categories'] : '[]';
    $selected_categories = json_decode($selected_categories_json, true);
    if (!is_array($selected_categories)) {
        $selected_categories = [];
    }

    // Obsługa usuwania zrzutu ekranu
    if (isset($_POST['remove_screenshot']) && $_POST['remove_screenshot'] == '1' && !empty($tool['screenshot'])) {
        // Usuń plik ze zrzutem ekranu
        $screenshot_path = '../images/' . $tool['screenshot'];
        if (file_exists($screenshot_path)) {
            unlink($screenshot_path);
        }

        // Usuń miniatury jeśli istnieją
        $base_name = pathinfo($tool['screenshot'], PATHINFO_FILENAME);
        $base_dir = pathinfo($screenshot_path, PATHINFO_DIRNAME);
        $thumb_sizes = [150, 300, 600];

        foreach ($thumb_sizes as $size) {
            $thumb_path = $base_dir . '/thumbnails/' . $base_name . '-' . $size . '.jpg';
            if (file_exists($thumb_path)) {
                unlink($thumb_path);
            }
        }

        // Usuń wartość zrzutu ekranu z narzędzia
        $tool['screenshot'] = '';
        $tool['image_type'] = '';

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Zrzut ekranu został usunięty.'
        ];
    }

    // Get selected tags
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    // Validate required fields
    if (empty($tool['name'])) {
        $errors[] = 'Nazwa narzędzia jest wymagana.';
    }

    if (empty($tool['description'])) {
        $errors[] = 'Opis narzędzia jest wymagany.';
    }

    if (empty($tool['website_url'])) {
        $errors[] = 'URL strony narzędzia jest wymagany.';
    } else if (!filter_var($tool['website_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Wprowadź prawidłowy adres URL.';
    }

    // Generate slug if empty
    if (empty($tool['slug'])) {
        $tool['slug'] = create_slug($tool['name']);
    }

    // Process logo upload if any
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === 0) {
        $allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (in_array($_FILES['logo_file']['type'], $allowed_types) && $_FILES['logo_file']['size'] <= $max_size) {
            $file_ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
            $file_name = create_slug($tool['name']) . '-logo.' . $file_ext;
            $target_path = '../images/' . $file_name;

            // Sprawdzamy MIME type pliku dla bezpieczeństwa
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($finfo, $_FILES['logo_file']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($file_mime, $allowed_types)) {
                $errors[] = 'Wykryto nieprawidłowy typ pliku.';
            } else if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target_path)) {
                $tool['logo'] = $file_name;

                // Generowanie miniatur dla przesłanego logo
                if ($tool['generate_thumbnails'] && ($file_mime == 'image/png' || $file_mime == 'image/jpeg')) {
                    generate_thumbnails($target_path);
                }
            } else {
                $errors[] = 'Nie udało się przesłać logo.';
            }
        } else {
            $errors[] = 'Nieprawidłowy typ pliku lub rozmiar logo. Dozwolone formaty: PNG, JPG, GIF, SVG. Maksymalny rozmiar: 2MB.';
        }
    }

    // Handle screenshot from URL if provided
    if (isset($_POST['get_screenshot']) && !empty($_POST['screenshot_url'])) {
        $screenshot_url = trim($_POST['screenshot_url']);

        // Validate URL
        if (filter_var($screenshot_url, FILTER_VALIDATE_URL)) {
            // Wykorzystaj bezpośrednie pobieranie przez plik screenshot.php lub thum.io
            // Dodajemy parametr type=screenshot lub type=favicon, zależnie od wyboru użytkownika
            $selected_image_type = isset($_POST['image_type']) ? trim($_POST['image_type']) : 'screenshot';

            // Przygotuj parametry dla thum.io
            $thumio_width = (int)$tool['thumio_width'];
            $thumio_format = $tool['thumio_format'];
            $thumio_viewport = $tool['thumio_viewport'];

            // Generate unique filename based on tool name and type
            $file_name = create_slug($tool['name'] ?: 'tool') . '-' . $selected_image_type . '-' . time() . '.' . $thumio_format;
            $target_path = '../images/screenshots/' . $file_name;

            // Create directory if it doesn't exist
            if (!file_exists('../images/screenshots/')) {
                if (!mkdir('../images/screenshots/', 0755, true)) {
                    $errors[] = 'Nie udało się utworzyć katalogu na zrzuty ekranu.';
                }
            }

            // Tworzymy katalog na miniatury jeśli nie istnieje
            if ($tool['generate_thumbnails'] && !file_exists('../images/screenshots/thumbnails/')) {
                if (!mkdir('../images/screenshots/thumbnails/', 0755, true)) {
                    $errors[] = 'Nie udało się utworzyć katalogu na miniatury.';
                }
            }

            // Włącz wyświetlanie błędów
            $old_error_reporting = error_reporting();
            error_reporting(E_ERROR);

            // Pobieranie obrazu
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 20, // Zwiększony czas oczekiwania dla zrzutów ekranu
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                    ]
                ]);

                // Loguj żądanie zrzutu
                error_log("Próba pobrania " . $selected_image_type . " z URL: " . $screenshot_url);

                $image_data = false;

                // Pobieranie zależne od typu obrazu
                if ($selected_image_type == 'screenshot') {
                    // Bezpośrednie użycie thum.io z parametrami
                    $viewportParam = ($thumio_viewport != 'desktop') ? '/view/' . $thumio_viewport : '';
                    $thum_io_url = "https://image.thum.io/get/width/" . $thumio_width . $viewportParam . "/" . $thumio_format . "/" . $screenshot_url;
                    error_log("Żądanie thum.io: " . $thum_io_url);
                    $image_data = @file_get_contents($thum_io_url, false, $context);
                } else {
                    // Pobierz favicon
                    $parsed_url = parse_url($screenshot_url);
                    $domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';

                    if (!empty($domain)) {
                        $favicon_url = "https://www.google.com/s2/favicons?domain=$domain&sz=64";
                        $image_data = @file_get_contents($favicon_url, false, $context);
                    }
                }

                if ($image_data !== false) {
                    if (file_put_contents($target_path, $image_data)) {
                        // Update tool with screenshot path
                        $screenshot_relative_path = 'screenshots/' . $file_name;
                        $tool['screenshot'] = $screenshot_relative_path;

                        // Zapisz również jako logo narzędzia jeśli nie ma istniejącego logo
                        if (empty($tool['logo'])) {
                            $tool['logo'] = $screenshot_relative_path;
                        }

                        // Dodaj informację o typie obrazu
                        $tool['image_type'] = $selected_image_type;

                        // Generuj miniatury jeśli opcja zaznaczona
                        if ($tool['generate_thumbnails']) {
                            $thumb_success = generate_thumbnails($target_path);
                            if (!$thumb_success) {
                                $errors[] = 'Nie udało się wygenerować miniatur. Obrazek zostanie użyty w oryginalnym rozmiarze.';
                            }
                        }

                        $_SESSION['alert'] = [
                            'type' => 'success',
                            'message' => ($selected_image_type == 'screenshot') ?
                                'Zrzut ekranu został pobrany pomyślnie.' :
                                'Logo/favicon zostało pobrane pomyślnie.'
                        ];
                    } else {
                        $errors[] = 'Nie udało się zapisać pobranego obrazu. Sprawdź uprawnienia do katalogu images/screenshots/.';
                    }
                } else {
                    // Logujemy szczegóły błędu
                    error_log("Nie udało się pobrać " . $selected_image_type . " dla: " . $screenshot_url);
                    $errors[] = 'Nie udało się pobrać obrazu. Sprawdź czy podany adres URL jest prawidłowy i dostępny.';
                }
            } catch (Exception $e) {
                error_log("Exception podczas pobierania obrazu: " . $e->getMessage());
                $errors[] = 'Wystąpił błąd podczas pobierania obrazu: ' . $e->getMessage();
            }

            // Przywróć poprzednie ustawienia raportowania błędów
            error_reporting($old_error_reporting);

        } else {
            $errors[] = 'Wprowadź prawidłowy adres URL dla zrzutu ekranu.';
        }
    }

    // Get translations
    $trans_data = [];
    foreach ($available_languages as $lang_code => $lang_info) {
        if ($lang_code === $default_language) continue; // Skip default language

        $trans_name = isset($_POST['trans_name_' . $lang_code]) ? trim($_POST['trans_name_' . $lang_code]) : '';
        $trans_description = isset($_POST['trans_description_' . $lang_code]) ? trim($_POST['trans_description_' . $lang_code]) : '';

        if (!empty($trans_name) || !empty($trans_description)) {
            $trans_data[$lang_code] = [
                'name' => $trans_name,
                'description' => $trans_description
            ];
        }
    }

    // If no errors, save tool
    if (empty($errors)) {
        $save_data = $tool;
        $result = save_tool($save_data, $trans_data);

        if ($result['success']) {
            // Save tags for the tool
            if (isset($result['id'])) {
                update_tool_tags($result['id'], $selected_tags);

                // Zapisz kategorie dla narzędzia
                update_tool_categories($result['id'], $selected_categories);
            }

            // Log activity
            $action = isset($tool['id']) && !empty($tool['id']) ? 'update' : 'create';
            log_activity($_SESSION['user_id'], $action, 'tool', $result['id']);

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => $result['message']
            ];

            // Poprawka do przekierowania - zapewnić, że nie ma wcześniejszego outputu
            // Tylko przekierowujemy gdy nie pobieramy zrzutu ekranu
            if (!isset($_POST['get_screenshot'])) {
                ob_end_clean(); // Wyczyść bufory outputu
                session_write_close(); // Zapisz sesję
                header('Location: tools.php');
                exit;
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Funkcja do generowania miniatur
function generate_thumbnails($source_path) {
    $thumb_sizes = [150, 300, 600]; // Rozmiary miniatur
    $quality = 85; // Jakość JPG

    // Sprawdź czy source_path istnieje
    if (!file_exists($source_path)) {
        error_log("Nie znaleziono pliku źródłowego: " . $source_path);
        return false;
    }

    // Katalog na miniatury
    $thumb_dir = dirname($source_path) . '/thumbnails/';
    if (!file_exists($thumb_dir)) {
        if (!mkdir($thumb_dir, 0755, true)) {
            error_log("Nie można utworzyć katalogu: " . $thumb_dir);
            return false;
        }
    }

    $base_name = pathinfo($source_path, PATHINFO_FILENAME);

    // Sprawdź typ obrazu
    $image_info = getimagesize($source_path);
    if (!$image_info) {
        error_log("Nie można uzyskać informacji o obrazie: " . $source_path);
        return false;
    }

    $mime_type = $image_info['mime'];
    $success = false;

    try {
        switch ($mime_type) {
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $source_image = imagecreatefromgif($source_path);
                break;
            default:
                error_log("Nieobsługiwany typ obrazu: " . $mime_type);
                return false;
        }

        if (!$source_image) {
            error_log("Nie udało się utworzyć obrazu GD: " . $source_path);
            return false;
        }

        $orig_width = imagesx($source_image);
        $orig_height = imagesy($source_image);

        // Przetwarzaj każdy rozmiar miniatury
        foreach ($thumb_sizes as $max_width) {
            // Zachowaj proporcje
            if ($orig_width > $max_width) {
                $new_width = $max_width;
                $new_height = round($orig_height * ($max_width / $orig_width));
            } else {
                // Jeśli obraz jest mniejszy niż docelowy rozmiar miniatury, użyj oryginalnego rozmiaru
                $new_width = $orig_width;
                $new_height = $orig_height;
            }

            // Utwórz miniaturę
            $thumb_image = imagecreatetruecolor($new_width, $new_height);

            // Zachowaj przezroczystość dla PNG
            if ($mime_type === 'image/png') {
                imagealphablending($thumb_image, false);
                imagesavealpha($thumb_image, true);
                $transparent = imagecolorallocatealpha($thumb_image, 255, 255, 255, 127);
                imagefilledrectangle($thumb_image, 0, 0, $new_width, $new_height, $transparent);
            }

            // Skopiuj i zmień rozmiar oryginalnego obrazu do miniatury
            imagecopyresampled(
                $thumb_image, $source_image,
                0, 0, 0, 0,
                $new_width, $new_height, $orig_width, $orig_height
            );

            // Zapisz miniaturę
            $thumb_path = $thumb_dir . $base_name . '-' . $max_width . '.jpg';
            if (imagejpeg($thumb_image, $thumb_path, $quality)) {
                $success = true;
            } else {
                error_log("Nie udało się zapisać miniatury: " . $thumb_path);
            }

            // Zwolnij pamięć
            imagedestroy($thumb_image);
        }

        imagedestroy($source_image);
        return $success;
    } catch (Exception $e) {
        error_log("Błąd podczas generowania miniatur: " . $e->getMessage());
        return false;
    }
}
?>

<!-- Dodaj TipTap zamiast QuillJS -->
<link href="https://unpkg.com/@tiptap/suggestion/dist/suggestion.css" rel="stylesheet">
<style>
    .tiptap-editor {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        min-height: 200px;
        background-color: #fff;
    }

    .tiptap-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 10px;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #f9f9f9;
    }

    .tiptap-toolbar button {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 14px;
    }

    .tiptap-toolbar button:hover {
        background-color: #f1f1f1;
    }

    .tiptap-toolbar button.is-active {
        background-color: #e9e9e9;
        font-weight: bold;
    }

    .tiptap-editor ul, .tiptap-editor ol {
        padding-left: 20px;
    }

    .tiptap-editor p {
        margin: 0 0 10px 0;
    }
</style>

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

<form action="tool-edit.php<?php echo isset($tool['id']) ? '?id=' . $tool['id'] : ''; ?>" method="post" enctype="multipart/form-data">
    <!-- Tabs Navigation -->
    <div class="admin-tabs">
        <div class="admin-tab active" data-tab="tab-general">Ogólne</div>
        <div class="admin-tab" data-tab="tab-details">Szczegóły</div>
        <div class="admin-tab" data-tab="tab-translations">Tłumaczenia</div>
        <div class="admin-tab" data-tab="tab-media">Media</div>
    </div>

    <!-- Tab Content -->
    <div id="tab-general" class="admin-tab-content active">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="name" class="admin-form-label">Nazwa narzędzia *</label>
                <input type="text" id="name" name="name" class="admin-form-input auto-slug-source" data-slug-target="#slug" value="<?php echo htmlspecialchars($tool['name']); ?>" required>
            </div>

            <div class="admin-form-group">
                <label for="slug" class="admin-form-label">Slug</label>
                <input type="text" id="slug" name="slug" class="admin-form-input auto-slug" value="<?php echo htmlspecialchars($tool['slug']); ?>">
                <div class="admin-form-help">
                    Identyfikator URL narzędzia. Pozostaw puste, aby wygenerować automatycznie z nazwy.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="website_url" class="admin-form-label">URL strony narzędzia *</label>
                <input type="url" id="website_url" name="website_url" class="admin-form-input" value="<?php echo htmlspecialchars($tool['website_url']); ?>" required>
                <div class="admin-form-help">
                    Pełny adres URL strony narzędzia (np. https://example.com).
                </div>
            </div>

            <div class="admin-form-group">
                <label for="category_id" class="admin-form-label">Główna kategoria *</label>
                <select id="category_id" name="category_id" class="admin-form-select" required>
                    <option value="">-- Wybierz główną kategorię --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo (isset($tool['category_id']) && $tool['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php if (!empty($category['subcategories'])): ?>
                            <?php foreach ($category['subcategories'] as $subcategory): ?>
                                <option value="<?php echo $subcategory['id']; ?>" <?php echo (isset($tool['category_id']) && $tool['category_id'] == $subcategory['id']) ? 'selected' : ''; ?>>
                                    &nbsp;&nbsp;&nbsp;- <?php echo htmlspecialchars($subcategory['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <div class="admin-form-help">
                    Wybierz główną kategorię narzędzia. Ta kategoria będzie używana do wyświetlania na liście narzędzi i do filtrowania.
                </div>
            </div>

            <div class="admin-form-group">
                <label class="admin-form-label">Dodatkowe kategorie</label>
                <div class="category-search-container">
                    <input type="text" id="category-search" class="admin-form-input" placeholder="Szukaj kategorii...">
                </div>
                <?php include 'includes/tool-categories-template.php'; ?>
                <div class="admin-form-help">
                    Możesz wybrać dodatkowe kategorie, do których to narzędzie również należy. Narzędzie będzie wyświetlane w każdej z tych kategorii.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="description" class="admin-form-label">Opis *</label>
                <div class="tiptap-toolbar" id="description-toolbar">
                    <button type="button" data-command="bold" title="Pogrubienie"><i class="fas fa-bold"></i></button>
                    <button type="button" data-command="italic" title="Kursywa"><i class="fas fa-italic"></i></button>
                    <button type="button" data-command="underline" title="Podkreślenie"><i class="fas fa-underline"></i></button>
                    <button type="button" data-command="orderedList" title="Lista numerowana"><i class="fas fa-list-ol"></i></button>
                    <button type="button" data-command="bulletList" title="Lista punktowana"><i class="fas fa-list-ul"></i></button>
                    <button type="button" data-command="link" title="Link"><i class="fas fa-link"></i></button>
                    <button type="button" data-command="image" title="Wstaw obraz"><i class="fas fa-image"></i></button>
                    <button type="button" data-command="clear" title="Wyczyść formatowanie"><i class="fas fa-eraser"></i></button>
                </div>
                <div id="description-editor" class="tiptap-editor"></div>
                <input type="hidden" id="description" name="description" value="<?php echo $tool['description']; ?>" required>
                <div class="admin-form-help">
                    Opis narzędzia wyświetlany na stronie szczegółów i w wynikach wyszukiwania. Możesz używać podstawowego formatowania.
                </div>
            </div>
        </div>
    </div>

    <div id="tab-details" class="admin-tab-content">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="pricing_type" class="admin-form-label">Typ cenowy</label>
                <select id="pricing_type" name="pricing_type" class="admin-form-select">
                    <option value="free" <?php echo ($tool['pricing_type'] === 'free') ? 'selected' : ''; ?>>Darmowe</option>
                    <option value="freemium" <?php echo ($tool['pricing_type'] === 'freemium') ? 'selected' : ''; ?>>Freemium</option>
                    <option value="paid" <?php echo ($tool['pricing_type'] === 'paid') ? 'selected' : ''; ?>>Płatne</option>
                    <option value="contact" <?php echo ($tool['pricing_type'] === 'contact') ? 'selected' : ''; ?>>Kontakt w sprawie ceny</option>
                </select>
            </div>

            <div class="admin-form-group" style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="display: flex; align-items: center;">
                    <input type="checkbox" id="featured" name="featured" value="1" <?php echo $tool['featured'] ? 'checked' : ''; ?>>
                    <label for="featured" style="margin-left: 10px; margin-bottom: 0;">Wyróżnione narzędzie</label>
                </div>

                <div style="display: flex; align-items: center;">
                    <input type="checkbox" id="new_launch" name="new_launch" value="1" <?php echo $tool['new_launch'] ? 'checked' : ''; ?>>
                    <label for="new_launch" style="margin-left: 10px; margin-bottom: 0;">Nowe narzędzie</label>
                </div>
            </div>

            <!-- Tagi -->
            <div class="admin-form-group">
                <label class="admin-form-label">Tagi</label>
                <div id="tag-inputs-container" style="margin-top: 10px;">
                    <div class="tag-input-row" style="display: flex; margin-bottom: 10px;">
                        <input type="text" class="admin-form-input tag-input" placeholder="Wprowadź nazwę tagu..." style="flex: 1; margin-right: 10px;">
                        <button type="button" class="btn btn-secondary add-tag-btn">Dodaj tag</button>
                    </div>
                </div>
                <div id="selected-tags-container" style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($tags as $tag): ?>
                        <div class="selected-tag" data-id="<?php echo $tag['id']; ?>" style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 20px; display: flex; align-items: center; margin-bottom: 5px;">
                            <span><?php echo htmlspecialchars($tag['name']); ?></span>
                            <input type="hidden" name="tags[]" value="<?php echo $tag['id']; ?>">
                            <button type="button" class="remove-tag-btn" style="background: none; border: none; color: #999; margin-left: 5px; cursor: pointer;">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="admin-form-help">
                    Wpisz i dodaj tagi, które najlepiej opisują to narzędzie.
                </div>
            </div>

            <?php if (isset($tool['id'])): ?>
                <div class="admin-form-group">
                    <label class="admin-form-label">Statystyki</label>
                    <div style="display: flex; gap: 30px; margin-top: 10px;">
                        <div>
                            <strong>Ocena:</strong> <?php echo number_format($tool['rating'], 1); ?>/5.0
                        </div>
                        <div>
                            <strong>Polubienia:</strong> <?php echo $tool['upvotes']; ?>
                        </div>
                        <div>
                            <strong>Wyświetlenia:</strong> <?php echo $tool['views'] ?? 0; ?>
                        </div>
                        <div>
                            <strong>Data dodania:</strong> <?php echo isset($tool['created_at']) ? date('d.m.Y', strtotime($tool['created_at'])) : 'Nowe narzędzie'; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="tab-translations" class="admin-tab-content">
        <!-- Language Tabs -->
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

        <!-- Language Content -->
        <?php
        $first_lang = true;
        foreach ($available_languages as $lang_code => $lang_info):
            if ($lang_code === $default_language) continue; // Skip default language

            $trans_name = isset($translations[$lang_code]['name']) ? $translations[$lang_code]['name'] : '';
            $trans_description = isset($translations[$lang_code]['description']) ? $translations[$lang_code]['description'] : '';
        ?>
            <div class="language-content <?php echo $first_lang ? 'active' : ''; ?>" data-lang="<?php echo $lang_code; ?>">
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="trans_name_<?php echo $lang_code; ?>" class="admin-form-label">Nazwa narzędzia (<?php echo $lang_info['native_name']; ?>)</label>
                        <input type="text" id="trans_name_<?php echo $lang_code; ?>" name="trans_name_<?php echo $lang_code; ?>" class="admin-form-input" value="<?php echo htmlspecialchars($trans_name); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label for="trans_description_<?php echo $lang_code; ?>" class="admin-form-label">Opis (<?php echo $lang_info['native_name']; ?>)</label>
                        <div class="tiptap-toolbar" id="toolbar-<?php echo $lang_code; ?>">
                            <button type="button" data-command="bold" title="Pogrubienie"><i class="fas fa-bold"></i></button>
                            <button type="button" data-command="italic" title="Kursywa"><i class="fas fa-italic"></i></button>
                            <button type="button" data-command="underline" title="Podkreślenie"><i class="fas fa-underline"></i></button>
                            <button type="button" data-command="orderedList" title="Lista numerowana"><i class="fas fa-list-ol"></i></button>
                            <button type="button" data-command="bulletList" title="Lista punktowana"><i class="fas fa-list-ul"></i></button>
                            <button type="button" data-command="link" title="Link"><i class="fas fa-link"></i></button>
                            <button type="button" data-command="image" title="Wstaw obraz"><i class="fas fa-image"></i></button>
                            <button type="button" data-command="clear" title="Wyczyść formatowanie"><i class="fas fa-eraser"></i></button>
                        </div>
                        <div id="editor-<?php echo $lang_code; ?>" class="tiptap-editor"></div>
                        <input type="hidden" name="trans_description_<?php echo $lang_code; ?>" id="trans_description_<?php echo $lang_code; ?>" value="<?php echo $trans_description; ?>">
                    </div>
                </div>
            </div>
        <?php
            $first_lang = false;
        endforeach;
        ?>
    </div>

    <div id="tab-media" class="admin-tab-content">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="logo" class="admin-form-label">Nazwa pliku logo</label>
                <input type="text" id="logo" name="logo" class="admin-form-input" value="<?php echo htmlspecialchars($tool['logo']); ?>">
                <div class="admin-form-help">
                    Nazwa pliku logo znajdującego się w katalogu images/
                </div>
            </div>

            <div class="admin-form-group">
                <label for="logo_file" class="admin-form-label">Prześlij logo</label>
                <input type="file" id="logo_file" name="logo_file" class="admin-form-file-input" accept=".png,.jpg,.jpeg,.gif,.svg">
                <div class="admin-form-help">
                    Dozwolone formaty: PNG, JPG, GIF, SVG. Maksymalny rozmiar: 2MB.
                </div>

                <?php if (!empty($tool['logo'])): ?>
                    <div style="margin-top: 15px;">
                        <strong>Aktualne logo:</strong>
                        <div style="margin-top: 10px;">
                            <?php
                            // Wybierz właściwy obrazek w zależności od typu obrazu
                            $image_to_display = $tool['logo'];

                            // Jeśli typ obrazu to 'screenshot', użyj zrzutu ekranu (jeśli istnieje)
                            if (isset($tool['image_type']) && $tool['image_type'] === 'screenshot' && !empty($tool['screenshot'])) {
                                $image_to_display = $tool['screenshot'];
                            }
                            ?>
                            <img src="../images/<?php echo htmlspecialchars($image_to_display); ?>" alt="Current Logo" style="max-width: 150px; max-height: 150px;" class="image-preview">
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Opcje konfiguracji thum.io -->
            <div class="admin-form-group">
                <label class="admin-form-label">Konfiguracja Thum.io</label>
                <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                    <div style="flex: 1; min-width: 200px;">
                        <label for="thumio_width" class="admin-form-label">Szerokość zrzutu</label>
                        <select id="thumio_width" name="thumio_width" class="admin-form-select">
                            <option value="640" <?php echo ($tool['thumio_width'] == 640) ? 'selected' : ''; ?>>640px</option>
                            <option value="800" <?php echo ($tool['thumio_width'] == 800) ? 'selected' : ''; ?>>800px</option>
                            <option value="1024" <?php echo ($tool['thumio_width'] == 1024) ? 'selected' : ''; ?>>1024px</option>
                            <option value="1280" <?php echo ($tool['thumio_width'] == 1280) ? 'selected' : ''; ?>>1280px</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label for="thumio_format" class="admin-form-label">Format zrzutu</label>
                        <select id="thumio_format" name="thumio_format" class="admin-form-select">
                            <option value="png" <?php echo ($tool['thumio_format'] == 'png') ? 'selected' : ''; ?>>PNG</option>
                            <option value="jpg" <?php echo ($tool['thumio_format'] == 'jpg') ? 'selected' : ''; ?>>JPG</option>
                            <option value="webp" <?php echo ($tool['thumio_format'] == 'webp') ? 'selected' : ''; ?>>WebP</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label for="thumio_viewport" class="admin-form-label">Tryb widoku</label>
                        <select id="thumio_viewport" name="thumio_viewport" class="admin-form-select">
                            <option value="desktop" <?php echo ($tool['thumio_viewport'] == 'desktop') ? 'selected' : ''; ?>>Desktop</option>
                            <option value="mobile" <?php echo ($tool['thumio_viewport'] == 'mobile') ? 'selected' : ''; ?>>Mobile</option>
                            <option value="tablet" <?php echo ($tool['thumio_viewport'] == 'tablet') ? 'selected' : ''; ?>>Tablet</option>
                        </select>
                    </div>
                </div>
                <div class="admin-form-help">
                    Parametry konfiguracyjne dla serwisu Thum.io używanego do generowania zrzutów ekranu.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="screenshot_url" class="admin-form-label">Pobierz zrzut ekranu z URL</label>
                <div class="input-group" style="display: flex; margin-bottom: 10px;">
                    <input type="url" id="screenshot_url" name="screenshot_url" class="admin-form-input"
                           placeholder="https://example.com" style="flex: 1; margin-right: 10px;"
                           value="<?php echo isset($_POST['screenshot_url']) ? htmlspecialchars($_POST['screenshot_url']) : $tool['website_url']; ?>">
                    <button type="submit" name="get_screenshot" class="btn btn-secondary" style="min-width: 150px;">Pobierz zrzut</button>
                </div>

                <!-- Dodana opcja wyboru typu obrazu -->
                <div style="display: flex; align-items: center; margin-top: 5px; margin-bottom: 15px; gap: 20px;">
                    <div>
                        <input type="radio" id="image_type_screenshot" name="image_type" value="screenshot"
                               <?php echo ($tool['image_type'] == 'screenshot' || empty($tool['image_type'])) ? 'checked' : ''; ?>>
                        <label for="image_type_screenshot">Zrzut ekranu</label>
                    </div>
                    <div>
                        <input type="radio" id="image_type_favicon" name="image_type" value="favicon"
                               <?php echo ($tool['image_type'] == 'favicon') ? 'checked' : ''; ?>>
                        <label for="image_type_favicon">Logo/Favicon</label>
                    </div>
                </div>

                <!-- Opcja generowania miniatur -->
                <div style="display: flex; align-items: center; margin-top: 5px;">
                    <input type="checkbox" id="generate_thumbnails" name="generate_thumbnails" value="1"
                           <?php echo ($tool['generate_thumbnails']) ? 'checked' : ''; ?>>
                    <label for="generate_thumbnails" style="margin-left: 10px;">Generuj miniatury w różnych rozmiarach</label>
                </div>

                <div class="admin-form-help">
                    Wprowadź adres URL strony narzędzia, aby automatycznie pobrać zrzut ekranu lub logo/favicon.
                </div>

                <?php if (!empty($tool['screenshot'])): ?>
                    <div style="margin-top: 15px;">
                        <strong>Aktualny zrzut ekranu:</strong>
                        <div style="margin-top: 10px; display: flex; flex-direction: column; align-items: flex-start; gap: 10px;">
                            <img src="../images/<?php echo htmlspecialchars($tool['image_type'] === 'screenshot' ? $tool['screenshot'] : $tool['logo']); ?>?v=<?php echo time(); ?>" alt="Current Screenshot" style="max-width: 300px; border: 1px solid #ddd; border-radius: 4px;" class="image-preview">

                            <div class="screenshot-controls" style="display: flex; gap: 10px;">
                                <button type="button" id="accept-screenshot" class="btn btn-success btn-sm">Akceptuj</button>
                                <button type="button" id="reject-screenshot" class="btn btn-danger btn-sm">Odrzuć</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Podgląd narzędzia -->
            <div class="admin-form-group">
                <label class="admin-form-label">Podgląd narzędzia</label>
                <div id="tool-preview" style="margin-top: 10px; border: 1px solid #ddd; border-radius: 4px; padding: 15px; background-color: #f9f9f9;">
                    <div style="display: flex; align-items: flex-start; gap: 15px;">
                        <div style="flex: 0 0 80px;">
                            <?php
                            // Wybierz właściwy obrazek w zależności od typu obrazu
                            $image_to_display = !empty($tool['logo']) ? $tool['logo'] : 'default-tool-logo.png';

                            // Jeśli typ obrazu to 'screenshot', użyj zrzutu ekranu (jeśli istnieje)
                            if (isset($tool['image_type']) && $tool['image_type'] === 'screenshot' && !empty($tool['screenshot'])) {
                                $image_to_display = $tool['screenshot'];
                            }
                            ?>
                            <img id="preview-logo" src="<?php echo '../images/' . htmlspecialchars($image_to_display); ?>"
                                 alt="Logo" style="width: 80px; height: 80px; object-fit: contain; border-radius: 4px;">
                        </div>
                        <div style="flex: 1;">
                            <h3 id="preview-name" style="margin-top: 0; margin-bottom: 5px;"><?php echo htmlspecialchars($tool['name'] ?: 'Nazwa narzędzia'); ?></h3>
                            <div id="preview-description" style="font-size: 14px; color: #666;">
                                <?php echo !empty($tool['description']) ? $tool['description'] : 'Opis narzędzia...'; ?>
                            </div>
                            <div style="margin-top: 10px;">
                                <span id="preview-category" class="badge" style="background-color: #007bff; color: white; font-size: 12px; padding: 3px 8px; border-radius: 4px;">
                                    <?php
                                    if (!empty($tool['category_id'])) {
                                        foreach ($categories as $category) {
                                            if ($category['id'] == $tool['category_id']) {
                                                echo htmlspecialchars($category['name']);
                                                break;
                                            }
                                            if (!empty($category['subcategories'])) {
                                                foreach ($category['subcategories'] as $subcategory) {
                                                    if ($subcategory['id'] == $tool['category_id']) {
                                                        echo htmlspecialchars($subcategory['name']);
                                                        break 2;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        echo 'Kategoria';
                                    }
                                    ?>
                                </span>
                                <span id="preview-pricing" class="badge" style="background-color: #28a745; color: white; font-size: 12px; padding: 3px 8px; border-radius: 4px; margin-left: 5px;">
                                    <?php
                                    $pricing_labels = [
                                        'free' => 'Darmowe',
                                        'freemium' => 'Freemium',
                                        'paid' => 'Płatne',
                                        'contact' => 'Kontakt'
                                    ];
                                    echo $pricing_labels[$tool['pricing_type']] ?? 'Darmowe';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="admin-form-help">
                    Podgląd narzędzia z aktualnymi zmianami. Aktualizuje się po wpisaniu danych.
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="admin-form-actions">
        <a href="tools.php" class="btn btn-secondary">Anuluj</a>
        <button type="submit" class="btn btn-primary">
            <?php echo isset($tool['id']) ? 'Zapisz zmiany' : 'Dodaj narzędzie'; ?>
        </button>
    </div>
</form>

<!-- Dodaj kod JavaScript potrzebny do obsługi tagów -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obsługa wyszukiwania i dodawania tagów
    function initTagsInput() {
        const tagInput = document.querySelector('.tag-input');
        const addTagBtn = document.querySelector('.add-tag-btn');
        const selectedTagsContainer = document.getElementById('selected-tags-container');

        if (!tagInput || !addTagBtn || !selectedTagsContainer) {
            console.warn('Elementy formularza tagów nie zostały znalezione');
            return;
        }

        // Funkcja do dodawania tagu
        function addTag(tagName, tagId) {
            // Sprawdź czy tag już istnieje
            const existingTag = document.querySelector(`.selected-tag[data-id="${tagId}"]`);
            if (existingTag) return;

            const tagElement = document.createElement('div');
            tagElement.className = 'selected-tag';
            tagElement.dataset.id = tagId;

            // Przygotuj wartość dla inputa - jeśli tagId jest stringiem (a nie liczbą),
            // dodajemy przedrostek 'new:' aby serwer wiedział, że trzeba utworzyć nowy tag
            const tagValue = isNaN(tagId) ? 'new:' + tagName : tagId;

            tagElement.innerHTML = `
                <span>${tagName}</span>
                <input type="hidden" name="tags[]" value="${tagValue}">
                <button type="button" class="remove-tag-btn" style="background: none; border: none; color: #999; margin-left: 5px; cursor: pointer;">&times;</button>
            `;

            selectedTagsContainer.appendChild(tagElement);

            // Dodaj obsługę usuwania tagu
            const removeBtn = tagElement.querySelector('.remove-tag-btn');
            removeBtn.addEventListener('click', function() {
                tagElement.remove();
            });

            // Wyczyść pole wejściowe
            tagInput.value = '';
            tagInput.focus();
        }

        // Obsługa przycisku dodawania tagu
        addTagBtn.addEventListener('click', function() {
            const tagName = tagInput.value.trim();
            if (tagName) {
                // Sprawdź czy tag istnieje, jeśli tak - dodaj istniejący, jeśli nie - stwórz nowy
                fetch(`ajax/search-tags.php?query=${encodeURIComponent(tagName)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            // Znaleziono dopasowanie, użyj pierwszego
                            addTag(data[0].name, data[0].id);
                        } else {
                            // Stwórz nowy tag na serwerze i uzyskaj jego ID
                            // Najpierw musimy utworzyć tag na serwerze
                            const formData = new FormData();
                            formData.append('create_tag', '1');
                            formData.append('tag_name', tagName);

                            fetch('ajax/create-tag.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(newTag => {
                                if (newTag.success && newTag.id) {
                                    // Dodaj tag z otrzymanym ID
                                    addTag(tagName, newTag.id);
                                } else {
                                    // Jeśli nie można utworzyć tagu, używamy samej nazwy
                                    addTag(tagName, tagName);
                                }
                            })
                            .catch(error => {
                                console.error('Błąd tworzenia tagu:', error);
                                // W przypadku błędu, dodaj jako tekst
                                addTag(tagName, tagName);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Błąd wyszukiwania tagów:', error);
                        // W przypadku błędu, dodaj jako tekst
                        addTag(tagName, tagName);
                    });
            }
        });

        // Obsługa klawisza Enter w polu wyszukiwania
        tagInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTagBtn.click();
            }
        });

        // Podpowiedzi dla tagów (autocomplete)
        let searchTimeout;
        tagInput.addEventListener('input', function() {
            const query = this.value.trim();

            // Wyczyść poprzedni timeout
            clearTimeout(searchTimeout);

            if (query.length < 2) return;

            // Dodaj opóźnienie, aby nie wysyłać żądania po każdym naciśnięciu klawisza
            searchTimeout = setTimeout(() => {
                fetch(`ajax/search-tags.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        // Usuń poprzednie podpowiedzi
                        const existingDropdown = document.querySelector('.tag-suggestions');
                        if (existingDropdown) existingDropdown.remove();

                        if (data.length > 0) {
                            // Utwórz dropdown z podpowiedziami
                            const dropdown = document.createElement('div');
                            dropdown.className = 'tag-suggestions';
                            dropdown.style.cssText = 'position:absolute; background:#fff; border:1px solid #ddd; max-height:200px; overflow-y:auto; width:100%; z-index:1000; border-radius:4px; box-shadow:0 4px 8px rgba(0,0,0,0.1);';

                            data.forEach(tag => {
                                const item = document.createElement('div');
                                item.className = 'tag-suggestion-item';
                                item.textContent = tag.name;
                                item.style.cssText = 'padding:8px 12px; cursor:pointer; border-bottom:1px solid #eee;';

                                item.addEventListener('mouseenter', function() {
                                    this.style.backgroundColor = '#f0f0f0';
                                });

                                item.addEventListener('mouseleave', function() {
                                    this.style.backgroundColor = '#fff';
                                });

                                item.addEventListener('click', function() {
                                    addTag(tag.name, tag.id);
                                    dropdown.remove();
                                });

                                dropdown.appendChild(item);
                            });

                            // Dodaj dropdown pod polem wejściowym
                            const inputContainer = tagInput.parentElement;
                            inputContainer.style.position = 'relative';
                            inputContainer.appendChild(dropdown);

                            // Zamknij dropdown po kliknięciu poza nim
                            document.addEventListener('click', function closeDropdown(e) {
                                if (!dropdown.contains(e.target) && e.target !== tagInput) {
                                    dropdown.remove();
                                    document.removeEventListener('click', closeDropdown);
                                }
                            });
                        }
                    })
                    .catch(error => console.error('Błąd wyszukiwania tagów:', error));
            }, 300);
        });

        // Inicjalizacja istniejących przycisków usuwania tagów
        document.querySelectorAll('.remove-tag-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.selected-tag').remove();
            });
        });
    }

    // Inicjalizacja obsługi tagów po załadowaniu strony
    initTagsInput();

    // Naprawiona obsługa formularza acceptance zrzutów ekranu
    const acceptScreenshotBtn = document.getElementById('accept-screenshot');
    if (acceptScreenshotBtn) {
        acceptScreenshotBtn.addEventListener('click', function() {
            if (confirm('Czy na pewno chcesz zaakceptować ten zrzut ekranu?')) {
                // Najpierw test endpointu AJAX
                console.log('Przeprowadzam test AJAX...');

                // Test prostego endpointu JSON
                const testXhr = new XMLHttpRequest();
                testXhr.open('GET', 'ajax/test-json.php', true);
                testXhr.onload = function() {
                    console.log('Test odpowiedź:', testXhr.responseText);
                    try {
                        const testResponse = JSON.parse(testXhr.responseText);
                        console.log('Test JSON działa poprawnie:', testResponse);
                        // Jeśli test się powiódł, wykonaj właściwe żądanie
                        sendAcceptRequest();
                    } catch (e) {
                        console.error('Test JSON nie działa:', e);
                        alert('Wystąpił problem z obsługą AJAX. Sprawdź konsolę.');
                    }
                };
                testXhr.onerror = function() {
                    console.error('Błąd podczas testu AJAX');
                    alert('Nie można połączyć się z serwerem.');
                };
                testXhr.send();

                // Funkcja wykonująca właściwe żądanie
                function sendAcceptRequest() {
                    const toolId = <?php echo isset($tool['id']) ? (int)$tool['id'] : 0; ?>;
                    const screenshot = '<?php echo isset($tool['screenshot']) ? htmlspecialchars($tool['screenshot']) : ''; ?>';

                    // Wyświetl dane do debugowania
                    console.log('Wysyłam: tool_id=' + toolId + ', screenshot=' + screenshot);

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'ajax/accept-screenshot.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    // Naprawiona obsługa odpowiedzi
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            // Wyświetl surową odpowiedź do debugowania
                            console.log('Odpowiedź serwera:', xhr.responseText);

                            // Wykrywanie HTML zamiast JSON - szukamy tylko błędu parsowania, nie ostrzeżeń
                            if (xhr.responseText.trim().startsWith('<!DOCTYPE html>') ||
                                xhr.responseText.trim().startsWith('<html')) {
                                alert('Serwer zwrócił błąd PHP. Sprawdź konsolę deweloperską.');
                                console.error('Serwer zwrócił HTML zamiast JSON:', xhr.responseText);
                                return;
                            }

                            try {
                                // Ekstrakcja JSON z odpowiedzi zawierającej ostrzeżenia PHP
                                let jsonString = xhr.responseText;

                                // Jeśli odpowiedź zawiera notice/warning z PHP, wyodrębnij tylko część JSON
                                const jsonStartPos = xhr.responseText.indexOf('{');
                                if (jsonStartPos > 0) {
                                    console.log('Wykryto treść przed JSON, próba wyodrębnienia');
                                    jsonString = xhr.responseText.substring(jsonStartPos);
                                }

                                const response = JSON.parse(jsonString);
                                if (response.success) {
                                    alert('Zrzut ekranu został zaakceptowany: ' + response.message);
                                    // Opcjonalnie - odśwież stronę aby zobaczyć zmiany
                                    window.location.reload();
                                } else {
                                    alert('Wystąpił błąd: ' + response.message);
                                }
                            } catch (e) {
                                console.error('Błąd parsowania JSON:', e);
                                console.error('Odpowiedź serwera:', xhr.responseText);
                                alert('Wystąpił błąd podczas przetwarzania odpowiedzi serwera. Sprawdź konsolę deweloperską.');
                            }
                        } else {
                            alert('Wystąpił błąd podczas przetwarzania żądania: ' + xhr.status);
                            console.error('Status błędu:', xhr.status, xhr.statusText);
                            console.error('Odpowiedź serwera:', xhr.responseText);
                        }
                    };

                    xhr.onerror = function() {
                        alert('Wystąpił błąd sieci. Sprawdź połączenie.');
                        console.error('Błąd sieci:', xhr);
                    };

                    xhr.send('tool_id=' + toolId + '&screenshot=' + encodeURIComponent(screenshot));
                }
            }
        });
    }

    // Inicjalizacja edytora TipTap
    function initializeTipTap() {
        // Najpierw główny edytor opisu
        const descriptionEditor = document.getElementById('description-editor');
        const descriptionInput = document.getElementById('description');
        const descriptionToolbar = document.getElementById('description-toolbar');

        if (descriptionEditor && descriptionInput) {
            // Ustaw początkową zawartość edytora z ukrytego pola input
            descriptionEditor.innerHTML = descriptionInput.value;

            // Obsługa przycisków paska narzędzi
            if (descriptionToolbar) {
                const buttons = descriptionToolbar.querySelectorAll('button');

                buttons.forEach(button => {
                    button.addEventListener('click', function() {
                        const command = this.getAttribute('data-command');

                        // Prosta implementacja podstawowych poleceń formatowania
                        switch (command) {
                            case 'bold':
                                document.execCommand('bold', false, null);
                                break;
                            case 'italic':
                                document.execCommand('italic', false, null);
                                break;
                            case 'underline':
                                document.execCommand('underline', false, null);
                                break;
                            case 'orderedList':
                                document.execCommand('insertOrderedList', false, null);
                                break;
                            case 'bulletList':
                                document.execCommand('insertUnorderedList', false, null);
                                break;
                            case 'link':
                                const url = prompt('Wprowadź adres URL:', 'https://');
                                if (url) {
                                    document.execCommand('createLink', false, url);
                                }
                                break;
                            case 'image':
                                const imgUrl = prompt('Wprowadź adres URL obrazu:', 'https://');
                                if (imgUrl) {
                                    document.execCommand('insertImage', false, imgUrl);
                                }
                                break;
                            case 'clear':
                                document.execCommand('removeFormat', false, null);
                                break;
                        }

                        // Aktualizuj ukryte pole po każdej zmianie
                        descriptionInput.value = descriptionEditor.innerHTML;
                    });
                });
            }

            // Aktualizuj ukryte pole po wprowadzeniu zmian w edytorze
            descriptionEditor.addEventListener('input', function() {
                descriptionInput.value = this.innerHTML;
            });

            // Ustaw edytor jako edytowalny
            descriptionEditor.contentEditable = true;
        }

        // Inicjalizacja edytorów dla tłumaczeń
        document.querySelectorAll('.language-content').forEach(langContent => {
            const langCode = langContent.getAttribute('data-lang');
            const editor = document.getElementById(`editor-${langCode}`);
            const input = document.getElementById(`trans_description_${langCode}`);
            const toolbar = document.getElementById(`toolbar-${langCode}`);

            if (editor && input) {
                // Ustaw początkową zawartość edytora
                editor.innerHTML = input.value;

                // Obsługa przycisków paska narzędzi
                if (toolbar) {
                    const buttons = toolbar.querySelectorAll('button');

                    buttons.forEach(button => {
                        button.addEventListener('click', function() {
                            const command = this.getAttribute('data-command');

                            // Implementacja taka sama jak wyżej
                            // Najpierw fokus na właściwym edytorze
                            editor.focus();

                            switch (command) {
                                case 'bold':
                                    document.execCommand('bold', false, null);
                                    break;
                                case 'italic':
                                    document.execCommand('italic', false, null);
                                    break;
                                case 'underline':
                                    document.execCommand('underline', false, null);
                                    break;
                                case 'orderedList':
                                    document.execCommand('insertOrderedList', false, null);
                                    break;
                                case 'bulletList':
                                    document.execCommand('insertUnorderedList', false, null);
                                    break;
                                case 'link':
                                    const url = prompt('Wprowadź adres URL:', 'https://');
                                    if (url) {
                                        document.execCommand('createLink', false, url);
                                    }
                                    break;
                                case 'image':
                                    const imgUrl = prompt('Wprowadź adres URL obrazu:', 'https://');
                                    if (imgUrl) {
                                        document.execCommand('insertImage', false, imgUrl);
                                    }
                                    break;
                                case 'clear':
                                    document.execCommand('removeFormat', false, null);
                                    break;
                            }

                            // Aktualizuj ukryte pole
                            input.value = editor.innerHTML;
                        });
                    });
                }

                // Aktualizuj ukryte pole po wprowadzeniu zmian
                editor.addEventListener('input', function() {
                    input.value = this.innerHTML;
                });

                // Ustaw edytor jako edytowalny
                editor.contentEditable = true;
            }
        });
    }

    // Wywołaj funkcję inicjalizacji edytora po załadowaniu DOM
    initializeTipTap();
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
