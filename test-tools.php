<?php
require_once 'includes/config.php';

echo "<h1>Lista dostępnych narzędzi</h1>";

$tools = get_tools(10);

echo "<ul>";
foreach ($tools as $tool) {
    echo "<li>";
    echo "ID: " . $tool['id'] . " | ";
    echo "Nazwa: " . $tool['name'] . " | ";
    echo "Slug narzędzia: " . $tool['slug'] . " | ";
    echo "Kategoria ID: " . $tool['category_id'] . " | ";
    echo "Nazwa kategorii: " . $tool['category_name'] . " | ";
    echo "Slug kategorii: " . ($tool['category_slug'] ?? 'NIE USTAWIONY');
    echo " | <a href='/tool.php?slug=" . $tool['slug'] . "'>Zobacz narzędzie</a>";
    echo "</li>";
}
echo "</ul>";

// Informacje o bazach danych
echo "<h2>Struktura tabeli kategorii</h2>";
$result = $conn->query("DESCRIBE categories");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td></tr>";
    }
    echo "</table>";
}

echo "<h2>Przykładowe dane kategorii</h2>";
$result = $conn->query("SELECT * FROM categories LIMIT 5");
if ($result) {
    echo "<table border='1'>";
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . $value . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
