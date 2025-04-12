<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define BASE_PATH constant - base path for the application
define('BASE_PATH', '.');

// Include language management
require_once 'language.php';

// Site configuration
define('SITE_TITLE', __('site_title'));
define('SITE_DESCRIPTION', __('site_description'));
define('SITE_URL', 'http://localhost/aitools');
define('ITEMS_PER_PAGE', 12);

// Include necessary files
require_once 'db_config.php';
require_once 'functions.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// Debug function - wyłączone debugowanie domyślnie
function debug_log($message, $dump = null) {
    // Uncomment to enable debugging
    // $debug_file = __DIR__ . '/../debug.log';
    // $timestamp = date('[Y-m-d H:i:s]');
    // $log_message = $timestamp . ' ' . $message;

    // if ($dump !== null) {
    //     $log_message .= PHP_EOL . print_r($dump, true);
    // }

    // file_put_contents($debug_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Time zone
date_default_timezone_set('UTC');

// Load sample data for the first run
function load_sample_data() {
    global $conn;

    // Check if we already have categories
    $result = $conn->query("SELECT COUNT(*) as count FROM categories");
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        return; // Data already exists
    }

    // Insert sample categories
    $categories = [
        ['name' => 'Text & Writing', 'slug' => 'text-writing', 'description' => 'AI tools for text generation and writing assistance', 'icon' => 'text-icon.svg'],
        ['name' => 'Image', 'slug' => 'image', 'description' => 'AI tools for image generation and editing', 'icon' => 'image-icon.svg'],
        ['name' => 'Video', 'slug' => 'video', 'description' => 'AI tools for video creation and editing', 'icon' => 'video-icon.svg'],
        ['name' => 'Code & IT', 'slug' => 'code-it', 'description' => 'AI tools for coding assistance and IT', 'icon' => 'code-icon.svg'],
        ['name' => 'Voice', 'slug' => 'voice', 'description' => 'AI tools for voice and audio processing', 'icon' => 'voice-icon.svg'],
        ['name' => 'Business', 'slug' => 'business', 'description' => 'AI tools for business applications', 'icon' => 'business-icon.svg'],
        ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'AI tools for marketing', 'icon' => 'marketing-icon.svg'],
        ['name' => 'AI Detector', 'slug' => 'ai-detector', 'description' => 'Tools to detect AI-generated content', 'icon' => 'detector-icon.svg'],
        ['name' => 'Chatbot', 'slug' => 'chatbot', 'description' => 'AI chatbots and conversational agents', 'icon' => 'chatbot-icon.svg']
    ];

    foreach ($categories as $category) {
        $sql = "INSERT INTO categories (name, slug, description, icon) VALUES (
            '" . $conn->real_escape_string($category['name']) . "',
            '" . $conn->real_escape_string($category['slug']) . "',
            '" . $conn->real_escape_string($category['description']) . "',
            '" . $conn->real_escape_string($category['icon']) . "'
        )";

        $conn->query($sql);
    }

    // Get category IDs
    $result = $conn->query("SELECT id, slug FROM categories");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[$row['slug']] = $row['id'];
    }

    // Insert sample tools
    $tools = [
        [
            'name' => 'ChatGPT',
            'slug' => 'chatgpt',
            'description' => 'ChatGPT is an AI-powered chatbot developed by OpenAI, based on the GPT architecture.',
            'logo' => 'chatgpt-logo.png',
            'website_url' => 'https://chat.openai.com/',
            'category_id' => $categories['chatbot'],
            'featured' => true,
            'new_launch' => false,
            'rating' => 4.8,
            'upvotes' => 1250
        ],
        [
            'name' => 'DALL-E',
            'slug' => 'dall-e',
            'description' => 'DALL-E is an AI system that can create realistic images and art from a description in natural language.',
            'logo' => 'dalle-logo.png',
            'website_url' => 'https://openai.com/dall-e/',
            'category_id' => $categories['image'],
            'featured' => true,
            'new_launch' => false,
            'rating' => 4.7,
            'upvotes' => 980
        ],
        [
            'name' => 'GitHub Copilot',
            'slug' => 'github-copilot',
            'description' => 'GitHub Copilot is an AI pair programmer that offers autocomplete-style suggestions as you code.',
            'logo' => 'copilot-logo.png',
            'website_url' => 'https://github.com/features/copilot',
            'category_id' => $categories['code-it'],
            'featured' => true,
            'new_launch' => false,
            'rating' => 4.6,
            'upvotes' => 850
        ],
        [
            'name' => 'Midjourney',
            'slug' => 'midjourney',
            'description' => 'Midjourney is an AI program that creates images from textual descriptions.',
            'logo' => 'midjourney-logo.png',
            'website_url' => 'https://www.midjourney.com/',
            'category_id' => $categories['image'],
            'featured' => true,
            'new_launch' => false,
            'rating' => 4.7,
            'upvotes' => 1020
        ],
        [
            'name' => 'Jasper',
            'slug' => 'jasper',
            'description' => 'Jasper is an AI content generator that helps you create marketing copy, social media posts, and more.',
            'logo' => 'jasper-logo.png',
            'website_url' => 'https://www.jasper.ai/',
            'category_id' => $categories['text-writing'],
            'featured' => false,
            'new_launch' => true,
            'rating' => 4.5,
            'upvotes' => 720
        ],
        [
            'name' => 'Grammarly',
            'slug' => 'grammarly',
            'description' => 'Grammarly is an AI writing assistant that helps with grammar, clarity, engagement, and delivery.',
            'logo' => 'grammarly-logo.png',
            'website_url' => 'https://www.grammarly.com/',
            'category_id' => $categories['text-writing'],
            'featured' => true,
            'new_launch' => false,
            'rating' => 4.8,
            'upvotes' => 1540
        ],
        [
            'name' => 'Lensa',
            'slug' => 'lensa',
            'description' => 'Lensa is an AI-powered photo editing app for enhancing and stylizing photos.',
            'logo' => 'lensa-logo.png',
            'website_url' => 'https://prisma-ai.com/lensa',
            'category_id' => $categories['image'],
            'featured' => false,
            'new_launch' => true,
            'rating' => 4.3,
            'upvotes' => 630
        ],
        [
            'name' => 'Descript',
            'slug' => 'descript',
            'description' => 'Descript is an all-in-one audio/video editing software that includes AI transcription and editing features.',
            'logo' => 'descript-logo.png',
            'website_url' => 'https://www.descript.com/',
            'category_id' => $categories['video'],
            'featured' => false,
            'new_launch' => true,
            'rating' => 4.6,
            'upvotes' => 810
        ],
        [
            'name' => 'Krisp',
            'slug' => 'krisp',
            'description' => 'Krisp is an AI-powered app that removes background noise and echo from meetings.',
            'logo' => 'krisp-logo.png',
            'website_url' => 'https://krisp.ai/',
            'category_id' => $categories['voice'],
            'featured' => false,
            'new_launch' => false,
            'rating' => 4.5,
            'upvotes' => 750
        ],
        [
            'name' => 'Copy.ai',
            'slug' => 'copy-ai',
            'description' => 'Copy.ai is an AI-powered copywriter that generates high-quality copy for various marketing needs.',
            'logo' => 'copy-ai-logo.png',
            'website_url' => 'https://www.copy.ai/',
            'category_id' => $categories['marketing'],
            'featured' => false,
            'new_launch' => false,
            'rating' => 4.4,
            'upvotes' => 680
        ]
    ];

    foreach ($tools as $tool) {
        $sql = "INSERT INTO tools (name, slug, description, logo, website_url, category_id, featured, new_launch, rating, upvotes) VALUES (
            '" . $conn->real_escape_string($tool['name']) . "',
            '" . $conn->real_escape_string($tool['slug']) . "',
            '" . $conn->real_escape_string($tool['description']) . "',
            '" . $conn->real_escape_string($tool['logo']) . "',
            '" . $conn->real_escape_string($tool['website_url']) . "',
            " . (int)$tool['category_id'] . ",
            " . ($tool['featured'] ? 'TRUE' : 'FALSE') . ",
            " . ($tool['new_launch'] ? 'TRUE' : 'FALSE') . ",
            " . (float)$tool['rating'] . ",
            " . (int)$tool['upvotes'] . "
        )";

        $conn->query($sql);
    }

    // Create admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, email, role) VALUES (
        'admin',
        '$admin_password',
        'admin@toolify.local',
        'admin'
    )";

    $conn->query($sql);
}

// Load sample data on first run
load_sample_data();
?>
