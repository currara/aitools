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
debug_log('Rating request received', [
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
    'rating' => 0,
    'average_rating' => 0
];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get tool ID and rating
    $tool_id = isset($_POST['tool_id']) ? (int)$_POST['tool_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

    // Validate input
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } elseif ($rating < 1 || $rating > 5) {
        $response['message'] = __('rating_range_error');
    } else {
        // Check if tool exists
        $sql = "SELECT id FROM tools WHERE id = $tool_id";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // Get user information
            $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL;
            $ip_address = $_SERVER['REMOTE_ADDR'];

            // Check if user already rated this tool
            $check_sql = "SELECT id, rating FROM ratings WHERE tool_id = $tool_id AND ";
            if ($user_id) {
                $check_sql .= "user_id = $user_id";
            } else {
                $check_sql .= "ip_address = '$ip_address' AND user_id IS NULL";
            }

            $check_result = $conn->query($check_sql);

            if ($check_result && $check_result->num_rows > 0) {
                // User already rated, update the rating
                $existing_rating = $check_result->fetch_assoc();
                $rating_id = $existing_rating['id'];
                $old_rating = $existing_rating['rating'];

                $update_sql = "UPDATE ratings SET rating = $rating WHERE id = $rating_id";

                if ($conn->query($update_sql)) {
                    $response['success'] = true;
                    $response['message'] = __('rating_updated');
                    $response['rating'] = $rating;

                    // Update average tool rating
                    updateToolRating($tool_id, $conn);

                    // Get the updated average rating
                    $avg_sql = "SELECT rating FROM tools WHERE id = $tool_id";
                    $avg_result = $conn->query($avg_sql);

                    if ($avg_result && $avg_result->num_rows > 0) {
                        $avg_row = $avg_result->fetch_assoc();
                        $response['average_rating'] = $avg_row['rating'];
                    }
                } else {
                    $response['message'] = sprintf(__('rating_update_error'), $conn->error);
                }
            } else {
                // Insert new rating
                $insert_sql = "INSERT INTO ratings (tool_id, user_id, rating, ip_address) VALUES ($tool_id, ";
                $insert_sql .= $user_id ? "$user_id" : "NULL";
                $insert_sql .= ", $rating, '$ip_address')";

                if ($conn->query($insert_sql)) {
                    $response['success'] = true;
                    $response['message'] = __('rating_saved');
                    $response['rating'] = $rating;

                    // Update average tool rating
                    updateToolRating($tool_id, $conn);

                    // Get the updated average rating
                    $avg_sql = "SELECT rating FROM tools WHERE id = $tool_id";
                    $avg_result = $conn->query($avg_sql);

                    if ($avg_result && $avg_result->num_rows > 0) {
                        $avg_row = $avg_result->fetch_assoc();
                        $response['average_rating'] = $avg_row['rating'];
                    }
                } else {
                    $response['message'] = sprintf(__('rating_save_error'), $conn->error);
                }
            }

        } else {
            $response['message'] = __('tool_not_found');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get tool ID
    $tool_id = isset($_GET['tool_id']) ? (int)$_GET['tool_id'] : 0;
    $count_only = isset($_GET['count_only']) ? (bool)$_GET['count_only'] : false;

    // Validate tool ID
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else {
        // Count ratings for this tool
        $sql = "SELECT COUNT(*) as count FROM ratings WHERE tool_id = $tool_id";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response['success'] = true;
            $response['count'] = (int)$row['count'];

            if (!$count_only) {
                // Get user information
                $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL;
                $ip_address = $_SERVER['REMOTE_ADDR'];

                // Check if user already rated this tool
                $check_sql = "SELECT rating FROM ratings WHERE tool_id = $tool_id AND ";
                if ($user_id) {
                    $check_sql .= "user_id = $user_id";
                } else {
                    $check_sql .= "ip_address = '$ip_address' AND user_id IS NULL";
                }

                $check_result = $conn->query($check_sql);

                if ($check_result && $check_result->num_rows > 0) {
                    $existing_rating = $check_result->fetch_assoc();
                    $response['user_rating'] = (int)$existing_rating['rating'];
                    $response['has_rated'] = true;
                } else {
                    $response['has_rated'] = false;
                }
            }
        } else {
            $response['message'] = sprintf(__('rating_count_error'), $conn->error);
        }
    }
} else {
    $response['message'] = __('invalid_request_method');
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

/**
 * Update the average rating for a tool
 *
 * @param int $tool_id The tool ID
 * @param mysqli $conn The database connection
 * @return bool True on success, false on failure
 */
function updateToolRating($tool_id, $conn) {
    // Calculate new average rating
    $avg_sql = "SELECT AVG(rating) as avg_rating FROM ratings WHERE tool_id = $tool_id";
    $avg_result = $conn->query($avg_sql);

    if ($avg_result && $avg_result->num_rows > 0) {
        $avg_row = $avg_result->fetch_assoc();
        $avg_rating = round($avg_row['avg_rating'], 2);

        // Update tool's rating
        $update_tool_sql = "UPDATE tools SET rating = $avg_rating WHERE id = $tool_id";
        return $conn->query($update_tool_sql);
    }

    return false;
}
?>
