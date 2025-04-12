<?php
// Include database configuration
require_once 'db_config.php';

// Function to clean and sanitize input data
function clean_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to create slug from text
function create_slug($text)
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Function to get all categories
function get_categories($parent_id = null)
{
    global $conn, $current_language;
    $categories = array();

    // Sprawdź czy kolumna parent_id istnieje
    $column_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    $parent_id_exists = $column_check && $column_check->num_rows > 0;

    $sql = "SELECT DISTINCT c.id, c.*, COALESCE(ct.name, c.name) as name, COALESCE(ct.description, c.description) as description
            FROM categories c
            LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_code = '$current_language'";

    // Add parent_id filter if specified and column exists
    if ($parent_id_exists) {
        if ($parent_id === null) {
            $sql .= " WHERE c.parent_id IS NULL"; // Only get main categories
        } else if ($parent_id === 'all') {
            // Get all categories, no filter
        } else {
            $sql .= " WHERE c.parent_id = " . (int)$parent_id;
        }
    }

    $sql .= " GROUP BY c.id ORDER BY name ASC"; // Dodane GROUP BY c.id, aby zapobiec duplikatom

    $result = $conn->query($sql);
    if (!$result) {
        error_log("Error in get_categories: " . $conn->error . " - SQL: " . $sql);
        return $categories;
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Get updated tool count for this category
            $row['count'] = count_tools_in_category($row['id']);

            // Get subcategories if this is a main category and parent_id exists
            if (($parent_id === null || $parent_id === 'all') && $parent_id_exists) {
                $row['subcategories'] = get_subcategories($row['id']);
            } else {
                $row['subcategories'] = []; // Ensure subcategories is always defined
            }

            $categories[] = $row;
        }
    }

    return $categories;
}

// Function to get subcategories of a category
function get_subcategories($parent_id)
{
    // Sprawdź czy kolumna parent_id istnieje
    global $conn;
    $column_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    $parent_id_exists = $column_check && $column_check->num_rows > 0;

    if (!$parent_id_exists) {
        return []; // Jeśli kolumna parent_id nie istnieje, zwróć pustą tablicę
    }

    // Użyj DISTINCT i GROUP BY, aby uniknąć duplikatów
    $sql = "SELECT DISTINCT c.id, c.*, COALESCE(ct.name, c.name) as name, COALESCE(ct.description, c.description) as description
            FROM categories c
            LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_code = '" . $GLOBALS['current_language'] . "'
            WHERE c.parent_id = " . (int)$parent_id . "
            GROUP BY c.id
            ORDER BY name ASC";

    $result = $conn->query($sql);
    $subcategories = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['count'] = count_tools_in_category($row['id']);
            $row['subcategories'] = []; // Initialize empty subcategories to avoid further recursion
            $subcategories[] = $row;
        }
    }

    return $subcategories;
}

// Function to check if a category has subcategories
function has_subcategories($category_id)
{
    global $conn;

    // Sprawdź czy kolumna parent_id istnieje
    $column_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    $parent_id_exists = $column_check && $column_check->num_rows > 0;

    if (!$parent_id_exists) {
        return false; // Jeśli kolumna parent_id nie istnieje, zwróć false
    }

    $sql = "SELECT COUNT(*) as count FROM categories WHERE parent_id = " . (int)$category_id;
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    return false;
}

// Function to count total tools in a category and its subcategories
function count_tools_in_category($category_id, $visited_categories = [])
{
    global $conn;

    // Zabezpieczenie przed nieskończoną rekurencją - sprawdzamy czy kategoria była już odwiedzona
    if (in_array($category_id, $visited_categories)) {
        error_log("Wykryto zapętlenie w hierarchii kategorii! Kategoria ID: " . $category_id);
        return 0; // Przerwij rekurencję jeśli ta kategoria była już odwiedzona
    }

    // Dodaj bieżącą kategorię do odwiedzonych
    $visited_categories[] = $category_id;

    // First get direct tools count
    $count = 0;
    $sql = "SELECT COUNT(*) as count FROM tools WHERE category_id = " . (int)$category_id;
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['count'];
    }

    // Sprawdź czy kolumna parent_id istnieje
    $column_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    $parent_id_exists = $column_check && $column_check->num_rows > 0;

    // Now add tools from subcategories if parent_id exists
    if ($parent_id_exists) {
        $sql = "SELECT id FROM categories WHERE parent_id = " . (int)$category_id;
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Przechodzmy dalej tylko jeśli to nie jest ta sama kategoria (zapętlenie)
                if ($row['id'] != $category_id) {
                    // Przekazujemy tablicę odwiedzonych kategorii
                    $count += count_tools_in_category($row['id'], $visited_categories);
                } else {
                    error_log("Wykryto bezpośrednie zapętlenie! Kategoria " . $category_id . " jest podkategorią samej siebie.");
                }
            }
        }
    }

    return $count;
}

// Function to update tool counts for all categories
function update_category_counts()
{
    global $conn;

    // Sprawdź czy kolumna parent_id istnieje
    $column_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    $parent_id_exists = $column_check && $column_check->num_rows > 0;

    // Dodaj kolumnę count, jeśli nie istnieje
    $count_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'count'");
    if (!$count_check || $count_check->num_rows == 0) {
        $conn->query("ALTER TABLE categories ADD COLUMN count INT DEFAULT 0");
        error_log("Dodano kolumnę 'count' do tabeli categories");
    }

    // Get all categories
    $sql = "SELECT id FROM categories";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            try {
                // Używamy zmodyfikowanej funkcji count_tools_in_category z zabezpieczeniem przed nieskończoną rekurencją
                $count = count_tools_in_category($row['id'], []);

                // Update the count in the database
                $update_sql = "UPDATE categories SET count = " . (int)$count . " WHERE id = " . (int)$row['id'];
                if (!$conn->query($update_sql)) {
                    error_log("Błąd podczas aktualizacji licznika kategorii " . $row['id'] . ": " . $conn->error);
                }
            } catch (Exception $e) {
                error_log("Wyjątek podczas liczenia narzędzi dla kategorii " . $row['id'] . ": " . $e->getMessage());
            }
        }
    }

    return true;
}

