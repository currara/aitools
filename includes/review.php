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
debug_log('Review request received', [
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
    'message' => '',
    'review_id' => 0
];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get review data
    $tool_id = isset($_POST['tool_id']) ? (int)$_POST['tool_id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    // Validate input
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } elseif (empty($content)) {
        $response['message'] = __('review_content_required');
    } else {
        // Check if tool exists
        $sql = "SELECT id FROM tools WHERE id = $tool_id";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // Get user information
            $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL;
            $ip_address = $_SERVER['REMOTE_ADDR'];

            // Check if user is logged in
            if ($user_id) {
                // Clean input data
                $title = $conn->real_escape_string($title);
                $content = $conn->real_escape_string($content);

                // Check if user already submitted a review for this tool
                $check_sql = "SELECT id FROM reviews WHERE tool_id = $tool_id AND user_id = $user_id";
                $check_result = $conn->query($check_sql);

                if ($check_result && $check_result->num_rows > 0) {
                    $response['message'] = __('review_already_submitted');
                } else {
                    // Insert new review
                    $insert_sql = "INSERT INTO reviews (tool_id, user_id, title, content, ip_address)
                                  VALUES ($tool_id, $user_id, '$title', '$content', '$ip_address')";

                    if ($conn->query($insert_sql)) {
                        $review_id = $conn->insert_id;
                        $response['success'] = true;
                        $response['message'] = __('review_added');
                        $response['review_id'] = $review_id;
                    } else {
                        $response['message'] = sprintf(__('review_add_error'), $conn->error);
                    }
                }
            } else {
                $response['message'] = __('login_required_for_review');
                // Add redirect info with proper handling of default language
                $response['redirect'] = ($current_language === $default_language) ? '/login' : '/' . $current_language . '/login';
            }
        } else {
            $response['message'] = __('tool_not_found');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get reviews for a tool
    $tool_id = isset($_GET['tool_id']) ? (int)$_GET['tool_id'] : 0;

    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else {
        // Get approved reviews for this tool
        $sql = "SELECT r.id, r.title, r.content, r.created_at,
                u.username, u.avatar
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.tool_id = $tool_id AND r.status = 'approved'
                ORDER BY r.created_at DESC";

        $result = $conn->query($sql);

        if ($result) {
            $reviews = [];
            while ($row = $result->fetch_assoc()) {
                // Format date
                $date = new DateTime($row['created_at']);
                $row['formatted_date'] = $date->format('d.m.Y');

                // Add default avatar if none
                if (empty($row['avatar'])) {
                    $row['avatar'] = 'images/default-avatar.png';
                }

                $reviews[] = $row;
            }

            $response['success'] = true;
            $response['reviews'] = $reviews;
            $response['count'] = count($reviews);
        } else {
            $response['message'] = sprintf(__('reviews_fetch_error'), $conn->error);
        }
    }
} else {
    $response['message'] = __('invalid_request_method');
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
