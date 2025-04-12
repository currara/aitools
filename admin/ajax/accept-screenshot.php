<?php
/**
 * Plik AJAX do akceptacji zrzutów ekranu jako logo narzędzia
 */

// Wyłącz wyświetlanie błędów na wyjściu, ale zapisuj je do logu
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../../error_ajax.log');

// Funkcja do debugowania
function debug_to_file($message, $data = null) {
    $log = date('[Y-m-d H:i:s] ') . $message;
    if ($data !== null) {
        $log .= "\n" . print_r($data, true);
    }
    error_log($log . "\n", 3, '../../error_ajax.log');
}

// Włącz nagłówki JSON
header('Content-Type: application/json');

// Początek debugowania
debug_to_file('Start accept-screenshot.php');
debug_to_file('POST data:', $_POST);

// Sprawdź, czy żądanie jest typu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Niedozwolona metoda żądania']);
    exit;
}

try {
    // Rejestruj wszystkie zmienne
    debug_to_file('Początek try bloku');

    // Połączenie z bazą danych
    debug_to_file('Przed połączeniem z bazą');
    require_once '../../includes/config.php';
    require_once '../../includes/db_config.php';
    require_once '../../includes/functions.php';
    debug_to_file('Po połączeniu z bazą');

    // Sprawdź sesję - używamy session_status() zamiast ponownego uruchamiania sesji
    debug_to_file('Status sesji: ' . session_status());
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    debug_to_file('Dane sesji:', $_SESSION);

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Brak zalogowanego użytkownika']);
        exit;
    }

    // Pobierz parametry
    $tool_id = isset($_POST['tool_id']) ? (int)$_POST['tool_id'] : 0;
    $screenshot = isset($_POST['screenshot']) ? trim($_POST['screenshot']) : '';
    debug_to_file("ID narzędzia: $tool_id, Screenshot: $screenshot");

    // Sprawdź poprawność parametrów
    if ($tool_id <= 0 || empty($screenshot)) {
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowe parametry']);
        exit;
    }

    // Sprawdź, czy narzędzie istnieje
    debug_to_file("Szukam narzędzia o ID: $tool_id");
    $tool = get_tool($tool_id);
    debug_to_file("Rezultat get_tool:", $tool);

    if (!$tool) {
        echo json_encode(['success' => false, 'message' => 'Narzędzie nie istnieje']);
        exit;
    }

    // Sprawdź, czy plik zrzutu ekranu istnieje - teraz obsługujemy różne ścieżki
    $possible_paths = [
        '../../images/' . $screenshot,
        '../../' . $screenshot,
        '../..' . $screenshot, // jeśli ścieżka zaczyna się od /
    ];

    $file_exists = false;
    $used_path = '';

    foreach ($possible_paths as $path) {
        debug_to_file("Sprawdzanie ścieżki: $path");
        if (file_exists($path)) {
            $file_exists = true;
            $used_path = $path;
            debug_to_file("Znaleziono plik: $path");
            break;
        }
    }

    if (!$file_exists) {
        echo json_encode([
            'success' => false,
            'message' => 'Plik zrzutu ekranu nie istnieje',
            'paths_checked' => $possible_paths
        ]);
        exit;
    }

    // Ustaw zrzut ekranu jako logo narzędzia
    global $conn;
    debug_to_file("Aktualizuję narzędzie w bazie danych");

    // Określ typ obrazu na podstawie nazwy pliku
    $image_type = 'screenshot';
    if (strpos($screenshot, 'favicon') !== false || strpos($screenshot, 'logo') !== false) {
        $image_type = 'favicon';
    }
    debug_to_file("Wykryty typ obrazu: $image_type");

    // Uproszczone zapytanie aktualizujące z poprawnym image_type
    $sql = "UPDATE tools SET logo = '" . $conn->real_escape_string($screenshot) . "',
                           screenshot = '" . $conn->real_escape_string($screenshot) . "',
                           image_type = '" . $conn->real_escape_string($image_type) . "'
            WHERE id = " . (int)$tool_id;

    debug_to_file("SQL: $sql");

    // Wykonaj zapytanie
    $result = $conn->query($sql);
    debug_to_file("Rezultat zapytania: " . ($result ? 'true' : 'false') . ", Error: " . $conn->error);

    if ($result) {
        // Zapisz log aktywności
        log_activity($_SESSION['user_id'], 'update', 'tool_logo', $tool_id);
        debug_to_file("Log aktywności zapisany");

        $response = [
            'success' => true,
            'message' => 'Zrzut ekranu został ustawiony jako logo narzędzia',
            'screenshot' => $screenshot,
            'logo' => $screenshot
        ];
        debug_to_file("Odpowiedź:", $response);
        echo json_encode($response);
    } else {
        $error = ['success' => false, 'message' => 'Błąd podczas aktualizacji bazy danych: ' . $conn->error];
        debug_to_file("Błąd SQL:", $error);
        echo json_encode($error);
    }
} catch (Exception $e) {
    $error = ['success' => false, 'message' => 'Wystąpił błąd: ' . $e->getMessage()];
    debug_to_file("Wyjątek:", $error);
    echo json_encode($error);
}
