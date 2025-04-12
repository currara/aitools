<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log activity before destroying session
if (isset($_SESSION['user_id'])) {
    log_activity($_SESSION['user_id'], 'logout');
}

// Clear session variables
$_SESSION = array();

// If session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
