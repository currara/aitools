<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Nie masz uprawnień do przeglądania tych danych.'
    ]);
    exit;
}

// Check if user_id is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Brak identyfikatora użytkownika.'
    ]);
    exit;
}

$user_id = (int)$_GET['user_id'];

// Check if user exists
$user = get_user($user_id);
if (!$user) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Użytkownik nie został znaleziony.'
    ]);
    exit;
}

// Get user activity log
$activities = get_activity_log(50, 0, $user_id);
$formatted_activities = [];

foreach ($activities as $activity) {
    $formatted_activity = [
        'date' => date('d.m.Y H:i', strtotime($activity['created_at'])),
        'action' => '',
        'details' => isset($activity['details']) ? htmlspecialchars($activity['details']) : ''
    ];

    // Format action
    $action = isset($activity['action']) ? $activity['action'] : '';
    $entity_type = isset($activity['entity_type']) ? $activity['entity_type'] : '';
    $entity_id = isset($activity['entity_id']) ? $activity['entity_id'] : '';

    if ($action === 'login') {
        $formatted_activity['action'] = 'Logowanie';
    } else if ($action === 'logout') {
        $formatted_activity['action'] = 'Wylogowanie';
    } else if ($action === 'page_view') {
        $formatted_activity['action'] = 'Wyświetlenie strony';
    } else if ($action === 'create') {
        if ($entity_type === 'tool') {
            $formatted_activity['action'] = 'Dodanie narzędzia';
            if ($entity_id) {
                $formatted_activity['details'] .= ' (ID: ' . $entity_id . ')';
            }
        } else if ($entity_type === 'category') {
            $formatted_activity['action'] = 'Dodanie kategorii';
            if ($entity_id) {
                $formatted_activity['details'] .= ' (ID: ' . $entity_id . ')';
            }
        } else {
            $formatted_activity['action'] = 'Utworzenie ' . htmlspecialchars($entity_type);
        }
    } else if ($action === 'update') {
        if ($entity_type === 'tool') {
            $formatted_activity['action'] = 'Aktualizacja narzędzia';
            if ($entity_id) {
                $formatted_activity['details'] .= ' (ID: ' . $entity_id . ')';
            }
        } else if ($entity_type === 'category') {
            $formatted_activity['action'] = 'Aktualizacja kategorii';
            if ($entity_id) {
                $formatted_activity['details'] .= ' (ID: ' . $entity_id . ')';
            }
        } else if ($entity_type === 'settings') {
            $formatted_activity['action'] = 'Aktualizacja ustawień';
        } else {
            $formatted_activity['action'] = 'Aktualizacja ' . htmlspecialchars($entity_type);
        }
    } else if ($action === 'delete') {
        if ($entity_type === 'tool') {
            $formatted_activity['action'] = 'Usunięcie narzędzia';
            if ($entity_id) {
                $formatted_activity['details'] .= ' (ID: ' . $entity_id . ')';
            }
        } else if ($entity_type === 'category') {
            $formatted_activity['action'] = 'Usunięcie kategorii';
            if ($entity_id) {
                $formatted_activity['details'] .= ' (ID: ' . $entity_id . ')';
            }
        } else if ($entity_type === 'user') {
            $formatted_activity['action'] = 'Usunięcie użytkownika';
            if ($entity_id) {
                $formatted_activity['details'] .= ' (ID: ' . $entity_id . ')';
            }
        } else {
            $formatted_activity['action'] = 'Usunięcie ' . htmlspecialchars($entity_type);
        }
    } else {
        $formatted_activity['action'] = htmlspecialchars($action . ($entity_type ? ' - ' . $entity_type : ''));
    }

    $formatted_activities[] = $formatted_activity;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Historia aktywności użytkownika ' . htmlspecialchars($user['username']),
    'activities' => $formatted_activities
]);
