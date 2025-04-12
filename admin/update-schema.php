<?php
// Plik do aktualizacji schematu bazy danych o nowe kolumny dla konfiguracji thum.io
include_once '../includes/config.php';
include_once '../includes/db_config.php';

// Inicjalizacja tablicy rezultatów
$results = array();

// Dodaj kolumnę thumio_width do tabeli tools, jeśli nie istnieje
$sql = "SHOW COLUMNS FROM tools LIKE 'thumio_width'";
$result = $conn->query($sql);
if ($result && $result->num_rows == 0) {
    $sql = "ALTER TABLE tools ADD COLUMN thumio_width INT DEFAULT 800 AFTER image_type";
    if($conn->query($sql) === false){
        $results[] = "BŁĄD: Nie można dodać kolumny thumio_width do tabeli tools. " . $conn->error;
        error_log("ERROR: Could not add thumio_width column to tools table. " . $conn->error);
    } else {
        $results[] = "SUKCES: Pomyślnie dodano kolumnę thumio_width do tabeli tools.";
        error_log("INFO: Successfully added thumio_width column to tools table.");
    }
}

// Dodaj kolumnę thumio_format do tabeli tools, jeśli nie istnieje
$sql = "SHOW COLUMNS FROM tools LIKE 'thumio_format'";
$result = $conn->query($sql);
if ($result && $result->num_rows == 0) {
    $sql = "ALTER TABLE tools ADD COLUMN thumio_format VARCHAR(10) DEFAULT 'png' AFTER thumio_width";
    if($conn->query($sql) === false){
        $results[] = "BŁĄD: Nie można dodać kolumny thumio_format do tabeli tools. " . $conn->error;
        error_log("ERROR: Could not add thumio_format column to tools table. " . $conn->error);
    } else {
        $results[] = "SUKCES: Pomyślnie dodano kolumnę thumio_format do tabeli tools.";
        error_log("INFO: Successfully added thumio_format column to tools table.");
    }
}

// Dodaj kolumnę thumio_viewport do tabeli tools, jeśli nie istnieje
$sql = "SHOW COLUMNS FROM tools LIKE 'thumio_viewport'";
$result = $conn->query($sql);
if ($result && $result->num_rows == 0) {
    $sql = "ALTER TABLE tools ADD COLUMN thumio_viewport VARCHAR(20) DEFAULT 'desktop' AFTER thumio_format";
    if($conn->query($sql) === false){
        $results[] = "BŁĄD: Nie można dodać kolumny thumio_viewport do tabeli tools. " . $conn->error;
        error_log("ERROR: Could not add thumio_viewport column to tools table. " . $conn->error);
    } else {
        $results[] = "SUKCES: Pomyślnie dodano kolumnę thumio_viewport do tabeli tools.";
        error_log("INFO: Successfully added thumio_viewport column to tools table.");
    }
}

// Dodaj kolumnę generate_thumbnails do tabeli tools, jeśli nie istnieje
$sql = "SHOW COLUMNS FROM tools LIKE 'generate_thumbnails'";
$result = $conn->query($sql);
if ($result && $result->num_rows == 0) {
    $sql = "ALTER TABLE tools ADD COLUMN generate_thumbnails BOOLEAN DEFAULT TRUE AFTER thumio_viewport";
    if($conn->query($sql) === false){
        $results[] = "BŁĄD: Nie można dodać kolumny generate_thumbnails do tabeli tools. " . $conn->error;
        error_log("ERROR: Could not add generate_thumbnails column to tools table. " . $conn->error);
    } else {
        $results[] = "SUKCES: Pomyślnie dodano kolumnę generate_thumbnails do tabeli tools.";
        error_log("INFO: Successfully added generate_thumbnails column to tools table.");
    }
}

// Wyświetl wyniki
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktualizacja schematu bazy danych</title>
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
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 10px;
            padding: 10px;
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
        <h1>Aktualizacja schematu bazy danych</h1>

        <?php if(empty($results)): ?>
            <p>Wszystkie potrzebne tabele i kolumny już istnieją. Nie potrzeba żadnych aktualizacji.</p>
        <?php else: ?>
            <h2>Wyniki aktualizacji:</h2>
            <ul>
                <?php foreach($results as $result): ?>
                    <?php if(strpos($result, 'SUKCES') !== false): ?>
                        <li class="success"><?php echo htmlspecialchars($result); ?></li>
                    <?php else: ?>
                        <li class="error"><?php echo htmlspecialchars($result); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a href="tool-edit.php" class="back-link">Powrót do edycji narzędzi</a>
    </div>
</body>
</html>
