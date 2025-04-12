<?php
/**
 * Fix Activity Log Table
 *
 * Ten skrypt naprawia problemy z tabelą activity_log, która może powodować błąd
 * "Unknown column 'login' in 'field list'" podczas logowania
 */

// Połączenie z bazą danych
require_once 'includes/config.php';
require_once 'includes/db.php';

// Wyświetl nagłówek
echo "<h1>Naprawa tabeli activity_log</h1>";

// Sprawdź, czy tabela activity_log istnieje
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'activity_log'");

if ($result && $result->num_rows > 0) {
    $table_exists = true;
    echo "<p>Tabela 'activity_log' istnieje.</p>";
} else {
    echo "<p>Tabela 'activity_log' nie istnieje. Tworzę tabelę...</p>";

    // Utwórz tabelę activity_log
    $sql = "CREATE TABLE activity_log (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) DEFAULT NULL,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) DEFAULT NULL,
        entity_id INT(11) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql)) {
        echo "<p>Tabela 'activity_log' została utworzona pomyślnie.</p>";
        $table_exists = true;
    } else {
        echo "<p>Błąd podczas tworzenia tabeli 'activity_log': " . $conn->error . "</p>";
    }
}

// Sprawdź, czy tabela activity istnieje (sprawdź, czy może być to źródło problemu)
$result = $conn->query("SHOW TABLES LIKE 'activity'");
if ($result && $result->num_rows > 0) {
    echo "<p>Tabela 'activity' istnieje. To może być źródło problemu, ponieważ funkcja log_activity używa tabeli 'activity_log'.</p>";

    // Sprawdź, czy tabela activity ma kolumnę 'login'
    $result = $conn->query("SHOW COLUMNS FROM activity LIKE 'login'");
    if ($result && $result->num_rows == 0) {
        echo "<p>Kolumna 'login' nie istnieje w tabeli 'activity'. To wyjaśnia błąd, ponieważ kod może próbować wstawiać dane do tej tabeli.</p>";

        echo "<h2>Możliwe rozwiązania:</h2>";
        echo "<ol>";
        echo "<li>Dodaj kolumnę 'login' do tabeli 'activity':</li>";
        echo "<pre>ALTER TABLE activity ADD COLUMN login VARCHAR(50) NOT NULL AFTER user_id;</pre>";

        echo "<li>Zmień funkcję log_activity, aby używała poprawnej nazwy tabeli:</li>";
        echo "<pre>
// W pliku includes/functions.php, około linii 891, zmień:
\$sql = \"INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent)...

// Na:
\$sql = \"INSERT INTO activity (user_id, login, entity_type, entity_id, details, ip_address, user_agent)...
</pre>";

        echo "</ol>";

        echo "<h2>Automatyczna naprawa:</h2>";
        echo "<p>Czy chcesz dodać kolumnę 'login' do tabeli 'activity'?</p>";

        if (isset($_GET['fix']) && $_GET['fix'] == 'add_login_column') {
            $sql = "ALTER TABLE activity ADD COLUMN login VARCHAR(50) DEFAULT NULL AFTER user_id";
            if ($conn->query($sql)) {
                echo "<p>Kolumna 'login' została dodana do tabeli 'activity'.</p>";
            } else {
                echo "<p>Błąd podczas dodawania kolumny 'login': " . $conn->error . "</p>";
            }
        } else {
            echo "<a href='?fix=add_login_column' class='button'>Dodaj kolumnę 'login' do tabeli 'activity'</a>";
        }
    } else {
        echo "<p>Kolumna 'login' już istnieje w tabeli 'activity'.</p>";
    }
}

// Wyświetl strukturę tabeli activity_log
if ($table_exists) {
    echo "<h2>Struktura tabeli 'activity_log':</h2>";
    $result = $conn->query("DESCRIBE activity_log");

    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Kolumna</th><th>Typ</th><th>Null</th><th>Klucz</th><th>Default</th><th>Extra</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    }
}

// Wyświetl strukturę tabeli activity, jeśli istnieje
$result = $conn->query("SHOW TABLES LIKE 'activity'");
if ($result && $result->num_rows > 0) {
    echo "<h2>Struktura tabeli 'activity':</h2>";
    $result = $conn->query("DESCRIBE activity");

    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Kolumna</th><th>Typ</th><th>Null</th><th>Klucz</th><th>Default</th><th>Extra</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    }
}

// Dodaj trochę stylów CSS
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2 { color: #333; }
p { margin: 10px 0; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
.button { display: inline-block; background: #4CAF50; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
</style>";
?>

<div style="margin-top: 30px;">
    <h2>Stwórz alternatywną funkcję logowania aktywności</h2>
    <p>Poniższy fragment kodu może być użyty do zastąpienia problematycznej funkcji log_activity w pliku functions.php:</p>
    <pre>
// Function to log activity
function log_activity($user_id, $action, $entity_type = null, $entity_id = null, $details = null) {
    global $conn;

    // Sprawdź, czy tabela activity_log istnieje
    $result = $conn->query("SHOW TABLES LIKE 'activity_log'");
    $table_name = ($result && $result->num_rows > 0) ? 'activity_log' : 'activity';

    if ($table_name == 'activity_log') {
        // Używaj oryginalnego kodu dla activity_log
        $user_id = $user_id ? (int)$user_id : 'NULL';
        $action = clean_input($action);
        $entity_type = $entity_type ? "'" . clean_input($entity_type) . "'" : 'NULL';
        $entity_id = $entity_id ? (int)$entity_id : 'NULL';
        $details = $details ? "'" . clean_input($details) . "'" : 'NULL';
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? "'" . clean_input($_SERVER['REMOTE_ADDR']) . "'" : 'NULL';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? "'" . clean_input($_SERVER['HTTP_USER_AGENT']) . "'" : 'NULL';

        $sql = "INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                VALUES ($user_id, '$action', $entity_type, $entity_id, $details, $ip_address, $user_agent)";
    } else {
        // Użyj kompatybilnego kodu dla tabeli activity
        $user_id = $user_id ? (int)$user_id : 'NULL';
        $sql = "INSERT INTO activity (user_id, login) VALUES ($user_id, '$action')";
    }

    $conn->query($sql);
}
    </pre>
</div>
