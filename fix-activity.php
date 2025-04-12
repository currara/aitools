<?php
/**
 * Naprawa tabeli aktywności
 *
 * Ten skrypt naprawia problemy z tabelą aktywności, która powoduje błąd
 * podczas logowania użytkowników.
 */

// Połączenie z bazą danych - używamy tylko istniejącego pliku config.php
require_once 'includes/config.php';

// W tym projekcie zmienna połączenia to $mysqli
$conn = $mysqli; // Dla zachowania spójności kodu

echo "<h1>Naprawa tabeli aktywności</h1>";

// Sprawdź czy tabela activity istnieje
$result = $mysqli->query("SHOW TABLES LIKE 'activity'");
if ($result && $result->num_rows > 0) {
    echo "<p>Tabela 'activity' istnieje.</p>";

    // Sprawdź czy kolumna login istnieje
    $result = $mysqli->query("SHOW COLUMNS FROM activity LIKE 'login'");
    if ($result && $result->num_rows > 0) {
        echo "<p>Kolumna 'login' już istnieje w tabeli 'activity'.</p>";
    } else {
        echo "<p>Kolumna 'login' nie istnieje w tabeli 'activity'. To jest przyczyną błędu.</p>";

        // Dodaj kolumnę login, jeśli tak wybrano
        if (isset($_GET['fix']) && $_GET['fix'] == 1) {
            $sql = "ALTER TABLE activity ADD COLUMN login VARCHAR(50) DEFAULT NULL AFTER user_id";
            if ($mysqli->query($sql)) {
                echo "<p style='color: green;'>Kolumna 'login' została pomyślnie dodana do tabeli 'activity'.</p>";
                echo "<p>Problem powinien być rozwiązany. Możesz teraz <a href='login.php'>zalogować się</a>.</p>";
            } else {
                echo "<p style='color: red;'>Błąd podczas dodawania kolumny 'login': " . $mysqli->error . "</p>";
            }
        } else {
            echo "<p>Czy chcesz dodać kolumnę 'login' do tabeli 'activity'?</p>";
            echo "<a href='?fix=1' style='display: inline-block; background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Napraw problem</a>";
        }
    }
} else {
    // Tabela activity nie istnieje, sprawdź czy istnieje activity_log
    $result = $mysqli->query("SHOW TABLES LIKE 'activity_log'");
    if ($result && $result->num_rows > 0) {
        echo "<p>Tabela 'activity_log' istnieje, ale kod próbuje używać tabeli 'activity'.</p>";

        if (isset($_GET['create']) && $_GET['create'] == 1) {
            // Utwórz tabelę activity
            $sql = "CREATE TABLE activity (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                login VARCHAR(50),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            if ($mysqli->query($sql)) {
                echo "<p style='color: green;'>Tabela 'activity' została pomyślnie utworzona.</p>";
                echo "<p>Problem powinien być rozwiązany. Możesz teraz <a href='login.php'>zalogować się</a>.</p>";
            } else {
                echo "<p style='color: red;'>Błąd podczas tworzenia tabeli 'activity': " . $mysqli->error . "</p>";
            }
        } else {
            echo "<p>Czy chcesz utworzyć tabelę 'activity'?</p>";
            echo "<a href='?create=1' style='display: inline-block; background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Utwórz tabelę</a>";
        }
    } else {
        echo "<p>Żadna z tabel 'activity' ani 'activity_log' nie istnieje. Musimy utworzyć jedną z nich.</p>";

        if (isset($_GET['create_log']) && $_GET['create_log'] == 1) {
            // Utwórz tabelę activity_log
            $sql = "CREATE TABLE activity_log (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(50) NOT NULL,
                entity_type VARCHAR(50) NULL,
                entity_id INT NULL,
                details TEXT NULL,
                ip_address VARCHAR(50) NULL,
                user_agent TEXT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            if ($mysqli->query($sql)) {
                echo "<p style='color: green;'>Tabela 'activity_log' została pomyślnie utworzona.</p>";
                echo "<p>Problem powinien być rozwiązany. Możesz teraz <a href='login.php'>zalogować się</a>.</p>";
            } else {
                echo "<p style='color: red;'>Błąd podczas tworzenia tabeli 'activity_log': " . $mysqli->error . "</p>";
            }
        } else {
            echo "<a href='?create_log=1' style='display: inline-block; background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Utwórz tabelę activity_log</a>";
        }
    }
}

// Style CSS
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
p { margin: 10px 0; }
a { color: #0066cc; text-decoration: none; }
</style>";
?>