// Function to get category by ID or slug
function get_category($id_or_slug)
{
    global $conn, $current_language;

    // Sprawdź czy kolumna parent_id istnieje
    $column_check = $conn->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    $parent_id_exists = $column_check && $column_check->num_rows > 0;

    if (is_numeric($id_or_slug)) {
        $sql = "SELECT c.*, COALESCE(ct.name, c.name) as name, COALESCE(ct.description, c.description) as description
                FROM categories c
                LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_code = '$current_language'
                WHERE c.id = " . (int)$id_or_slug;
    } else {
        $sql = "SELECT c.*, COALESCE(ct.name, c.name) as name, COALESCE(ct.description, c.description) as description
                FROM categories c
                LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_code = '$current_language'
                WHERE c.slug = '" . clean_input($id_or_slug) . "'";
    }

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();

        // Get parent category if this is a subcategory and parent_id exists
        if ($parent_id_exists && !empty($category['parent_id'])) {
            $category['parent'] = get_category($category['parent_id']);
        }

        // Get subcategories if parent_id exists
        if ($parent_id_exists) {
            $category['subcategories'] = get_subcategories($category['id']);
        } else {
            $category['subcategories'] = [];
        }

        // Get updated tool count
        $category['count'] = count_tools_in_category($category['id']);

        return $category;
    }

    return null;
}

// Function to get all tools
function get_tools($limit = 10, $offset = 0, $category_id = null, $featured = null, $new_launch = null, $sort = 'newest', $pricing_type = null)
{
    global $conn, $current_language;
    $tools = array();

    // Debug info
    error_log("Debug - get_tools called with params: limit=$limit, offset=$offset, category_id=" .
        ($category_id ? $category_id : "null") . ", featured=" . ($featured === true ? "true" : ($featured === false ? "false" : "null")) .
        ", new_launch=" . ($new_launch === true ? "true" : ($new_launch === false ? "false" : "null")) . ", sort=$sort, pricing_type=" .
        ($pricing_type ? $pricing_type : "null"));

    // CRITICAL DEBUG - Dump all tools directly from database for troubleshooting
    if ($limit > 100) {
        $debug_sql = "SELECT COUNT(*) as count FROM tools";
        $debug_result = $conn->query($debug_sql);
        if ($debug_result && $debug_result->num_rows > 0) {
            $debug_row = $debug_result->fetch_assoc();
            error_log("Debug - Total tools in database: " . $debug_row['count']);
        }
    }

    $sql = "SELECT t.*, c.name as category_name, c.slug as category_slug, COALESCE(tt.name, t.name) as name, COALESCE(tt.description, t.description) as description
            FROM tools t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN tool_translations tt ON t.id = tt.tool_id AND tt.language_code = '$current_language'
            WHERE 1=1";

    // Filter by category
    if ($category_id !== null) {
        if (is_numeric($category_id)) {
            $sql .= " AND t.category_id = " . (int)$category_id;
        } else {
            // If category is provided as slug
            $sql .= " AND c.slug = '" . clean_input($category_id) . "'";
        }
    }

    // Filter by featured status - ONLY if specifically true or false, not null
    if ($featured === true) {
        $sql .= " AND t.featured = TRUE";
    } else if ($featured === false) {
        $sql .= " AND t.featured = FALSE";
    }

    // Filter by new launch status - ONLY if specifically true or false, not null
    if ($new_launch === true) {
        $sql .= " AND t.new_launch = TRUE";
    } else if ($new_launch === false) {
        $sql .= " AND t.new_launch = FALSE";
    }

    // Filter by pricing type
    if ($pricing_type !== null) {
        if (is_array($pricing_type)) {
            // Multiple pricing types
            $pricing_types = array_map(function ($type) use ($conn) {
                return "'" . $conn->real_escape_string($type) . "'";
            }, $pricing_type);
            $sql .= " AND t.pricing_type IN (" . implode(',', $pricing_types) . ")";
        } else {
            // Single pricing type
            $sql .= " AND t.pricing_type = '" . clean_input($pricing_type) . "'";
        }
    }

    // Sorting
    switch ($sort) {
        case 'oldest':
            $sql .= " ORDER BY t.created_at ASC";
            break;
        case 'rating':
            $sql .= " ORDER BY t.rating DESC, t.created_at DESC";
            break;
        case 'popularity':
            $sql .= " ORDER BY t.views DESC, t.created_at DESC";
            break;
        case 'price_asc':
            // Sort by pricing_type: free → freemium → paid → contact
            $sql .= " ORDER BY FIELD(t.pricing_type, 'free', 'freemium', 'paid', 'contact'), t.created_at DESC";
            break;
        case 'price_desc':
            // Sort by pricing_type: contact → paid → freemium → free
            $sql .= " ORDER BY FIELD(t.pricing_type, 'contact', 'paid', 'freemium', 'free'), t.created_at DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY t.created_at DESC";
            break;
    }

    // Apply limit only if provided and valid
    if ($limit > 0) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }

    // Execute query and log it for debugging
    error_log("Debug - SQL query: " . $sql);
    $result = $conn->query($sql);
    if (!$result) {
        error_log("SQL Error in get_tools: " . $conn->error . " - Query: " . $sql);
        return $tools;
    }

    error_log("Debug - Found " . $result->num_rows . " tools in this query");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Debug first few rows
            if (count($tools) < 3) {
                error_log("Debug - Tool: ID=" . $row['id'] . ", Name=" . $row['name'] . ", Category=" . $row['category_name'] . ", Pricing=" . $row['pricing_type']);
            }

            // Get tags for each tool
            $row['tags'] = get_tool_tags($row['id']);

            // Calculate is_new flag based on creation date (if new_launch isn't explicitly set)
            if (!isset($row['is_new']) && !empty($row['created_at'])) {
                $created_date = new DateTime($row['created_at']);
                $now = new DateTime();
                $interval = $created_date->diff($now);
                $row['is_new'] = ($interval->days <= 30); // Consider tools created within 30 days as new
            }

            // Ensure category_slug is not null for all tools
            if (empty($row['category_slug']) && !empty($row['category_id'])) {
                // Try to get the category separately
                $category = get_category($row['category_id']);
                if ($category && !empty($category['slug'])) {
                    $row['category_slug'] = $category['slug'];
                }
            }

            $tools[] = $row;
        }
    }

    return $tools;
}

// Function to get tool by ID or slug
function get_tool($id_or_slug)
{
    global $conn, $current_language;

    if (is_numeric($id_or_slug)) {
        $sql = "SELECT t.*, c.name as category_name, c.slug as category_slug, COALESCE(tt.name, t.name) as name, COALESCE(tt.description, t.description) as description
                FROM tools t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN tool_translations tt ON t.id = tt.tool_id AND tt.language_code = '$current_language'
                WHERE t.id = " . (int)$id_or_slug;
    } else {
        $sql = "SELECT t.*, c.name as category_name, c.slug as category_slug, COALESCE(tt.name, t.name) as name, COALESCE(tt.description, t.description) as description
                FROM tools t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN tool_translations tt ON t.id = tt.tool_id AND tt.language_code = '$current_language'
                WHERE t.slug = '" . clean_input($id_or_slug) . "'";
    }

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $tool = $result->fetch_assoc();
        // Get tags for the tool
        $tool['tags'] = get_tool_tags($tool['id']);

        // Ensure category_slug is not null
        if (empty($tool['category_slug']) && !empty($tool['category_id'])) {
            // Try to get the category separately
            $category = get_category($tool['category_id']);
            if ($category && !empty($category['slug'])) {
                $tool['category_slug'] = $category['slug'];
            }
        }

        return $tool;
    }

    return null;
}

