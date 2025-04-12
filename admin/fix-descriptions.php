<?php
/**
 * Skrypt naprawiający podwójnie zakodowane opisy narzędzi w bazie danych
 *
 * Ten skrypt sprawdza wszystkie narzędzia i ich tłumaczenia, poszukując
 * podwójnie zakodowanych opisów (np. &lt;p&gt; zamiast <p>) i naprawia je.
 */

// Włącz wyświetlanie błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Buforowanie wyjścia, aby uniknąć problemów z nagłówkami
ob_start();

// Wymagane pliki
require_once '../includes/config.php';
require_once '../includes/db_config.php';

// Sprawdź, czy użytkownik jest zalogowany i ma uprawnienia administratora
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit;
}

$processed = 0;
$fixed = 0;

// Funkcja do odkodowania podwójnie zakodowanego HTML
function decode_double_encoded_html($text) {
    // Sprawdź, czy tekst zawiera zakodowane tagi HTML
    if (strpos($text, '&lt;') !== false) {
        // Odkoduj pierwsze poziom kodowania
        $decoded = html_entity_decode($text);
        return $decoded;
    }

    return $text;
}

// Napraw opisy w głównej tabeli narzędzi
$sql = "SELECT id, description FROM tools";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $processed++;
        $original = $row['description'];
        $fixed_description = decode_double_encoded_html($original);

        // Jeśli opis został zmieniony, zaktualizuj w bazie danych
        if ($fixed_description !== $original) {
            $fixed++;

            // Zabezpiecz tekst przed wstrzyknięciem SQL
            $fixed_description = $conn->real_escape_string($fixed_description);

            $update_sql = "UPDATE tools SET description = '$fixed_description' WHERE id = " . (int)$row['id'];
            $conn->query($update_sql);

            echo "Naprawiono opis narzędzia ID: " . $row['id'] . "<br>";
            echo "Oryginał: " . htmlspecialchars(substr($original, 0, 100)) . "...<br>";
            echo "Po naprawie: " . htmlspecialchars(substr($fixed_description, 0, 100)) . "...<br><hr>";
        }
    }
}

// Napraw opisy w tłumaczeniach narzędzi
$sql = "SELECT tool_id, language_code, description FROM tool_translations";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $processed++;
        $original = $row['description'];
        $fixed_description = decode_double_encoded_html($original);

        // Jeśli opis został zmieniony, zaktualizuj w bazie danych
        if ($fixed_description !== $original) {
            $fixed++;

            // Zabezpiecz tekst przed wstrzyknięciem SQL
            $fixed_description = $conn->real_escape_string($fixed_description);

            $update_sql = "UPDATE tool_translations SET description = '$fixed_description'
                           WHERE tool_id = " . (int)$row['tool_id'] . "
                           AND language_code = '" . $conn->real_escape_string($row['language_code']) . "'";
            $conn->query($update_sql);

            echo "Naprawiono opis tłumaczenia narzędzia ID: " . $row['tool_id'] .
                 ", język: " . $row['language_code'] . "<br>";
            echo "Oryginał: " . htmlspecialchars(substr($original, 0, 100)) . "...<br>";
            echo "Po naprawie: " . htmlspecialchars(substr($fixed_description, 0, 100)) . "...<br><hr>";
        }
    }
}

// Podsumowanie
echo "<h2>Podsumowanie</h2>";
echo "Przetworzono opisów: $processed<br>";
echo "Naprawiono opisów: $fixed<br>";

// Przycisk powrotu do panelu
echo '<div style="margin-top: 20px;">
    <a href="index.php" class="btn btn-primary">Powrót do panelu</a>
</div>';

// Zakończ buforowanie
ob_end_flush();
?>
