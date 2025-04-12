<?php
// Include configuration
require_once 'config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get email
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';

    // Validate email
    if (empty($email)) {
        $response['message'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
    } else {
        // Create subscribers table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS subscribers (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if ($conn->query($sql) === false) {
            $response['message'] = 'Failed to create subscribers table: ' . $conn->error;
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM subscribers WHERE email = '$email'";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                $response['message'] = 'You are already subscribed';
            } else {
                // Insert email
                $sql = "INSERT INTO subscribers (email) VALUES ('$email')";

                if ($conn->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = 'You have successfully subscribed to our newsletter';
                } else {
                    $response['message'] = 'Failed to subscribe: ' . $conn->error;
                }
            }
        }
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