// Function to count total tools
function count_tools($category_id = null, $pricing_type = null)
{
    global $conn;

    $sql = "SELECT COUNT(*) as total FROM tools t";

    // Start WHERE clause
    $where_conditions = [];

    // Filter by category if specified
    if ($category_id !== null) {
        if (is_numeric($category_id)) {
            $sql .= " WHERE t.category_id = " . (int)$category_id;
            $where_conditions[] = "t.category_id = " . (int)$category_id;
        } else {
            // If category is provided as slug, join with categories table
            $sql .= " LEFT JOIN categories c ON t.category_id = c.id";
            $where_conditions[] = "c.slug = '" . clean_input($category_id) . "'";
        }
    }

    // Filter by pricing type if specified
    if ($pricing_type !== null) {
        if (is_array($pricing_type)) {
            // Multiple pricing types
            $pricing_types = array_map(function ($type) use ($conn) {
                return "'" . $conn->real_escape_string($type) . "'";
            }, $pricing_type);
            $where_conditions[] = "t.pricing_type IN (" . implode(',', $pricing_types) . ")";
        } else {
            // Single pricing type
            $where_conditions[] = "t.pricing_type = '" . clean_input($pricing_type) . "'";
        }
    }

    // Add WHERE clause if we have conditions
    if (!empty($where_conditions)) {
        if (strpos($sql, "WHERE") === false) {
            $sql .= " WHERE ";
        } else {
            $sql .= " AND ";
        }
        $sql .= implode(" AND ", $where_conditions);
    }

    // Debug the query
    error_log("Debug - count_tools SQL: " . $sql);

    $result = $conn->query($sql);
    if (!$result) {
        error_log("SQL Error in count_tools: " . $conn->error . " - Query: " . $sql);
        return 0;
    }

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    return 0;
}

// Function to search tools
function search_tools($query, $limit = 10, $offset = 0)
{
    global $conn, $current_language;
    $tools = array();

    $query = clean_input($query);

    $sql = "SELECT t.*, c.name as category_name, c.slug as category_slug, COALESCE(tt.name, t.name) as display_name, COALESCE(tt.description, t.description) as display_description
            FROM tools t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN tool_translations tt ON t.id = tt.tool_id AND tt.language_code = '$current_language'
            WHERE t.name LIKE '%$query%' OR t.description LIKE '%$query%'
            OR (tt.name LIKE '%$query%' OR tt.description LIKE '%$query%')
            ORDER BY t.name ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Get tags for each tool
            if (function_exists('get_tool_tags')) {
                $row['tags'] = get_tool_tags($row['id']);
            }

            // Ensure category_slug is not null
            if (empty($row['category_slug']) && !empty($row['category_id'])) {
                // Try to get the category separately
                $category = get_category($row['category_id']);
                if ($category && !empty($category['slug'])) {
                    $row['category_slug'] = $category['slug'];
                }
            }

            $tools[] = $row;
        }
    }

    return $tools;
}

// Function to check if user is logged in
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is editor or admin
function is_editor()
{
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'editor' || $_SESSION['role'] === 'admin');
}

// Function to create tag if it doesn't exist, and return its ID
function create_tag_if_not_exists($tag_name)
{
    global $conn;

    $tag_name = trim($tag_name);
    if (empty($tag_name)) {
        return false;
    }

    // First check if tag exists
    $slug = create_slug($tag_name);
    $sql = "SELECT id FROM tags WHERE slug = '" . $conn->real_escape_string($slug) . "'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }

    // Create new tag
    $sql = "INSERT INTO tags (name, slug) VALUES ('" .
        $conn->real_escape_string($tag_name) . "', '" .
        $conn->real_escape_string($slug) . "')";

    if ($conn->query($sql)) {
        return $conn->insert_id;
    }

    return false;
}

// Function to save a translated category with localized slug
function save_category_translation($category_id, $lang_code, $data)
{
    global $conn;

    // Clean and prepare data
    $name = clean_input($data['name']);
    $description = isset($data['description']) ? clean_input($data['description']) : '';

    // Create slug based on translated name
    if (isset($data['slug']) && !empty($data['slug'])) {
        $slug = clean_input($data['slug']);
    } else {
        $slug = create_localized_slug($name, $lang_code);
    }

    // Check if translation exists
    $check_sql = "SELECT id FROM category_translations
                  WHERE category_id = " . (int)$category_id . "
                  AND language_code = '" . clean_input($lang_code) . "'";

    $result = $conn->query($check_sql);

    // Ogranicz długość pola icon do 100 znaków
    $icon = isset($data['icon']) ? clean_input($data['icon']) : '';
    $icon = substr($icon, 0, 100);

    if ($result && $result->num_rows > 0) {
        // Update existing translation
        $sql = "UPDATE category_translations SET
                name = '" . $conn->real_escape_string($name) . "',
                slug = '" . $conn->real_escape_string($slug) . "',
                description = '" . $conn->real_escape_string($description) . "'
                WHERE category_id = " . (int)$category_id . "
                AND language_code = '" . clean_input($lang_code) . "'";
    } else {
        // Create new translation
        $sql = "INSERT INTO category_translations (category_id, language_code, name, slug, description)
                VALUES (" . (int)$category_id . ",
                '" . clean_input($lang_code) . "',
                '" . $conn->real_escape_string($name) . "',
                '" . $conn->real_escape_string($slug) . "',
                '" . $conn->real_escape_string($description) . "')";
    }

    if ($conn->query($sql) === false) {
        return ['success' => false, 'message' => 'Error saving category translation: ' . $conn->error];
    }

    return ['success' => true, 'message' => 'Category translation saved successfully'];
}

