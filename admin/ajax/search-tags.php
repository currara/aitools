<?php
// Uwzględnij konfigurację
require_once '../../includes/config.php';

// Sprawdź, czy użytkownik jest zalogowany i ma uprawnienia
if (!is_admin() && !is_editor()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Brak uprawnień']);
    exit;
}

// Pobierz parametr zapytania
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Wyszukaj tagi
$tags = [];
$sql = "SELECT id, name, slug FROM tags
        WHERE name LIKE '%" . $conn->real_escape_string($query) . "%'
        ORDER BY name ASC
        LIMIT 10";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
}

// Zwróć wyniki jako JSON
header('Content-Type: application/json');
echo json_encode($tags);
