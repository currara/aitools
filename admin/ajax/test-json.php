<?php
/**
 * Prosty skrypt testujący zwracający zawsze prawidłowy JSON
 */

// Wyłącz wyświetlanie błędów na wyjściu
error_reporting(0);
ini_set('display_errors', 0);

// Ustaw nagłówek JSON
header('Content-Type: application/json');

// Zwróć prosty obiekt JSON
echo json_encode([
    'success' => true,
    'message' => 'To jest testowa odpowiedź JSON',
    'timestamp' => time(),
    'date' => date('Y-m-d H:i:s')
]);