// Function to save a translated tool with localized slug
function save_tool_translation($tool_id, $lang_code, $data)
{
    global $conn;

    // Clean and prepare data
    $name = clean_input($data['name']);
    $description = isset($data['description']) ? clean_input($data['description']) : '';

    // Create slug based on translated name
    if (isset($data['slug']) && !empty($data['slug'])) {
        $slug = clean_input($data['slug']);
    } else {
        $slug = create_localized_slug($name, $lang_code);
    }

    // Check if translation exists
    $check_sql = "SELECT id FROM tool_translations
                  WHERE tool_id = " . (int)$tool_id . "
                  AND language_code = '" . clean_input($lang_code) . "'";

    $result = $conn->query($check_sql);

    if ($result && $result->num_rows > 0) {
        // Update existing translation
        $sql = "UPDATE tool_translations SET
                name = '" . $conn->real_escape_string($name) . "',
                slug = '" . $conn->real_escape_string($slug) . "',
                description = '" . $conn->real_escape_string($description) . "'
                WHERE tool_id = " . (int)$tool_id . "
                AND language_code = '" . clean_input($lang_code) . "'";
    } else {
        // Create translation
        $sql = "INSERT INTO tool_translations (tool_id, language_code, name, slug, description)
                VALUES (" . (int)$tool_id . ",
                '" . clean_input($lang_code) . "',
                '" . $conn->real_escape_string($name) . "',
                '" . $conn->real_escape_string($slug) . "',
                '" . $conn->real_escape_string($description) . "')";
    }

    if ($conn->query($sql) === false) {
        return ['success' => false, 'message' => 'Error saving tool translation: ' . $conn->error];
    }

    return ['success' => true, 'message' => 'Tool translation saved successfully'];
}

// Admin functions
// ==============

// Function to create or update category
function save_category($data, $translations = [])
{
    global $conn;

    $name = clean_input($data['name']);

    // Generate slug if empty or validate existing slug
    if (isset($data['slug']) && !empty(trim($data['slug']))) {
        $slug = clean_input($data['slug']);
    } else {
        // Generate slug from name
        $slug = create_slug($name);
    }

    // Final validation - ensure slug is never empty
    if (empty($slug)) {
        return ['success' => false, 'message' => 'Slug kategorii nie może być pusty'];
    }

    $description = isset($data['description']) ? clean_input($data['description']) : '';
    $icon = isset($data['icon']) ? clean_input($data['icon']) : '';

    // Check if it's an update or a new category
    if (isset($data['id']) && !empty($data['id'])) {
        $id = (int)$data['id'];

        // Update category
        $sql = "UPDATE categories SET
                name = '$name',
                slug = '$slug',
                description = '$description',
                icon = '$icon'
                WHERE id = $id";

        if ($conn->query($sql) === false) {
            return ['success' => false, 'message' => 'Błąd podczas aktualizacji kategorii: ' . $conn->error];
        }
    } else {
        // Create new category
        $sql = "INSERT INTO categories (name, slug, description, icon)
                VALUES ('$name', '$slug', '$description', '$icon')";

        if ($conn->query($sql) === false) {
            return ['success' => false, 'message' => 'Błąd podczas tworzenia kategorii: ' . $conn->error];
        }

        $id = $conn->insert_id;
    }

    // Process translations if any
    if (!empty($translations)) {
        foreach ($translations as $lang_code => $trans) {
            if (empty($trans['name'])) continue;

            $trans_name = clean_input($trans['name']);
            $trans_description = isset($trans['description']) ? clean_input($trans['description']) : '';

            // Check if translation exists
            $check_sql = "SELECT id FROM category_translations
                         WHERE category_id = $id AND language_code = '$lang_code'";
            $result = $conn->query($check_sql);

            if ($result && $result->num_rows > 0) {
                // Update translation
                $sql = "UPDATE category_translations SET
                        name = '$trans_name',
                        description = '$trans_description'
                        WHERE category_id = $id AND language_code = '$lang_code'";
            } else {
                // Create translation
                $sql = "INSERT INTO category_translations (category_id, language_code, name, description)
                        VALUES ($id, '$lang_code', '$trans_name', '$trans_description')";
            }

            $conn->query($sql);
        }
    }

    return ['success' => true, 'message' => 'Kategoria zapisana pomyślnie', 'id' => $id];
}

// Function to delete category
function delete_category($id)
{
    global $conn;

    // First delete translations
    $sql = "DELETE FROM category_translations WHERE category_id = " . (int)$id;
    $conn->query($sql);

    // Then delete category
    $sql = "DELETE FROM categories WHERE id = " . (int)$id;
    if ($conn->query($sql) === false) {
        return ['success' => false, 'message' => 'Błąd podczas usuwania kategorii: ' . $conn->error];
    }

    return ['success' => true, 'message' => 'Kategoria została usunięta'];
}

// Function to get category translations
function get_category_translations($category_id)
{
    global $conn;
    $translations = [];

    $sql = "SELECT * FROM category_translations WHERE category_id = " . (int)$category_id;
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $translations[$row['language_code']] = $row;
        }
    }

    return $translations;
}

