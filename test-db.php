<?php
// Include configuration
require_once 'includes/config.php';

// Check if upvotes table exists
$result = $mysqli->query("SHOW TABLES LIKE 'upvotes'");
echo "Tabela upvotes: " . ($result->num_rows > 0 ? "Istnieje" : "Nie istnieje") . "<br>";

// Check if ratings table exists
$result = $mysqli->query("SHOW TABLES LIKE 'ratings'");
echo "Tabela ratings: " . ($result->num_rows > 0 ? "Istnieje" : "Nie istnieje") . "<br>";

// Show structure of upvotes table if exists
if ($result->num_rows > 0) {
    $result = $mysqli->query("DESCRIBE upvotes");
    echo "<br>Struktura tabeli upvotes:<br>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Show structure of ratings table if exists
$result = $mysqli->query("SHOW TABLES LIKE 'ratings'");
if ($result->num_rows > 0) {
    $result = $mysqli->query("DESCRIBE ratings");
    echo "<br>Struktura tabeli ratings:<br>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Check content of upvotes table
$result = $mysqli->query("SELECT COUNT(*) as count FROM upvotes");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<br>Liczba głosów w tabeli upvotes: " . $row['count'] . "<br>";
}

// Check content of ratings table
$result = $mysqli->query("SELECT COUNT(*) as count FROM ratings");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<br>Liczba ocen w tabeli ratings: " . $row['count'] . "<br>";
}

// Show test query for upvote
echo "<br>Testowe zapytanie dla upvote:<br>";
$test_ip = "127.0.0.1";
$test_tool_id = 1;
$sql = "SELECT id FROM upvotes WHERE tool_id = $test_tool_id AND ip_address = '$test_ip' AND user_id IS NULL";
echo "SQL: " . $sql . "<br>";
$result = $mysqli->query($sql);
echo "Wynik: " . ($result ? ($result->num_rows > 0 ? "Znaleziono głos" : "Nie znaleziono głosu") : "Błąd zapytania") . "<br>";

// Show test query for rating
echo "<br>Testowe zapytanie dla rating:<br>";
$sql = "SELECT id, rating FROM ratings WHERE tool_id = $test_tool_id AND ip_address = '$test_ip' AND user_id IS NULL";
echo "SQL: " . $sql . "<br>";
$result = $mysqli->query($sql);
echo "Wynik: " . ($result ? ($result->num_rows > 0 ? "Znaleziono ocenę: " . $result->fetch_assoc()['rating'] : "Nie znaleziono oceny") : "Błąd zapytania") . "<br>";

// Sprawdzenie struktury tabeli tools
echo "<h1>Struktura tabeli tools</h1>";
$result = $mysqli->query("DESCRIBE tools");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
