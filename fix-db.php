<!-- Skrypt do naprawy bazy danych i utworzenia podkategorii -->
<?php
// Włącz raportowanie wszystkich błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Załaduj konfigurację
require_once 'includes/config.php';

// Zacznij zbierać output do bufora
ob_start();

echo '<h1>Aktualizacja struktury bazy danych</h1>';

// Sprawdź czy kolumna parent_id istnieje
echo '<h2>Sprawdzanie struktury kategorii...</h2>';
$sql = "SHOW COLUMNS FROM categories LIKE 'parent_id'";
$result = $conn->query($sql);

if ($result && $result->num_rows == 0) {
    echo '<p style="color: orange;">Dodawanie kolumny parent_id do tabeli categories...</p>';

    $sql = "ALTER TABLE categories ADD COLUMN parent_id INT DEFAULT NULL";
    if ($conn->query($sql)) {
        echo '<p style="color: green;">Kolumna parent_id została dodana.</p>';

        $sql = "ALTER TABLE categories ADD FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL";
        if ($conn->query($sql)) {
            echo '<p style="color: green;">Dodano klucz obcy dla parent_id.</p>';
        } else {
            echo '<p style="color: red;">Błąd podczas dodawania klucza obcego: ' . $conn->error . '</p>';
        }
    } else {
        echo '<p style="color: red;">Błąd podczas dodawania kolumny: ' . $conn->error . '</p>';
    }
} else {
    echo '<p style="color: green;">Kolumna parent_id już istnieje w tabeli categories.</p>';
}

// Aktualizacja liczby narzędzi w kategoriach
echo '<h2>Aktualizacja liczby narzędzi w kategoriach...</h2>';
if (function_exists('update_category_counts')) {
    if (update_category_counts()) {
        echo '<p style="color: green;">Liczba narzędzi w kategoriach została zaktualizowana.</p>';
    } else {
        echo '<p style="color: red;">Wystąpił błąd podczas aktualizacji liczby narzędzi.</p>';
    }
} else {
    echo '<p style="color: red;">Funkcja update_category_counts() nie istnieje.</p>';

    // Alternatywna metoda aktualizacji liczby narzędzi
    echo '<p>Próba alternatywnej aktualizacji licznika narzędzi...</p>';

    $sql = "SELECT id FROM categories";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $updated = 0;
        while ($row = $result->fetch_assoc()) {
            $category_id = $row['id'];

            // Pobierz liczbę narzędzi
            $count_sql = "SELECT COUNT(*) as count FROM tools WHERE category_id = " . $category_id;
            $count_result = $conn->query($count_sql);

            if ($count_result && $count_result->num_rows > 0) {
                $count_row = $count_result->fetch_assoc();
                $count = $count_row['count'];

                // Aktualizuj kategorię
                $update_sql = "UPDATE categories SET count = " . $count . " WHERE id = " . $category_id;
                if ($conn->query($update_sql)) {
                    $updated++;
                }
            }
        }

        echo "<p style=\"color: green;\">Zaktualizowano liczniki dla $updated kategorii.</p>";
    }
}

// Dodawanie przykładowych podkategorii, jeśli potrzebne
echo '<h2>Sprawdzanie podkategorii...</h2>';
$sql = "SELECT COUNT(*) as count FROM categories WHERE parent_id IS NOT NULL";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $count = $row['count'];

    if ($count > 0) {
        echo "<p style=\"color: green;\">Znaleziono $count podkategorii. Nie są potrzebne żadne zmiany.</p>";
    } else {
        echo '<p style="color: orange;">Nie znaleziono podkategorii. Tworzenie przykładowych podkategorii...</p>';

        // Przykładowa struktura kategorii
        $categories_structure = [
            'text-writing' => ['translate', 'copywriting', 'email-writer', 'paraphrase', 'summarize'],
            'image-generation' => ['photo-editing', 'art-generation', 'logo-design', 'background-removal'],
            'video-generation' => ['video-editing', 'animation', 'motion-graphics', 'subtitles'],
            'audio-generation' => ['text-to-speech', 'music-generation', 'podcast-tools', 'voice-cloning'],
            'coding' => ['code-generation', 'code-completion', 'debugging', 'code-explanation'],
            'business' => ['finance', 'marketing', 'analytics', 'presentation']
        ];

        // Najpierw znajdź istniejące główne kategorie
        foreach ($categories_structure as $main_slug => $subcategories) {
            $sql = "SELECT id, name FROM categories WHERE slug LIKE '%" . $conn->real_escape_string($main_slug) . "%'";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $main_category = $result->fetch_assoc();
                $parent_id = $main_category['id'];
                $parent_name = $main_category['name'];

                echo "<p>Znaleziono kategorię główną: $parent_name (ID: $parent_id)</p>";

                // Dodaj podkategorie
                foreach ($subcategories as $subcategory_slug) {
                    $subcategory_name = ucwords(str_replace('-', ' ', $subcategory_slug));

                    $sql = "INSERT INTO categories (name, slug, parent_id, description) VALUES (
                        '" . $conn->real_escape_string($subcategory_name) . "',
                        '" . $conn->real_escape_string($subcategory_slug) . "',
                        " . $parent_id . ",
                        'Subcategory of " . $conn->real_escape_string($parent_name) . "'
                    )";

                    if ($conn->query($sql)) {
                        echo "<p style=\"color: green;\">Utworzono podkategorię: $subcategory_name</p>";
                    } else {
                        echo "<p style=\"color: red;\">Błąd podczas tworzenia podkategorii $subcategory_name: " . $conn->error . "</p>";
                    }
                }
            }
        }
    }
}

// Zakończ i wypisz output
$output = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktualizacja bazy danych</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #333;
        }
        p {
            margin-bottom: 10px;
        }
        .success {
            color: green;
        }
        .warning {
            color: orange;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <?php echo $output; ?>

    <hr>
    <p>Po zakończeniu aktualizacji struktury bazy danych, możesz przejść do <a href="import-categories.php">importu kategorii</a>.</p>
    <p><a href="/">Powrót do strony głównej</a></p>
</body>
</html>