// Function to save tool
function save_tool($data, $translations = [])
{
    global $conn;

    $name = clean_input($data['name']);

    // Generate slug if empty or validate existing slug
    if (isset($data['slug']) && !empty(trim($data['slug']))) {
        $slug = clean_input($data['slug']);
    } else {
        // Generate slug from name
        $slug = create_slug($name);
    }

    // Final validation - ensure slug is never empty
    if (empty($slug)) {
        return ['success' => false, 'message' => 'Slug narzędzia nie może być pusty'];
    }

    // Dla opisów HTML używamy bezpiecznej metody bez htmlspecialchars
    $description = isset($data['description']) ? $conn->real_escape_string(trim(stripslashes($data['description']))) : '';

    $website_url = isset($data['website_url']) ? clean_input($data['website_url']) : '';
    $category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;
    $featured = isset($data['featured']) ? ($data['featured'] ? 'TRUE' : 'FALSE') : 'FALSE';
    $new_launch = isset($data['new_launch']) ? ($data['new_launch'] ? 'TRUE' : 'FALSE') : 'FALSE';
    $pricing_type = isset($data['pricing_type']) ? clean_input($data['pricing_type']) : 'free';

    // Handle logo and screenshot
    $logo = isset($data['logo']) ? clean_input($data['logo']) : 'default-tool-logo.png';
    $screenshot = isset($data['screenshot']) ? clean_input($data['screenshot']) : '';

    // Check if it's an update or a new tool
    if (isset($data['id']) && !empty($data['id'])) {
        $id = (int)$data['id'];

        // Update tool
        $sql = "UPDATE tools SET
                name = '$name',
                slug = '$slug',
                description = '$description',
                logo = '$logo',
                screenshot = '$screenshot',
                website_url = '$website_url',
                category_id = " . ($category_id ? $category_id : "NULL") . ",
                featured = $featured,
                new_launch = $new_launch,
                pricing_type = '$pricing_type'
                WHERE id = $id";

        if ($conn->query($sql) === false) {
            return ['success' => false, 'message' => 'Błąd podczas aktualizacji narzędzia: ' . $conn->error];
        }
    } else {
        // Create new tool
        $sql = "INSERT INTO tools (name, slug, description, logo, screenshot, website_url, category_id, featured, new_launch, pricing_type)
                VALUES ('$name', '$slug', '$description', '$logo', '$screenshot', '$website_url', " .
            ($category_id ? $category_id : "NULL") . ", $featured, $new_launch, '$pricing_type')";

        if ($conn->query($sql) === false) {
            return ['success' => false, 'message' => 'Błąd podczas tworzenia narzędzia: ' . $conn->error];
        }

        $id = $conn->insert_id;
    }

    // Process translations if any
    if (!empty($translations)) {
        foreach ($translations as $lang_code => $trans) {
            if (empty($trans['name'])) continue;

            $trans_name = clean_input($trans['name']);
            // Dla opisów w tłumaczeniach również nie używamy htmlspecialchars
            $trans_description = isset($trans['description']) ? $conn->real_escape_string(trim(stripslashes($trans['description']))) : '';

            // Check if translation exists
            $check_sql = "SELECT id FROM tool_translations
                         WHERE tool_id = $id AND language_code = '$lang_code'";
            $result = $conn->query($check_sql);

            if ($result && $result->num_rows > 0) {
                // Update translation
                $sql = "UPDATE tool_translations SET
                        name = '$trans_name',
                        description = '$trans_description'
                        WHERE tool_id = $id AND language_code = '$lang_code'";
            } else {
                // Create translation
                $sql = "INSERT INTO tool_translations (tool_id, language_code, name, description)
                        VALUES ($id, '$lang_code', '$trans_name', '$trans_description')";
            }

            $conn->query($sql);
        }
    }

    return ['success' => true, 'message' => 'Narzędzie zapisane pomyślnie', 'id' => $id];
}

// Function to delete tool
function delete_tool($id)
{
    global $conn;

    // First delete translations
    $sql = "DELETE FROM tool_translations WHERE tool_id = " . (int)$id;
    $conn->query($sql);

    // Then delete tool
    $sql = "DELETE FROM tools WHERE id = " . (int)$id;
    if ($conn->query($sql) === false) {
        return ['success' => false, 'message' => 'Błąd podczas usuwania narzędzia: ' . $conn->error];
    }

    return ['success' => true, 'message' => 'Narzędzie zostało usunięte'];
}

// Function to get tool translations
function get_tool_translations($tool_id)
{
    global $conn;
    $translations = [];

    $sql = "SELECT * FROM tool_translations WHERE tool_id = " . (int)$tool_id;
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $translations[$row['language_code']] = $row;
        }
    }

    return $translations;
}

// Function to log activity
function log_activity($user_id, $action, $entity_type = null, $entity_id = null, $details = null)
{
    global $mysqli; // Używamy $mysqli zamiast $conn

    // Sprawdź, czy tabela activity_log istnieje
    $result = $mysqli->query("SHOW TABLES LIKE 'activity_log'");
    $table_name = ($result && $result->num_rows > 0) ? 'activity_log' : 'activity';

    if ($table_name == 'activity_log') {
        // Używaj oryginalnego kodu dla activity_log
        $user_id = $user_id ? (int)$user_id : 'NULL';
        $action = is_string($action) ? "'" . clean_input($action) . "'" : 'NULL';
        $entity_type = $entity_type ? "'" . clean_input($entity_type) . "'" : 'NULL';
        $entity_id = $entity_id ? (int)$entity_id : 'NULL';
        $details = $details ? "'" . clean_input($details) . "'" : 'NULL';
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? "'" . clean_input($_SERVER['REMOTE_ADDR']) . "'" : 'NULL';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? "'" . clean_input($_SERVER['HTTP_USER_AGENT']) . "'" : 'NULL';

        $sql = "INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                VALUES ($user_id, $action, $entity_type, $entity_id, $details, $ip_address, $user_agent)";
    } else {
        // Użyj kompatybilnego kodu dla tabeli activity
        $user_id = $user_id ? (int)$user_id : 'NULL';
        // Sprawdź, czy tabela activity ma kolumnę 'login'
        $result = $mysqli->query("SHOW COLUMNS FROM activity LIKE 'login'");

        if ($result && $result->num_rows > 0) {
            // Jeśli kolumna login istnieje, użyj jej
            $action_value = is_string($action) ? "'" . clean_input($action) . "'" : 'NULL';
            $sql = "INSERT INTO activity (user_id, login) VALUES ($user_id, $action_value)";
        } else {
            // W przeciwnym razie zakładamy, że tabela ma tylko podstawowe kolumny
            $sql = "INSERT INTO activity (user_id) VALUES ($user_id)";
        }
    }

    // Próbuj wykonać zapytanie, ale nie przerwij działania w przypadku błędu
    try {
        $mysqli->query($sql);
    } catch (Exception $e) {
        // Zapisz błąd do logu, ale nie przerywaj działania
        error_log("Błąd podczas logowania aktywności: " . $e->getMessage());
    }
}

// Function to get recent activity
function get_activity_log($limit = 50, $offset = 0, $user_id = null)
{
    global $conn;
    $logs = [];

    $sql = "SELECT a.*, u.username
            FROM activity_log a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE 1=1";

    if ($user_id !== null) {
        $sql .= " AND a.user_id = " . (int)$user_id;
    }

    $sql .= " ORDER BY a.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    return $logs;
}

// Function to get all settings
function get_settings($is_translatable = null)
{
    global $conn, $current_language;
    $settings = [];

    $sql = "SELECT s.*, COALESCE(st.setting_value, s.setting_value) as value
            FROM settings s
            LEFT JOIN setting_translations st ON s.id = st.setting_id AND st.language_code = '$current_language'";

    if ($is_translatable !== null) {
        $sql .= " WHERE s.is_translatable = " . ($is_translatable ? "TRUE" : "FALSE");
    }

    $sql .= " ORDER BY s.setting_key ASC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row;
        }
    }

    return $settings;
}

// Function to get a single setting
function get_setting($key)
{
    global $conn, $current_language;

    $key = clean_input($key);

    $sql = "SELECT s.*, COALESCE(st.setting_value, s.setting_value) as value
            FROM settings s
            LEFT JOIN setting_translations st ON s.id = st.setting_id AND st.language_code = '$current_language'
            WHERE s.setting_key = '$key'";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row;
    }

    return null;
}

