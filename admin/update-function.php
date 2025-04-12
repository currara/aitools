<?php
// Skrypt do aktualizacji funkcji save_tool w pliku functions.php
include_once '../includes/config.php';

// Ścieżka do pliku functions.php
$functions_file = '../includes/functions.php';

// Nowa implementacja funkcji save_tool
$new_function = <<<'EOD'
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
    $image_type = isset($data['image_type']) ? clean_input($data['image_type']) : 'screenshot';

    // Nowe parametry thum.io
    $thumio_width = isset($data['thumio_width']) ? (int)$data['thumio_width'] : 800;
    $thumio_format = isset($data['thumio_format']) ? clean_input($data['thumio_format']) : 'png';
    $thumio_viewport = isset($data['thumio_viewport']) ? clean_input($data['thumio_viewport']) : 'desktop';
    $generate_thumbnails = isset($data['generate_thumbnails']) ? ($data['generate_thumbnails'] ? 'TRUE' : 'FALSE') : 'TRUE';

    // Check if it's an update or a new tool
    if (isset($data['id']) && !empty($data['id'])) {
        $id = (int)$data['id'];

        // Update tool with new fields
        $sql = "UPDATE tools SET
                name = '$name',
                slug = '$slug',
                description = '$description',
                logo = '$logo',
                screenshot = '$screenshot',
                image_type = '$image_type',
                thumio_width = $thumio_width,
                thumio_format = '$thumio_format',
                thumio_viewport = '$thumio_viewport',
                generate_thumbnails = $generate_thumbnails,
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
        // Create new tool with new fields
        $sql = "INSERT INTO tools (
                name, slug, description, logo, screenshot, image_type,
                thumio_width, thumio_format, thumio_viewport, generate_thumbnails,
                website_url, category_id, featured, new_launch, pricing_type
                ) VALUES (
                '$name', '$slug', '$description', '$logo', '$screenshot', '$image_type',
                $thumio_width, '$thumio_format', '$thumio_viewport', $generate_thumbnails,
                '$website_url', " . ($category_id ? $category_id : "NULL") . ", $featured, $new_launch, '$pricing_type'
                )";

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
EOD;

// Odczytaj zawartość pliku
$content = file_get_contents($functions_file);
if ($content === false) {
    die("Nie można odczytać pliku functions.php");
}

// Znajdź początek i koniec funkcji save_tool
$pattern = '/function save_tool\(\$data, \$translations = \[\]\)\s*\{(?:[^{}]*+|(?R))*+\}/s';
if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    $old_function = $matches[0][0];
    $start_pos = $matches[0][1];

    // Zastąp starą funkcję nową
    $new_content = substr_replace($content, $new_function, $start_pos, strlen($old_function));

    // Zapisz zmiany do pliku
    if (file_put_contents($functions_file, $new_content)) {
        $success = true;
        $message = "Funkcja save_tool została pomyślnie zaktualizowana.";
    } else {
        $success = false;
        $message = "Nie udało się zapisać zmian w pliku functions.php.";
    }
} else {
    $success = false;
    $message = "Nie znaleziono funkcji save_tool w pliku functions.php.";
}

// Wyświetl wynik
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktualizacja funkcji save_tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Aktualizacja funkcji save_tool</h1>

        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>

        <a href="update-schema.php" class="back-link">Przejdź do aktualizacji schematu bazy danych</a>
    </div>
</body>
</html>
