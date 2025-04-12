<?php
// Prosty skrypt do generowania zrzutów ekranu

// Włącz logowanie błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Połączenie z bazą danych
require_once 'includes/config.php';
require_once 'includes/db.php';

// Katalog na miniatury
$output_dir = 'images/thumbnails/';
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Katalog na zrzuty ekranu
$screenshots_dir = 'images/screenshots/';
if (!file_exists($screenshots_dir)) {
    mkdir($screenshots_dir, 0755, true);
}

// Obsługa bezpośredniego żądania URL
if (isset($_GET['url']) && !empty($_GET['url'])) {
    $url = $_GET['url'];
    $image_type = isset($_GET['type']) ? $_GET['type'] : 'auto';

    // Zapisujemy informacje do logów
    error_log("Screenshot request: URL=$url, Type=$image_type");

    // Jeśli żądany jest konkretnie zrzut ekranu, używamy thum.io
    if ($image_type === 'screenshot') {
        // Tworzymy URL do thum.io API dla pełnego zrzutu ekranu
        $thum_io_url = "//image.thum.io/get/width/800/png/" . urlencode($url);
        error_log("Calling thum.io API: $thum_io_url");

        // Przekieruj do thum.io lub pobierz i przekaż zrzut
        header("Location: https:" . $thum_io_url);
        exit;
    }

    // Tryb automatyczny - pobieramy favicon jako fallback
    if ($image_type === 'auto' || $image_type === 'favicon') {
        // Spróbuj pobrać favicon jako backup
        $favicon_data = get_favicon($url);

        if ($favicon_data !== false) {
            // Zwracamy favicon jako obrazek
            header('Content-Type: image/png');
            header('X-Image-Type: favicon');
            echo $favicon_data;
            exit;
        }
    }

    // Jeśli żadna z metod nie działa, używamy domyślnego obrazu
    $default_image_path = 'images/default-tool-logo.png';
    if (file_exists($default_image_path)) {
        $default_image = file_get_contents($default_image_path);
        header('Content-Type: image/png');
        header('X-Image-Type: default');
        echo $default_image;
        exit;
    } else {
        // Zwróć błąd jeśli nawet domyślny obrazek nie istnieje
        header('HTTP/1.0 404 Not Found');
        echo "Nie znaleziono obrazka";
        exit;
    }
}

// Generowanie zrzutu ekranu
function make_screenshot($url, $tool_id)
{
    global $db, $output_dir, $screenshots_dir;

    // Sprawdź URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) return "Błąd: Nieprawidłowy URL";

    error_log("Trying to get screenshot for tool ID: $tool_id, URL: $url");

    // Używamy thum.io do pobierania zrzutów ekranu
    $thum_io_url = "https://image.thum.io/get/width/800/png/" . $url;

    // Pobierz obrazek
    $context = stream_context_create([
        'http' => [
            'timeout' => 20, // Czas oczekiwania w sekundach
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]);

    $image = @file_get_contents($thum_io_url, false, $context);

    if (!$image) {
        error_log("Failed to get screenshot from thum.io");

        // Fallback do favicon jeśli zrzut ekranu nie zadziałał
        $favicon_data = get_favicon($url);
        $image_type = 'favicon'; // To będzie favicon

        if ($favicon_data !== false) {
            // Nazwa pliku dla faviconu
            $file_name = create_slug($tool_id) . "-favicon-" . time() . ".png";
            $file_path = $screenshots_dir . $file_name;

            // Zapisz plik
            if (!file_put_contents($file_path, $favicon_data)) {
                return "Błąd: Nie udało się zapisać pliku favicon";
            }

            // Ścieżka względna do pliku
            $relative_path = 'screenshots/' . $file_name;

            // Aktualizuj bazę danych
            $stmt = $db->prepare("UPDATE tools SET screenshot = ?, logo = ?, image_type = ? WHERE id = ?");
            $stmt->bind_param('sssi', $relative_path, $relative_path, $image_type, $tool_id);
            if (!$stmt->execute()) {
                return "Błąd bazy danych: " . $db->error;
            }

            return "Sukces: Zapisano ikonę strony jako miniaturę";
        }

        return "Błąd: Nie udało się pobrać zrzutu ekranu ani favicon";
    }

    // Nazwa pliku dla zrzutu ekranu
    $file_name = create_slug($tool_id) . "-screenshot-" . time() . ".png";
    $file_path = $screenshots_dir . $file_name;
    $image_type = 'screenshot'; // To jest pełny zrzut ekranu

    // Zapisz plik
    if (!file_put_contents($file_path, $image)) {
        error_log("Failed to save screenshot file");
        return "Błąd: Nie udało się zapisać pliku";
    }

    // Ścieżka względna do pliku
    $relative_path = 'screenshots/' . $file_name;

    // Aktualizuj bazę danych z typem obrazu
    $stmt = $db->prepare("UPDATE tools SET screenshot = ?, logo = ?, image_type = ? WHERE id = ?");
    $stmt->bind_param('sssi', $relative_path, $relative_path, $image_type, $tool_id);
    if (!$stmt->execute()) {
        error_log("Database error: " . $db->error);
        return "Błąd bazy danych: " . $db->error;
    }

    return "Sukces: Zapisano zrzut ekranu";
}

// Helper function to create slug
function create_slug($text) {
    // Convert to string if not already
    $text = (string)$text;
    // Remove all characters that are not alphanumeric, a dash, or an underscore
    $text = preg_replace('/[^a-z0-9\-\_]/', '-', strtolower($text));
    // Replace multiple dashes with a single dash
    $text = preg_replace('/\-+/', '-', $text);
    // Trim dashes from the beginning and end
    $text = trim($text, '-');
    return $text;
}

// Pobieranie favicon jako alternatywa
function get_favicon($url)
{
    // Wyodrębnij domenę
    $parsed = parse_url($url);
    $domain = isset($parsed['host']) ? $parsed['host'] : '';
    if (empty($domain)) return false;

    // Potencjalne lokalizacje favicon
    $favicon_urls = [
        "https://$domain/favicon.ico",
        "https://$domain/favicon.png",
        "https://www.$domain/favicon.ico",
        "https://$domain/apple-touch-icon.png",
        "https://$domain/apple-touch-icon-precomposed.png",
        "https://$domain/assets/favicon.ico",
        "https://$domain/assets/images/favicon.ico",
        "https://$domain/wp-content/themes/theme/favicon.ico",
    ];

    // Spróbuj pobrać favicon
    foreach ($favicon_urls as $favicon_url) {
        $favicon = @file_get_contents($favicon_url);
        if ($favicon) {
            return $favicon;
        }
    }

    // Dodatkowa próba poprzez Google Favicon API
    $google_favicon_url = "https://www.google.com/s2/favicons?domain=$domain&sz=64";
    $favicon = @file_get_contents($google_favicon_url);
    if ($favicon) {
        return $favicon;
    }

    return false;
}

// Obsługa żądania
$message = "";
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $db->query("SELECT id, name, url FROM tools WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        // Wykonaj zrzut ekranu
        $message = make_screenshot($row['url'], $row['id']);
        // Rezultat przetwarzania logujemy
        error_log("Screenshot process result: $message");
    }
}

// Lista narzędzi
$tools = [];
$result = $db->query("SELECT id, name, url, screenshot, image_type FROM tools ORDER BY id DESC LIMIT 50");
while ($row = $result->fetch_assoc()) {
    $tools[] = $row;
}
?>