// Function to save setting
function save_setting($key, $value, $translations = [], $type = 'text', $is_translatable = false)
{
    global $conn;

    $key = clean_input($key);
    $value = clean_input($value);
    $type = clean_input($type);
    $is_translatable = $is_translatable ? 'TRUE' : 'FALSE';

    // Check if setting exists
    $sql = "SELECT id FROM settings WHERE setting_key = '$key'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];

        // Update setting
        $sql = "UPDATE settings SET
                setting_value = '$value',
                setting_type = '$type',
                is_translatable = $is_translatable
                WHERE id = $id";
    } else {
        // Create setting
        $sql = "INSERT INTO settings (setting_key, setting_value, setting_type, is_translatable)
                VALUES ('$key', '$value', '$type', $is_translatable)";
    }

    if ($conn->query($sql) === false) {
        return ['success' => false, 'message' => 'Błąd podczas zapisywania ustawienia: ' . $conn->error];
    }

    // Get setting ID if we didn't have it before
    if (!isset($id)) {
        $id = $conn->insert_id;
    }

    // Process translations if any and if setting is translatable
    if ($is_translatable === 'TRUE' && !empty($translations)) {
        foreach ($translations as $lang_code => $trans_value) {
            $trans_value = clean_input($trans_value);

            // Check if translation exists
            $check_sql = "SELECT id FROM setting_translations
                         WHERE setting_id = $id AND language_code = '$lang_code'";
            $result = $conn->query($check_sql);

            if ($result && $result->num_rows > 0) {
                // Update translation
                $sql = "UPDATE setting_translations SET
                        setting_value = '$trans_value'
                        WHERE setting_id = $id AND language_code = '$lang_code'";
            } else {
                // Create translation
                $sql = "INSERT INTO setting_translations (setting_id, language_code, setting_value)
                        VALUES ($id, '$lang_code', '$trans_value')";
            }

            $conn->query($sql);
        }
    }

    return ['success' => true, 'message' => 'Ustawienie zapisane pomyślnie', 'id' => $id];
}

// Function to delete setting
function delete_setting($key)
{
    global $conn;

    $key = clean_input($key);

    // Get setting ID first
    $sql = "SELECT id FROM settings WHERE setting_key = '$key'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];

        // Delete translations first
        $sql = "DELETE FROM setting_translations WHERE setting_id = $id";
        $conn->query($sql);

        // Then delete setting
        $sql = "DELETE FROM settings WHERE id = $id";
        if ($conn->query($sql) === false) {
            return ['success' => false, 'message' => 'Błąd podczas usuwania ustawienia: ' . $conn->error];
        }

        return ['success' => true, 'message' => 'Ustawienie zostało usunięte'];
    }

    return ['success' => false, 'message' => 'Ustawienie nie istnieje'];
}

// Function to get users
function get_users($limit = 20, $offset = 0, $role = null, $status = null)
{
    global $conn;
    $users = [];

    $sql = "SELECT * FROM users WHERE 1=1";

    if ($role !== null) {
        $sql .= " AND role = '" . clean_input($role) . "'";
    }

    if ($status !== null) {
        $sql .= " AND status = '" . clean_input($status) . "'";
    }

    $sql .= " ORDER BY id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Don't include password hash
            unset($row['password']);
            $users[] = $row;
        }
    }

    return $users;
}

// Function to get a single user
function get_user($id)
{
    global $conn;

    $sql = "SELECT * FROM users WHERE id = " . (int)$id;
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Don't include password hash
        unset($user['password']);
        return $user;
    }

    return null;
}

// Function to save user
function save_user($data)
{
    global $conn;

    $username = clean_input($data['username']);
    $email = clean_input($data['email']);
    $role = isset($data['role']) ? clean_input($data['role']) : 'user';
    $first_name = isset($data['first_name']) ? clean_input($data['first_name']) : '';
    $last_name = isset($data['last_name']) ? clean_input($data['last_name']) : '';
    $status = isset($data['status']) ? clean_input($data['status']) : 'active';

    // Check if it's an update or a new user
    if (isset($data['id']) && !empty($data['id'])) {
        $id = (int)$data['id'];

        // Check if password is being updated
        $password_sql = '';
        if (isset($data['password']) && !empty($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $password_sql = "password = '$password', ";
        }

        // Update user
        $sql = "UPDATE users SET
                username = '$username',
                $password_sql
                email = '$email',
                role = '$role',
                first_name = '$first_name',
                last_name = '$last_name',
                status = '$status'
                WHERE id = $id";

        if ($conn->query($sql) === false) {
            return ['success' => false, 'message' => 'Błąd podczas aktualizacji użytkownika: ' . $conn->error];
        }
    } else {
        // Create new user
        if (!isset($data['password']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Hasło jest wymagane dla nowego użytkownika'];
        }

        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, password, email, role, first_name, last_name, status)
                VALUES ('$username', '$password', '$email', '$role', '$first_name', '$last_name', '$status')";

        if ($conn->query($sql) === false) {
            return ['success' => false, 'message' => 'Błąd podczas tworzenia użytkownika: ' . $conn->error];
        }

        $id = $conn->insert_id;
    }

    return ['success' => true, 'message' => 'Użytkownik zapisany pomyślnie', 'id' => $id];
}

// Function to delete user
function delete_user($id)
{
    global $conn;

    // Delete user
    $sql = "DELETE FROM users WHERE id = " . (int)$id;
    if ($conn->query($sql) === false) {
        return ['success' => false, 'message' => 'Błąd podczas usuwania użytkownika: ' . $conn->error];
    }

    return ['success' => true, 'message' => 'Użytkownik został usunięty'];
}

// Function to get localized slug for a category
function get_localized_category_slug($category_id, $lang_code)
{
    global $conn, $current_language;

    // Default to provided language, or current language if not specified
    if ($lang_code === null) {
        $lang_code = $current_language;
    }

    // Just get the original slug from categories table - don't try to get translated slug
    $sql = "SELECT slug FROM categories WHERE id = " . (int)$category_id;

    try {
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['slug'];
        }
    } catch (Exception $e) {
        // Log error silently
        error_log("Error in get_localized_category_slug: " . $e->getMessage());
    }

    return ''; // Return empty if not found
}

// Function to get localized slug for a tool
function get_localized_tool_slug($tool_id, $lang_code)
{
    global $conn, $current_language;

    // Default to provided language, or current language if not specified
    if ($lang_code === null) {
        $lang_code = $current_language;
    }

    // Just get the original slug from tools table - don't try to get translated slug
    $sql = "SELECT slug FROM tools WHERE id = " . (int)$tool_id;

    try {
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['slug'];
        }
    } catch (Exception $e) {
        // Log error silently
        error_log("Error in get_localized_tool_slug: " . $e->getMessage());
    }

    return ''; // Return empty if not found
}

