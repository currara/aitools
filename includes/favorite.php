<?php
// Include configuration
require_once 'config.php';

// Set language if passed in request
if (isset($_POST['lang']) && array_key_exists($_POST['lang'], $available_languages)) {
    $current_language = $_POST['lang'];
    $lang = load_language($current_language);
} elseif (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $current_language = $_GET['lang'];
    $lang = load_language($current_language);
}

// Debug request
debug_log('Favorite request received', [
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'POST' => $_POST,
    'GET' => $_GET,
    'LANGUAGE' => $current_language,
    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
    'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'No referer'
]);

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get tool ID
    $tool_id = isset($_POST['tool_id']) ? (int)$_POST['tool_id'] : 0;

    // Validate tool ID
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else {
        // Check if tool exists
        $sql = "SELECT id FROM tools WHERE id = $tool_id";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // Check if user is logged in
            if (isset($_SESSION['user_id'])) {
                $user_id = (int)$_SESSION['user_id'];

                // Check if tool is already in favorites
                $check_sql = "SELECT id FROM favorites WHERE tool_id = $tool_id AND user_id = $user_id";
                $check_result = $conn->query($check_sql);

                if ($check_result && $check_result->num_rows > 0) {
                    // Tool already in favorites, remove it
                    $delete_sql = "DELETE FROM favorites WHERE tool_id = $tool_id AND user_id = $user_id";

                    if ($conn->query($delete_sql)) {
                        $response['success'] = true;
                        $response['message'] = __('removed_from_favorites');
                        $response['in_favorites'] = false;
                    } else {
                        $response['message'] = sprintf(__('favorites_error'), $conn->error);
                    }
                } else {
                    // Add tool to favorites
                    $insert_sql = "INSERT INTO favorites (tool_id, user_id) VALUES ($tool_id, $user_id)";

                    if ($conn->query($insert_sql)) {
                        $response['success'] = true;
                        $response['message'] = __('added_to_favorites');
                        $response['in_favorites'] = true;
                    } else {
                        $response['message'] = sprintf(__('favorites_error'), $conn->error);
                    }
                }
            } else {
                // User is not logged in, redirect to login page
                $response['message'] = __('login_required');
                // Don't add language prefix for default language
                $response['redirect'] = ($current_language === $default_language) ? '/login' : '/' . $current_language . '/login';
            }
        } else {
            $response['message'] = __('tool_not_found');
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if tool is in favorites (for GET requests)
    $tool_id = isset($_GET['tool_id']) ? (int)$_GET['tool_id'] : 0;

    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
        $check_sql = "SELECT id FROM favorites WHERE tool_id = $tool_id AND user_id = $user_id";
        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $response['success'] = true;
            $response['in_favorites'] = true;
        } else {
            $response['success'] = true;
            $response['in_favorites'] = false;
        }
    } else {
        $response['success'] = true;
        $response['in_favorites'] = false;
        $response['message'] = __('login_required');
    }
} else {
    $response['message'] = __('invalid_request_method');
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
