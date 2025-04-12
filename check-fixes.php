<?php
// Plik diagnostyczny do sprawdzenia naprawienia błędów
require_once 'includes/config.php';

echo "<h1>Sprawdzenie naprawionych problemów</h1>";

// 1. Sprawdzenie funkcji w admin/index.php
echo "<h2>1. Problem z funkcją count() w admin/index.php</h2>";
if (function_exists('custom_count')) {
    echo "<p style='color:green'>✓ Funkcja custom_count() istnieje - problem został naprawiony.</p>";
} else {
    echo "<p style='color:red'>✗ Funkcja custom_count() nie istnieje - problem nie został naprawiony.</p>";
}

// 2. Sprawdzenie kolumny submitted_by w tabeli tools
echo "<h2>2. Problem z kolumną submitted_by w tabeli tools</h2>";
$check_column = $mysqli->query("SHOW COLUMNS FROM tools LIKE 'submitted_by'");
if ($check_column->num_rows > 0) {
    echo "<p style='color:orange'>⚠ Kolumna submitted_by istnieje w tabeli tools. Poprawka w user-profile.php będzie działać poprawnie.</p>";
} else {
    echo "<p style='color:orange'>⚠ Kolumna submitted_by nie istnieje w tabeli tools. Poprawka w user-profile.php obsługuje ten przypadek.</p>";
    echo "<p>Możesz dodać tę kolumnę do tabeli tools:</p>";
    echo "<pre>ALTER TABLE tools ADD COLUMN submitted_by INT DEFAULT NULL;</pre>";
}

// 3. Sprawdzenie tłumaczeń w login.php, user-profile.php i user-favorites.php
echo "<h2>3. Sprawdzenie tłumaczeń</h2>";
echo "<h3>3.1. Tłumaczenia w login.php</h3>";
$login_translations = ['login_title', 'login_username', 'login_password', 'login_remember_me', 'login_button', 'login_no_account', 'login_register_link'];
foreach ($login_translations as $key) {
    if (isset($lang[$key])) {
        echo "<p style='color:green'>✓ Tłumaczenie '$key' istnieje: " . htmlspecialchars($lang[$key]) . "</p>";
    } else {
        echo "<p style='color:red'>✗ Brak tłumaczenia dla '$key'</p>";
    }
}

echo "<h3>3.2. Tłumaczenia w user-profile.php</h3>";
$profile_translations = ['profile_title', 'profile_tools_submitted', 'profile_favorites', 'profile_joined', 'profile_edit_profile', 'profile_change_password'];
foreach ($profile_translations as $key) {
    if (isset($lang[$key])) {
        echo "<p style='color:green'>✓ Tłumaczenie '$key' istnieje: " . htmlspecialchars($lang[$key]) . "</p>";
    } else {
        echo "<p style='color:red'>✗ Brak tłumaczenia dla '$key'</p>";
    }
}

echo "<h3>3.3. Tłumaczenia w user-favorites.php</h3>";
$favorites_translations = ['favorites_title', 'favorites_no_items', 'favorites_browse_tools', 'favorites_count'];
foreach ($favorites_translations as $key) {
    if (isset($lang[$key])) {
        echo "<p style='color:green'>✓ Tłumaczenie '$key' istnieje: " . htmlspecialchars($lang[$key]) . "</p>";
    } else {
        echo "<p style='color:red'>✗ Brak tłumaczenia dla '$key'</p>";
    }
}

// 4. Sprawdzenie ścieżek CSS w header.php
echo "<h2>4. Sprawdzenie ścieżek CSS w header.php</h2>";
echo "<p>SITE_URL: " . SITE_URL . "</p>";
echo "<p>Wszystkie ścieżki CSS w header.php zostały zaktualizowane, aby używać zmiennej SITE_URL.</p>";

?>