// Function to get the URL for a category in current or specified language
function get_category_url($category, $lang_code = null, $page = null)
{
    global $current_language;

    // Use current language if not specified
    if ($lang_code === null) {
        $lang_code = $current_language;
    }

    // Simply use the original slug from category data
    // This avoids dealing with potentially non-existent translations
    $slug = isset($category['slug']) ? $category['slug'] : '';

    $url = '/' . $lang_code . '/category/' . $slug;

    // Add page number if specified
    if ($page !== null && $page > 1) {
        $url .= '/page/' . $page;
    }

    return $url;
}

// Function to get the URL for a tool in current or specified language
function get_tool_url($tool, $lang_code = null, $page = null)
{
    global $current_language;

    // Use current language if not specified
    if ($lang_code === null) {
        $lang_code = $current_language;
    }

    // Simply use the original slug from tool data
    // This avoids dealing with potentially non-existent translations
    $slug = isset($tool['slug']) ? $tool['slug'] : '';

    $url = '/' . $lang_code . '/tool/' . $slug;

    // Add page number if specified
    if ($page !== null && $page > 1) {
        $url .= '/page/' . $page;
    }

    return $url;
}

// Function to generate a localized slug for categories or tools
function create_localized_slug($text, $lang_code)
{
    // Base slug creation
    $slug = create_slug($text);

    // For languages that might need special handling
    switch ($lang_code) {
        case 'ru':
            // Transliterate from Cyrillic to Latin for Russian
            // This is a simple example - you might want a more sophisticated transliteration
            $slug = transliterate_russian_to_latin($text);
            break;
            // Add cases for other languages as needed
    }

    return $slug;
}

// Simple transliteration function for Russian (example)
function transliterate_russian_to_latin($text)
{
    $transliteration = array(
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'yo',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'j',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'h',
        'ц' => 'c',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'sch',
        'ъ' => '',
        'ы' => 'y',
        'ь' => '',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya',
        'А' => 'A',
        'Б' => 'B',
        'В' => 'V',
        'Г' => 'G',
        'Д' => 'D',
        'Е' => 'E',
        'Ё' => 'Yo',
        'Ж' => 'Zh',
        'З' => 'Z',
        'И' => 'I',
        'Й' => 'J',
        'К' => 'K',
        'Л' => 'L',
        'М' => 'M',
        'Н' => 'N',
        'О' => 'O',
        'П' => 'P',
        'Р' => 'R',
        'С' => 'S',
        'Т' => 'T',
        'У' => 'U',
        'Ф' => 'F',
        'Х' => 'H',
        'Ц' => 'C',
        'Ч' => 'Ch',
        'Ш' => 'Sh',
        'Щ' => 'Sch',
        'Ъ' => '',
        'Ы' => 'Y',
        'Ь' => '',
        'Э' => 'E',
        'Ю' => 'Yu',
        'Я' => 'Ya',
        ' ' => '-'
    );

    $text = strtr($text, $transliteration);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');

    return $text;
}

// Function to get tags for a tool
function get_tool_tags($tool_id, $limit = null)
{
    global $conn;
    $tags = array();

    $sql = "SELECT t.id, t.name, t.slug
            FROM tags t
            JOIN tool_tags tt ON t.id = tt.tag_id
            WHERE tt.tool_id = " . (int)$tool_id . "
            ORDER BY t.name ASC";

    // Add limit only if specified
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit;
    }

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }

    return $tags;
}

// Function to get all available tags
function get_all_tags()
{
    global $conn;
    $tags = [];

    $sql = "SELECT id, name, slug FROM tags ORDER BY name ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }

    return $tags;
}

// Function to update tool tags
function update_tool_tags($tool_id, $tag_ids)
{
    global $conn;

    // Validate input
    if (empty($tool_id) || !is_numeric($tool_id)) {
        return false;
    }

    // First, delete all existing associations for this tool
    $delete_sql = "DELETE FROM tool_tags WHERE tool_id = " . (int)$tool_id;
    $conn->query($delete_sql);

    // Then add the new tag associations
    if (!empty($tag_ids)) {
        $validated_tags = [];

        foreach ($tag_ids as $tag_id) {
            // Sprawdź przedrostek 'new:' dla nowych tagów
            if (is_string($tag_id) && strpos($tag_id, 'new:') === 0) {
                // To jest nowy tag - wyodrębnij nazwę tagu po przedrostku 'new:'
                $tag_name = substr($tag_id, 4); // Pomijamy 'new:'
                if (!empty($tag_name)) {
                    // Sprawdź, czy tag o tej nazwie już istnieje
                    $check_sql = "SELECT id FROM tags WHERE name = '" . $conn->real_escape_string($tag_name) . "'";
                    $check_result = $conn->query($check_sql);

                    if ($check_result && $check_result->num_rows > 0) {
                        // Tag już istnieje, pobierz jego ID
                        $tag_row = $check_result->fetch_assoc();
                        $validated_tags[] = (int)$tag_row['id'];
                    } else {
                        // Stwórz nowy tag
                        $insert_sql = "INSERT INTO tags (name, slug) VALUES ('" .
                                    $conn->real_escape_string($tag_name) . "', '" .
                                    $conn->real_escape_string(create_slug($tag_name)) . "')";

                        if ($conn->query($insert_sql)) {
                            $validated_tags[] = (int)$conn->insert_id;
                        }
                    }
                }
                continue; // Przejdź do następnego tagu
            }
        }
    }

    return true;
}

// Function to create new tag if it doesn't exist
function create_tag($name)
{
    global $conn;

    // Check if tag already exists
    $slug = create_slug($name);
    $check_sql = "SELECT id FROM tags WHERE slug = '" . $conn->real_escape_string($slug) . "'";
    $result = $conn->query($check_sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id']; // Return existing tag ID
    }

    // Create new tag
    $insert_sql = "INSERT INTO tags (name, slug) VALUES ('" .
        $conn->real_escape_string($name) . "', '" .
        $conn->real_escape_string($slug) . "')";

    if ($conn->query($insert_sql)) {
        return $conn->insert_id; // Return new tag ID
    }

    return false;
}

// Function to add a new language
function add_language($code, $name, $native_name, $text_direction = 'ltr')
{
    global $conn;

    // Validate language code (should be 2 chars)
    if (strlen($code) !== 2) {
        return [
            'success' => false,
            'message' => 'Kod języka musi składać się z 2 znaków.'
        ];
    }

    // Check if language already exists
    $check_sql = "SELECT * FROM languages WHERE code = '" . $conn->real_escape_string($code) . "'";
    $result = $conn->query($check_sql);

    if ($result && $result->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'Język o podanym kodzie już istnieje.'
        ];
    }

    // Insert new language
    $insert_sql = "INSERT INTO languages (code, name, native_name, text_direction, active) VALUES (
        '" . $conn->real_escape_string($code) . "',
        '" . $conn->real_escape_string($name) . "',
        '" . $conn->real_escape_string($native_name) . "',
        '" . $conn->real_escape_string($text_direction) . "',
        1
    )";

    if ($conn->query($insert_sql)) {
        return [
            'success' => true,
            'message' => 'Język został dodany pomyślnie.',
            'id' => $conn->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Błąd podczas dodawania języka: ' . $conn->error
        ];
    }
}

