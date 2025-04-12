<?php
/**
 * Language Management and Translation Functions
 */

// Available languages
$available_languages = [
    'en' => [
        'name' => 'English',
        'native_name' => 'English',
        'locale' => 'en_US',
        'flag' => 'gb.png',
        'direction' => 'ltr',
    ],
    'pl' => [
        'name' => 'Polish',
        'native_name' => 'Polski',
        'locale' => 'pl_PL',
        'flag' => 'pl.png',
        'direction' => 'ltr',
    ],
    'es' => [
        'name' => 'Spanish',
        'native_name' => 'Español',
        'locale' => 'es_ES',
        'flag' => 'es.png',
        'direction' => 'ltr',
    ],
    'pt' => [
        'name' => 'Portuguese',
        'native_name' => 'Português',
        'locale' => 'pt_BR',
        'flag' => 'pt.png',
        'direction' => 'ltr',
    ],
    'ru' => [
        'name' => 'Russian',
        'native_name' => 'Русский',
        'locale' => 'ru_RU',
        'flag' => 'ru.png',
        'direction' => 'ltr',
    ],
];

// Default language
$default_language = 'en';

// Current language
$current_language = $default_language;

// Language detection function
function detect_language() {
    global $available_languages, $default_language;

    // Check if we're on the root URL path (/)
    $request_uri = $_SERVER['REQUEST_URI'];
    $is_root_path = (trim($request_uri, '/') === '');

    debug_log("Detecting language - Request URI: " . $request_uri);
    debug_log("GET params: " . json_encode($_GET));

    // Check the URI path for language code first
    $uri_parts = explode('/', trim($request_uri, '/'));

    // If the URI doesn't have a language prefix, force English
    // This handles paths like /tools, /categories etc.
    if (!empty($uri_parts[0]) && !array_key_exists($uri_parts[0], $available_languages)) {
        debug_log("No language prefix in URL, forcing English for: " . $request_uri);
        // Update session and cookie for consistency
        $_SESSION['lang'] = $default_language;
        setcookie('lang', $default_language, time() + (86400 * 30), '/');
        return $default_language;
    }

    // 1. Check URL parameter 'lang' from mod_rewrite - THIS IS CRITICAL
    if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
        $detected_lang = $_GET['lang'];
        debug_log("Language detected from URL param: " . $detected_lang);

        // English is special - ensure we properly handle it
        if ($detected_lang === 'en') {
            debug_log("English language detected from URL param");
        }

        // Save to session
        $_SESSION['lang'] = $detected_lang;
        // Save to cookie (30 days expiration)
        setcookie('lang', $detected_lang, time() + (86400 * 30), '/');
        return $detected_lang;
    }

    // 2. Check URL path for language code
    // Check if first part is a valid language code and ensure no multiple prefixes
    if (!empty($uri_parts[0]) && array_key_exists($uri_parts[0], $available_languages)) {
        $detected_lang = $uri_parts[0];
        debug_log("Language detected from URL path: " . $detected_lang);

        // Check if the next segment is also a language code (multiple prefixes)
        if (isset($uri_parts[1]) && array_key_exists($uri_parts[1], $available_languages)) {
            // Detected multiple prefix, prevent saving this in session
            debug_log("Multiple language prefixes detected, returning " . $detected_lang . " without saving");
            return $detected_lang;
        }

        // Save to session
        $_SESSION['lang'] = $detected_lang;
        // Save to cookie (30 days expiration)
        setcookie('lang', $detected_lang, time() + (86400 * 30), '/');
        return $detected_lang;
    }

    // 3. Check session
    if (isset($_SESSION['lang']) && array_key_exists($_SESSION['lang'], $available_languages)) {
        debug_log("Language detected from session: " . $_SESSION['lang']);
        return $_SESSION['lang'];
    }

    // 4. Check cookie
    if (isset($_COOKIE['lang']) && array_key_exists($_COOKIE['lang'], $available_languages)) {
        // Save to session for future page loads
        $_SESSION['lang'] = $_COOKIE['lang'];
        debug_log("Language detected from cookie: " . $_COOKIE['lang']);
        return $_COOKIE['lang'];
    }

    // 5. Check browser settings - but only for non-root paths
    if (!$is_root_path && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($browser_languages as $browser_language) {
            $browser_language = substr($browser_language, 0, 2);
            if (array_key_exists($browser_language, $available_languages)) {
                // Otherwise save to session and cookie
                $_SESSION['lang'] = $browser_language;
                setcookie('lang', $browser_language, time() + (86400 * 30), '/');
                debug_log("Language detected from browser: " . $browser_language);
                return $browser_language;
            }
        }
    }

    // 6. Default language if nothing matches
    debug_log("No language detected, using default: " . $default_language);
    return $default_language;
}

// Load language function
function load_language($language_code) {
    global $available_languages, $default_language, $lang;

    // Validate language code
    if (!array_key_exists($language_code, $available_languages)) {
        $language_code = $default_language;
    }

    // Path to language file
    $language_file = dirname(__DIR__) . '/languages/' . $language_code . '.php';

    // Load default language as fallback
    $default_language_file = dirname(__DIR__) . '/languages/' . $default_language . '.php';
    if (file_exists($default_language_file)) {
        include $default_language_file;
        debug_log('Loaded default language file: ' . $default_language_file);
    } else {
        // If default language file doesn't exist, create an empty array
        $lang = [];
        debug_log('Default language file not found: ' . $default_language_file);
    }

    // If requested language is not the default, override with translations
    if ($language_code != $default_language && file_exists($language_file)) {
        debug_log('Loading non-default language file: ' . $language_file);
        include $language_file;
    } else if ($language_code == $default_language) {
        debug_log('Using default language (English)');
        // Do not reload en.php as it has already been loaded above
    } else {
        debug_log('Requested language file not found: ' . $language_file);
    }

    return $lang;
}

