<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php?redirect=admin');
    exit;
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Brak identyfikatora użytkownika.'
    ];
    header('Location: users.php');
    exit;
}

$user_id = (int)$_POST['id'];

// Prevent deleting own account
if ($user_id === (int)$_SESSION['user_id']) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Nie możesz usunąć swojego konta.'
    ];
    header('Location: users.php');
    exit;
}

// Check if user exists
$user = get_user($user_id);
if (!$user) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Użytkownik nie został znaleziony.'
    ];
    header('Location: users.php');
    exit;
}

// Delete user
$result = delete_user($user_id);

if ($result['success']) {
    // Log activity
    log_activity($_SESSION['user_id'], 'delete', 'user', $user_id);

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

// Redirect back to users list
header('Location: users.php');
exit;
