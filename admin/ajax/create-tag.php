<?php
// Plik AJAX do tworzenia nowych tagów
header('Content-Type: application/json');

// Uwzględnij konfigurację
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany i ma uprawnienia
if (!is_admin() && !is_editor()) {
    echo json_encode(['success' => false, 'message' => 'Brak uprawnień']);
    exit;
}

// Sprawdź, czy to jest żądanie utworzenia tagu
if (isset($_POST['create_tag']) && isset($_POST['tag_name'])) {
    $tag_name = trim($_POST['tag_name']);

    if (empty($tag_name)) {
        echo json_encode(['success' => false, 'message' => 'Nazwa tagu nie może być pusta']);
        exit;
    }

    // Sprawdź, czy tag już istnieje
    $tag_id = create_tag($tag_name);

    if ($tag_id) {
        echo json_encode(['success' => true, 'id' => $tag_id, 'name' => $tag_name]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nie udało się utworzyć tagu']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowe żądanie']);
}
