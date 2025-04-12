<?php
// Include configuration
require_once 'config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'tags' => []
];

// Check if request is POST (for adding/removing tags)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get tool ID and tag information
    $tool_id = isset($_POST['tool_id']) ? (int)$_POST['tool_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $tag_name = isset($_POST['tag_name']) ? trim($_POST['tag_name']) : '';
    $tag_id = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;

    // Validate tool ID
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else {
        // Check if tool exists
        $sql = "SELECT id FROM tools WHERE id = $tool_id";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // Handle different actions
            if ($action === 'add' && !empty($tag_name)) {
                // Add tag to tool
                if (add_tag_to_tool($tool_id, $tag_name)) {
                    $response['success'] = true;
                    $response['message'] = __('tag_added');
                    // Get updated tags
                    $response['tags'] = get_tool_tags($tool_id);
                } else {
                    $response['message'] = __('tag_add_error');
                }
            } elseif ($action === 'remove' && $tag_id > 0) {
                // Remove tag from tool
                if (remove_tag_from_tool($tool_id, $tag_id)) {
                    $response['success'] = true;
                    $response['message'] = __('tag_removed');
                    // Get updated tags
                    $response['tags'] = get_tool_tags($tool_id);
                } else {
                    $response['message'] = __('tag_remove_error');
                }
            } else {
                $response['message'] = __('invalid_action');
            }
        } else {
            $response['message'] = __('tool_not_found');
        }
    }
}
// Check if request is GET (for retrieving tags)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get tool ID
    $tool_id = isset($_GET['tool_id']) ? (int)$_GET['tool_id'] : 0;

    // Validate tool ID
    if ($tool_id <= 0) {
        $response['message'] = __('invalid_tool_id');
    } else {
        // Get tags for the tool
        $response['tags'] = get_tool_tags($tool_id);
        $response['success'] = true;
    }
} else {
    $response['message'] = __('invalid_request_method');
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