// Translation function: __($key, ...$args)
function __($key, ...$args) {
    global $lang;

    if (isset($lang[$key])) {
        if (empty($args)) {
            return $lang[$key];
        } else {
            return vsprintf($lang[$key], $args);
        }
    }

    // Return key if translation is not found
    return $key;
}

// Get current text direction
function get_text_direction() {
    global $available_languages, $current_language, $lang;

    if (isset($lang['direction'])) {
        return $lang['direction'];
    }

    if (isset($available_languages[$current_language]['direction'])) {
        return $available_languages[$current_language]['direction'];
    }

    return 'ltr'; // Default to left-to-right
}

// Get language URL
function get_language_url($lang_code) {
    global $current_language, $available_languages, $default_language;

    debug_log("Creating language URL for: " . $lang_code . " (current: " . $current_language . ")");

    // Get current URL
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url_parts = parse_url($current_url);
    $path = isset($url_parts['path']) ? $url_parts['path'] : '/';

    // Build base URL
    $base_url = $url_parts['scheme'] . '://' . $url_parts['host'];
    if (isset($url_parts['port'])) {
        $base_url .= ':' . $url_parts['port'];
    }

    debug_log("Original path: " . $path);

    // Get pattern for all language codes
    $langs_pattern = implode('|', array_keys($available_languages));

    // Extract the clean path without any language prefix
    $path_without_lang = preg_replace("#^/($langs_pattern)(/.*)?$#", '$2', $path);
    $path_without_lang = empty($path_without_lang) ? '/' : $path_without_lang;

    debug_log("Path without language prefix: " . $path_without_lang);

    // Get query parameters except 'lang'
    $query_string = "";
    if (isset($url_parts['query'])) {
        parse_str($url_parts['query'], $query_params);
        unset($query_params['lang']);
        if (!empty($query_params)) {
            $query_string = '?' . http_build_query($query_params);
        }
    }

    // Build final URL
    if ($lang_code === 'en' || $lang_code === $default_language) {
        // For English, remove language prefix (no need for lang=en param)
        $new_url = $base_url . $path_without_lang . $query_string;
        debug_log("English URL constructed: " . $new_url);
        return $new_url;
    } else {
        // For other languages, add the language prefix
        $new_url = $base_url . '/' . $lang_code . $path_without_lang . $query_string;
        debug_log("Non-English URL constructed: " . $new_url);
        return $new_url;
    }
}

// Get canonical URL
function get_canonical_url() {
    global $current_language, $default_language;

    // Current URL components
    $request_uri = $_SERVER['REQUEST_URI'];

    // CRITICAL FIX: Check if we're on the root path (/)
    $is_root_path = (trim($request_uri, '/') === '');
    if ($is_root_path) {
        // For root path, always return the base URL without prefix
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        return $base_url . '/';
    }

    $uri_parts = explode('/', trim($request_uri, '/'));

    // Base URL
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

    // Check if URL has a language prefix
    if (!empty($uri_parts[0]) && array_key_exists($uri_parts[0], $GLOBALS['available_languages'])) {
        // Remove the language prefix and use the rest of the URL
        array_shift($uri_parts);
        $path_without_lang = !empty($uri_parts) ? '/' . implode('/', $uri_parts) : '/';
        return $base_url . $path_without_lang;
    }

    // If no language prefix, keep the URL as is (it's already in default language format)
    return $base_url . $request_uri;
}

// Get hreflang and canonical tags for SEO
function get_hreflang_tags($include_canonical = true) {
    global $available_languages, $current_language, $default_language;

    $hreflang_tags = '';

    foreach ($available_languages as $code => $language) {
        $url = get_language_url($code);
        $hreflang_tags .= '<link rel="alternate" hreflang="' . $language['locale'] . '" href="' . $url . '" />' . "\n";
    }

    // Add x-default hreflang (points to the default language URL - now without prefix)
    $default_url = get_language_url($default_language);
    $hreflang_tags .= '<link rel="alternate" hreflang="x-default" href="' . $default_url . '" />' . "\n";

    // Add canonical URL if requested
    if ($include_canonical) {
        $canonical_url = get_canonical_url();
        $hreflang_tags .= '<link rel="canonical" href="' . $canonical_url . '" />' . "\n";
    }

    return $hreflang_tags;
}

// Initialize language
$request_uri = $_SERVER['REQUEST_URI'];
$is_root_path = (trim($request_uri, '/') === '');

// For root path, FORCE English without any detection
if ($is_root_path) {
    debug_log('CRITICAL FIX: Root path detected - forcing English language, NO REDIRECTS');
    $current_language = $default_language;
    $lang = load_language($default_language);
    $text_direction = get_text_direction();
} else {
    // Normal language detection for non-root paths
    $current_language = detect_language();
    $lang = load_language($current_language);
    $text_direction = get_text_direction();
}

// Handle AJAX requests for translations
if (isset($_GET['get_translation']) && !empty($_GET['get_translation'])) {
    // Override language if passed in request
    if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
        $current_language = $_GET['lang'];
        $lang = load_language($current_language);
    }

    $translation_key = $_GET['get_translation'];
    header('Content-Type: text/plain; charset=UTF-8');
    echo __($translation_key);
    exit;
}
