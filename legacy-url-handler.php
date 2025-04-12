<?php
/**
 * Legacy URL Handler
 *
 * This script handles redirections from old URL format (with GET parameters)
 * to new URL format (with language prefix in the path).
 *
 * It detects the user's preferred language and redirects to the appropriate
 * language-prefixed URL.
 */

// Load configuration
require_once 'includes/config.php';

// Determine the preferred language for the user
$lang_code = $current_language;

// Build the new URL with language prefix
$new_url = '';
$script_name = $_SERVER['SCRIPT_NAME'];
$request_uri = $_SERVER['REQUEST_URI'];
$query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

// Extract path without domain and query string
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove leading slash if it exists
$path = ltrim($path, '/');

// Extract the base name of the URL path
$script_base = basename($path);

// Handle empty path (root URL)
if (empty($path)) {
    $new_url = '/' . $lang_code . '/';
}
// Handle special cases based on path pattern
else if (preg_match('/^category\/([^\/]+)\/?$/', $path, $matches)) {
    $slug = $matches[1];
    $new_url = '/' . $lang_code . '/category/' . $slug;
}
else if (preg_match('/^tool\/([^\/]+)\/?$/', $path, $matches)) {
    $slug = $matches[1];
    $new_url = '/' . $lang_code . '/tool/' . $slug;
}
else if (preg_match('/^search\/([^\/]+)\/?$/', $path, $matches)) {
    $query = $matches[1];
    $new_url = '/' . $lang_code . '/search/' . $query;
}
else if ($path === 'categories' || $path === 'categories/') {
    $new_url = '/' . $lang_code . '/categories';
}
else if (preg_match('/^([^\/]+)\.php$/', $path, $matches)) {
    // Handle PHP files
    $file = $matches[1];
    $new_url = '/' . $lang_code . '/' . $file . '.php';
}
else {
    // For other paths, just add language prefix
    $new_url = '/' . $lang_code . '/' . $path;
}

// Add query parameters if they exist (except lang)
if (!empty($query_string)) {
    $query_params = [];
    parse_str($query_string, $query_params);
    unset($query_params['lang']); // Remove lang parameter

    if (!empty($query_params)) {
        $new_url .= '?' . http_build_query($query_params);
    }
}

// Redirect to the new URL format
if (!empty($new_url)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $new_url);
    exit;
}
?>
