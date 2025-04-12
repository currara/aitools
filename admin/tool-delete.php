<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin or editor
if (!is_logged_in() || (!is_admin() && !is_editor())) {
    header('Location: ../login.php?redirect=admin');
    exit;
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Brak identyfikatora narzędzia.'
    ];
    header('Location: tools.php');
    exit;
}

$tool_id = (int)$_POST['id'];

// Check if tool exists
$tool = get_tool($tool_id);
if (!$tool) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Narzędzie nie zostało znalezione.'
    ];
    header('Location: tools.php');
    exit;
}

// Delete tool
$result = delete_tool($tool_id);

if ($result['success']) {
    // Log activity
    log_activity($_SESSION['user_id'], 'delete', 'tool', $tool_id);

    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => $result['message']
    ];
} else {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => $result['message']
    ];
}

// Redirect back to tools list
header('Location: tools.php');
exit;
