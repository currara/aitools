<?php
// Include database configuration
require_once 'includes/config.php';

// Start output buffering
ob_start();

echo '<h1>Naprawa cyklicznych zależności w hierarchii kategorii</h1>';

// Sprawdź czy kolumna parent_id istnieje
echo '<h2>Sprawdzanie struktury kategorii...</h2>';
$sql = "SHOW COLUMNS FROM categories LIKE 'parent_id'";
$result = $conn->query($sql);

if ($result && $result->num_rows == 0) {
    echo '<p style="color: red;">Kolumna parent_id nie istnieje w tabeli categories. Najpierw uruchom skrypt fix-db.php.</p>';
    $output = ob_get_clean();
    echo $output;
    exit;
}

echo '<p style="color: green;">Kolumna parent_id istnieje w tabeli categories.</p>';

// Funkcja do wykrywania cykli w hierarchii kategorii
function detectCycles($conn) {
    $cycles = [];

    // Pobierz wszystkie kategorie z rodzicem
    $sql = "SELECT id, name, parent_id FROM categories WHERE parent_id IS NOT NULL";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categoryId = $row['id'];
            $categoryName = $row['name'];
            $parentId = $row['parent_id'];

            // Sprawdź, czy rodzic istnieje
            $checkParent = "SELECT id FROM categories WHERE id = " . (int)$parentId;
            $parentResult = $conn->query($checkParent);

            if (!$parentResult || $parentResult->num_rows == 0) {
                $cycles[] = [
                    'id' => $categoryId,
                    'name' => $categoryName,
                    'parent_id' => $parentId,
                    'issue' => 'Rodzic nie istnieje',
                    'action' => 'set_null'
                ];
                continue;
            }

            // Wykrywanie bezpośredniego zapętlenia (kategoria jest swoim własnym rodzicem)
            if ($categoryId == $parentId) {
                $cycles[] = [
                    'id' => $categoryId,
                    'name' => $categoryName,
                    'parent_id' => $parentId,
                    'issue' => 'Kategoria jest własnym rodzicem',
                    'action' => 'set_null'
                ];
                continue;
            }

            // Wykrywanie cykli w hierarchii (długość > 2)
            $visited = [$categoryId];
            $currentId = $parentId;

            while ($currentId != null) {
                if (in_array($currentId, $visited)) {
                    $cycles[] = [
                        'id' => $categoryId,
                        'name' => $categoryName,
                        'parent_id' => $parentId,
                        'issue' => 'Wykryto cykl w hierarchii: ' . implode(' -> ', $visited) . ' -> ' . $currentId,
                        'action' => 'set_null'
                    ];
                    break;
                }

                $visited[] = $currentId;

                // Znajdź rodzica bieżącej kategorii
                $parentSql = "SELECT parent_id FROM categories WHERE id = " . (int)$currentId;
                $parentResult = $conn->query($parentSql);

                if ($parentResult && $parentResult->num_rows > 0) {
                    $parentRow = $parentResult->fetch_assoc();
                    $currentId = $parentRow['parent_id'];
                } else {
                    $currentId = null;
                }
            }
        }
    }

    return $cycles;
}

// Funkcja do naprawy cykli
function fixCycles($conn, $cycles) {
    $fixed = 0;

    foreach ($cycles as $cycle) {
        if ($cycle['action'] == 'set_null') {
            $sql = "UPDATE categories SET parent_id = NULL WHERE id = " . (int)$cycle['id'];
            if ($conn->query($sql)) {
                echo '<p style="color: green;">Naprawiono kategorię: ' . htmlspecialchars($cycle['name']) . ' (ID: ' . $cycle['id'] . ') - ' . $cycle['issue'] . '</p>';
                $fixed++;
            } else {
                echo '<p style="color: red;">Błąd podczas naprawy kategorii ' . htmlspecialchars($cycle['name']) . ' (ID: ' . $cycle['id'] . '): ' . $conn->error . '</p>';
            }
        }
    }

    return $fixed;
}

// Sprawdź, czy mamy wykonać naprawę
$fix = isset($_GET['fix']) && $_GET['fix'] == '1';

// Wykryj cykle
echo '<h2>Wykrywanie cykli w hierarchii kategorii...</h2>';
$cycles = detectCycles($conn);

if (empty($cycles)) {
    echo '<p style="color: green;">Nie wykryto cykli w hierarchii kategorii.</p>';
} else {
    echo '<p style="color: orange;">Wykryto ' . count($cycles) . ' problemów w hierarchii kategorii:</p>';
    echo '<ul>';
    foreach ($cycles as $cycle) {
        echo '<li style="color: orange;">' . htmlspecialchars($cycle['name']) . ' (ID: ' . $cycle['id'] . ') - ' . $cycle['issue'] . '</li>';
    }
    echo '</ul>';

    if ($fix) {
        echo '<h2>Naprawianie cykli...</h2>';
        $fixedCount = fixCycles($conn, $cycles);
        echo '<p style="color: green;">Naprawiono ' . $fixedCount . ' z ' . count($cycles) . ' problemów.</p>';
    } else {
        echo '<p><a href="?fix=1" class="btn btn-warning" style="background-color: #ffa500; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">Napraw znalezione problemy</a></p>';
        echo '<p style="color: orange;"><strong>Uwaga:</strong> Naprawa ustawi parent_id = NULL dla wszystkich problematycznych kategorii.</p>';
    }
}

// Update category counts after fixes
if ($fix && !empty($cycles)) {
    echo '<h2>Aktualizacja liczników narzędzi...</h2>';
    if (function_exists('update_category_counts')) {
        try {
            if (update_category_counts()) {
                echo '<p style="color: green;">Liczba narzędzi w kategoriach została zaktualizowana.</p>';
            } else {
                echo '<p style="color: red;">Wystąpił błąd podczas aktualizacji liczby narzędzi.</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">Wystąpił wyjątek podczas aktualizacji liczby narzędzi: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p style="color: orange;">Funkcja update_category_counts() nie istnieje. Liczby narzędzi nie zostały zaktualizowane.</p>';
    }
}

// Finish and output
$output = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naprawa hierarchii kategorii</title>
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
        ul {
            margin-bottom: 20px;
        }
        li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php echo $output; ?>

    <hr>
    <p>Po zakończeniu naprawy hierarchii kategorii, możesz przejść do <a href="import-categories.php">importu kategorii</a>.</p>
    <p><a href="/">Powrót do strony głównej</a></p>
</body>
</html>
