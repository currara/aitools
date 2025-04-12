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
debug_log('Upvote request received', [
    'POST' => $_POST,
    'GET' => $_GET,
    'SERVER' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
        'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'No referer'
    ],
    'LANGUAGE' => $current_language
]);

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'upvotes' => 0
];

// Check if it's a check request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check']) && isset($_GET['tool_id'])) {
    $tool_id = (int)$_GET['tool_id'];

    // Validate tool ID
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else {
        // Check if tool exists
        $sql = "SELECT id, upvotes FROM tools WHERE id = $tool_id";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $tool = $result->fetch_assoc();

            // Get user information
            $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $ip_address = $_SERVER['REMOTE_ADDR'];

            // Check if the user or IP already upvoted this tool
            $check_sql = "SELECT id FROM upvotes WHERE tool_id = $tool_id AND ";

            if ($user_id) {
                $check_sql .= "user_id = $user_id";
            } else {
                $check_sql .= "ip_address = '$ip_address' AND user_id IS NULL";
            }

            $check_result = $conn->query($check_sql);

            if ($check_result && $check_result->num_rows > 0) {
                // User already upvoted
                $response['already_upvoted'] = true;
                $response['upvotes'] = $tool['upvotes'];
            } else {
                $response['already_upvoted'] = false;
                $response['upvotes'] = $tool['upvotes'];
            }

            $response['success'] = true;
        } else {
            $response['message'] = __('tool_not_found');
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get tool ID
    $tool_id = isset($_POST['tool_id']) ? (int)$_POST['tool_id'] : 0;

    // Validate tool ID
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else {
        // Check if tool exists
        $sql = "SELECT id, upvotes FROM tools WHERE id = $tool_id";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $tool = $result->fetch_assoc();

            // Get user information
            $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $ip_address = $_SERVER['REMOTE_ADDR'];

            // Check if the user or IP already upvoted this tool
            $check_sql = "SELECT id FROM upvotes WHERE tool_id = $tool_id AND ";

            if ($user_id) {
                $check_sql .= "user_id = $user_id";
            } else {
                $check_sql .= "ip_address = '$ip_address' AND user_id IS NULL";
            }

            $check_result = $conn->query($check_sql);

            if ($check_result && $check_result->num_rows > 0) {
                // User already upvoted
                $response['success'] = false;
                $response['message'] = __('already_upvoted');
                $response['upvotes'] = $tool['upvotes'];
            } else {
                // Record the upvote
                $insert_sql = "INSERT INTO upvotes (tool_id, user_id, ip_address) VALUES ($tool_id, ";
                $insert_sql .= $user_id ? "$user_id" : "NULL";
                $insert_sql .= ", '$ip_address')";

                if ($conn->query($insert_sql)) {
                    // Update the tool's upvote count
                    $update_sql = "UPDATE tools SET upvotes = upvotes + 1 WHERE id = $tool_id";

                    if ($conn->query($update_sql)) {
                        $updated_upvotes = $tool['upvotes'] + 1;

                        $response['success'] = true;
                        $response['message'] = __('upvote_recorded');
                        $response['upvotes'] = $updated_upvotes;
                    } else {
                        $response['message'] = sprintf(__('upvote_error'), $conn->error);
                    }
                } else {
                    $response['message'] = sprintf(__('upvote_error'), $conn->error);
                }
            }
        } else {
            $response['message'] = __('tool_not_found');
        }
    }
} else {
    $response['message'] = __('invalid_request_method');
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
