<?php
/**
 * Funkcje pomocnicze do obsługi wielu kategorii dla narzędzi
 */

/**
 * Pobiera wszystkie kategorie przypisane do narzędzia
 *
 * @param int $tool_id ID narzędzia
 * @return array Tablica z kategoriami przypisanymi do narzędzia
 */
function get_tool_categories($tool_id) {
    global $conn;
    $categories = [];

    // Sprawdź czy tabela tool_categories istnieje
    $result = $conn->query("SHOW TABLES LIKE 'tool_categories'");
    if (!$result || $result->num_rows === 0) {
        return $categories;
    }

    // Pobierz kategorie przypisane do narzędzia
    $sql = "SELECT tc.category_id, c.name
            FROM tool_categories tc
            JOIN categories c ON tc.category_id = c.id
            WHERE tc.tool_id = " . (int)$tool_id;

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => $row['category_id'],
                'name' => $row['name']
            ];
        }
    }

    return $categories;
}

/**
 * Aktualizuje kategorie przypisane do narzędzia
 *
 * @param int $tool_id ID narzędzia
 * @param array $categories Tablica z ID kategorii
 * @return bool True jeśli operacja się powiodła
 */
function update_tool_categories($tool_id, $categories) {
    global $conn;

    // Sprawdź czy tabela tool_categories istnieje
    if (!ensure_tool_categories_table_exists()) {
        return false;
    }

    // Sprawdź czy tool_id istnieje w bazie danych
    $check_tool = $conn->query("SELECT id FROM tools WHERE id = " . (int)$tool_id);
    if (!$check_tool || $check_tool->num_rows === 0) {
        return false;
    }

    // Usuń wszystkie dotychczasowe powiązania
    $sql = "DELETE FROM tool_categories WHERE tool_id = " . (int)$tool_id;
    if (!$conn->query($sql)) {
        return false;
    }

    // Jeśli nie ma kategorii do dodania, kończymy
    if (empty($categories)) {
        return true;
    }

    // Przygotuj zapytanie do dodania nowych powiązań
    $values = [];
    foreach ($categories as $category) {
        $category_id = is_array($category) ? (isset($category['id']) ? (int)$category['id'] : 0) : (int)$category;

        // Sprawdź czy kategoria istnieje
        if ($category_id > 0) {
            $check_category = $conn->query("SELECT id FROM categories WHERE id = " . $category_id);
            if ($check_category && $check_category->num_rows > 0) {
                $values[] = "(" . (int)$tool_id . ", " . $category_id . ")";
            }
        }
    }

    if (empty($values)) {
        return true;
    }

    $sql = "INSERT INTO tool_categories (tool_id, category_id) VALUES " . implode(', ', $values);

    return $conn->query($sql) !== false;
}

/**
 * Tworzy tabelę tool_categories jeśli nie istnieje
 *
 * @return bool True jeśli tabela istnieje lub została utworzona
 */
function ensure_tool_categories_table_exists() {
    global $conn;

    // Sprawdź czy tabela istnieje
    $result = $conn->query("SHOW TABLES LIKE 'tool_categories'");
    if ($result && $result->num_rows > 0) {
        return true;
    }

    // Utwórz tabelę
    $sql = "CREATE TABLE IF NOT EXISTS tool_categories (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        tool_id INT NOT NULL,
        category_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (tool_id),
        INDEX (category_id),
        UNIQUE KEY tool_category (tool_id, category_id)
    )";

    $created = $conn->query($sql);

    if (!$created) {
        error_log("Nie udało się utworzyć tabeli tool_categories: " . $conn->error);
        return false;
    }

    // Dodaj klucze obce, jeśli operacja się powiedzie
    try {
        $sql_fk1 = "ALTER TABLE tool_categories ADD CONSTRAINT fk_tool_id FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE";
        $conn->query($sql_fk1);

        $sql_fk2 = "ALTER TABLE tool_categories ADD CONSTRAINT fk_category_id FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE";
        $conn->query($sql_fk2);
    } catch (Exception $e) {
        error_log("Ostrzeżenie: Nie udało się dodać kluczy obcych do tabeli tool_categories: " . $e->getMessage());
        // Nie zwracamy false, bo tabela została utworzona, tylko klucze obce nie zadziałały
    }

    return true;
}

/**
 * Migruje dane z pojedynczej kategorii do wielu kategorii
 *
 * @return bool True jeśli migracja się powiodła
 */
function migrate_single_to_multi_categories() {
    global $conn;

    // Upewnij się, że tabela istnieje
    if (!ensure_tool_categories_table_exists()) {
        return false;
    }

    // Sprawdź czy już migrowano (czy są jakieś dane w tabeli)
    $result = $conn->query("SELECT COUNT(*) as count FROM tool_categories");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            // Już są dane w tabeli, więc uznajemy, że migracja już się odbyła
            return true;
        }
    }

    // Migruj dane z pojedynczej kategorii do wielu
    try {
        $sql = "INSERT INTO tool_categories (tool_id, category_id)
                SELECT id, category_id FROM tools WHERE category_id IS NOT NULL";

        $migrated = $conn->query($sql);

        if (!$migrated) {
            error_log("Błąd podczas migracji danych kategorii: " . $conn->error);
            return false;
        }

        // Policz, ile rekordów zmigrowano
        $count_result = $conn->query("SELECT COUNT(*) as count FROM tool_categories");
        if ($count_result) {
            $count_row = $count_result->fetch_assoc();
            error_log("Zmigrowano " . $count_row['count'] . " powiązań kategorii.");
        }

        return true;
    } catch (Exception $e) {
        error_log("Wyjątek podczas migracji kategorii: " . $e->getMessage());
        return false;
    }
}