// Function to update language
function update_language($code, $name, $native_name, $text_direction = 'ltr')
{
    global $conn;

    // Check if language exists
    $check_sql = "SELECT * FROM languages WHERE code = '" . $conn->real_escape_string($code) . "'";
    $result = $conn->query($check_sql);

    if (!$result || $result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Język o podanym kodzie nie istnieje.'
        ];
    }

    // Update language
    $update_sql = "UPDATE languages SET
        name = '" . $conn->real_escape_string($name) . "',
        native_name = '" . $conn->real_escape_string($native_name) . "',
        text_direction = '" . $conn->real_escape_string($text_direction) . "'
        WHERE code = '" . $conn->real_escape_string($code) . "'";

    if ($conn->query($update_sql)) {
        return [
            'success' => true,
            'message' => 'Język został zaktualizowany pomyślnie.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Błąd podczas aktualizacji języka: ' . $conn->error
        ];
    }
}

// Function to toggle language active status
function toggle_language($code, $active = true)
{
    global $conn, $default_language;

    // Cannot disable default language
    if ($code === $default_language && !$active) {
        return [
            'success' => false,
            'message' => 'Nie można wyłączyć domyślnego języka.'
        ];
    }

    // Check if language exists
    $check_sql = "SELECT * FROM languages WHERE code = '" . $conn->real_escape_string($code) . "'";
    $result = $conn->query($check_sql);

    if (!$result || $result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Język o podanym kodzie nie istnieje.'
        ];
    }

    // Update language active status
    $update_sql = "UPDATE languages SET active = " . ($active ? 1 : 0) .
        " WHERE code = '" . $conn->real_escape_string($code) . "'";

    if ($conn->query($update_sql)) {
        return [
            'success' => true,
            'message' => 'Status języka został zmieniony pomyślnie.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Błąd podczas zmiany statusu języka: ' . $conn->error
        ];
    }
}

// Function to delete language
function delete_language($code)
{
    global $conn, $default_language;

    // Cannot delete default language
    if ($code === $default_language) {
        return [
            'success' => false,
            'message' => 'Nie można usunąć domyślnego języka.'
        ];
    }

    // Check if language exists
    $check_sql = "SELECT * FROM languages WHERE code = '" . $conn->real_escape_string($code) . "'";
    $result = $conn->query($check_sql);

    if (!$result || $result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Język o podanym kodzie nie istnieje.'
        ];
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete language translations
        $conn->query("DELETE FROM translations WHERE language_code = '" . $conn->real_escape_string($code) . "'");

        // Delete tool translations
        $conn->query("DELETE FROM tool_translations WHERE language_code = '" . $conn->real_escape_string($code) . "'");

        // Delete category translations
        $conn->query("DELETE FROM category_translations WHERE language_code = '" . $conn->real_escape_string($code) . "'");

        // Delete setting translations
        $conn->query("DELETE FROM setting_translations WHERE language_code = '" . $conn->real_escape_string($code) . "'");

        // Delete language
        $conn->query("DELETE FROM languages WHERE code = '" . $conn->real_escape_string($code) . "'");

        // Commit transaction
        $conn->commit();

        return [
            'success' => true,
            'message' => 'Język został usunięty pomyślnie.'
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();

        return [
            'success' => false,
            'message' => 'Błąd podczas usuwania języka: ' . $e->getMessage()
        ];
    }
}

// Function to set default language
function set_default_language($code)
{
    global $conn;

    // Check if language exists and is active
    $check_sql = "SELECT * FROM languages WHERE code = '" . $conn->real_escape_string($code) . "' AND active = 1";
    $result = $conn->query($check_sql);

    if (!$result || $result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Wybrany język nie istnieje lub nie jest aktywny.'
        ];
    }

    // Update default language setting
    $update_sql = "UPDATE settings SET setting_value = '" . $conn->real_escape_string($code) . "' WHERE setting_key = 'default_language'";

    if (!$conn->query($update_sql)) {
        // If setting doesn't exist, create it
        $insert_sql = "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('default_language', '" .
            $conn->real_escape_string($code) . "', 'text')";

        if (!$conn->query($insert_sql)) {
            return [
                'success' => false,
                'message' => 'Błąd podczas zapisywania domyślnego języka: ' . $conn->error
            ];
        }
    }

    return [
        'success' => true,
        'message' => 'Domyślny język został zmieniony pomyślnie.'
    ];
}

// Function to get user by ID
function get_user_by_id($user_id)
{
    global $conn;

    $user_id = (int)$user_id;
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Function to get available languages (optionally including inactive languages)
function get_available_languages($include_inactive = false)
{
    global $available_languages, $conn;

    // Sprawdź czy tabela languages istnieje
    $table_check = $conn->query("SHOW TABLES LIKE 'languages'");
    $table_exists = $table_check && $table_check->num_rows > 0;

    // Jeśli tabela languages nie istnieje, zwróć dostępne języki z konfiguracji
    if (!$table_exists) {
        return $available_languages;
    }

    // Pobierz języki z bazy danych
    $languages = [];

    $sql = "SELECT * FROM languages";
    if (!$include_inactive) {
        $sql .= " WHERE active = 1";
    }
    $sql .= " ORDER BY name ASC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $languages[$row['code']] = [
                'name' => $row['name'],
                'native_name' => $row['native_name'],
                'text_direction' => $row['text_direction'],
                'active' => $row['active'] == 1
            ];
        }
        return $languages;
    }

    // Jeśli brak języków w bazie, zwróć z konfiguracji
    return $available_languages;
}

// Function to generate optimized image tag with lazy loading
function optimized_image($src, $alt = '', $attributes = [])
{
    // Ensure we have default values for important attributes
    $default_attributes = [
        'loading' => 'lazy',
        'decoding' => 'async',
        'class' => '',
        'width' => '',
        'height' => ''
    ];

    // Merge default attributes with provided attributes
    $attributes = array_merge($default_attributes, $attributes);

    // Build attributes string
    $attr_string = '';
    foreach ($attributes as $key => $value) {
        if (!empty($value)) {
            $attr_string .= " $key=\"" . htmlspecialchars($value) . "\"";
        }
    }

    return "<img src=\"$src\" alt=\"" . htmlspecialchars($alt) . "\"$attr_string>";
}
