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
        'message' => 'Brak identyfikatora kategorii.'
    ];
    header('Location: categories.php');
    exit;
}

$category_id = (int)$_POST['id'];

// Check if category exists
$category = get_category($category_id);
if (!$category) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Kategoria nie została znaleziona.'
    ];
    header('Location: categories.php');
    exit;
}

// Delete category
$result = delete_category($category_id);

if ($result['success']) {
    // Log activity
    log_activity($_SESSION['user_id'], 'delete', 'category', $category_id);

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

// Redirect back to categories list
header('Location: categories.php');
exit;
