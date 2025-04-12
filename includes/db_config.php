<?php
// Include database credentials from separate file
require_once __DIR__ . '/db_credentials.php';

// Attempt to connect to MySQL database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if($mysqli->query($sql) === false){
    die("ERROR: Could not create database. " . $mysqli->error);
}

// Select the database
$mysqli->select_db(DB_NAME);

// Create necessary tables if they don't exist
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    count INT DEFAULT 0,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create categories table. " . $mysqli->error);
}

$sql = "CREATE TABLE IF NOT EXISTS category_translations (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY category_language (category_id, language_code),
    UNIQUE KEY category_slug_language (slug, language_code)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create category_translations table. " . $mysqli->error);
}

$sql = "CREATE TABLE IF NOT EXISTS tools (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    website_url VARCHAR(255),
    category_id INT,
    featured BOOLEAN DEFAULT FALSE,
    new_launch BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    upvotes INT DEFAULT 0,
    views INT DEFAULT 0,
    pricing_type ENUM('free', 'freemium', 'paid', 'contact') DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create tools table. " . $mysqli->error);
}

// Dodaj kolumnę image_type do tabeli tools, jeśli nie istnieje
$sql = "SHOW COLUMNS FROM tools LIKE 'image_type'";
$result = $mysqli->query($sql);
if ($result && $result->num_rows == 0) {
    $sql = "ALTER TABLE tools ADD COLUMN image_type VARCHAR(20) DEFAULT 'favicon' AFTER logo";
    if($mysqli->query($sql) === false){
        error_log("ERROR: Could not add image_type column to tools table. " . $mysqli->error);
    } else {
        error_log("INFO: Successfully added image_type column to tools table.");
    }
}

// Dodaj kolumnę screenshot do tabeli tools, jeśli nie istnieje
$sql = "SHOW COLUMNS FROM tools LIKE 'screenshot'";
$result = $mysqli->query($sql);
if ($result && $result->num_rows == 0) {
    $sql = "ALTER TABLE tools ADD COLUMN screenshot VARCHAR(255) AFTER logo";
    if($mysqli->query($sql) === false){
        error_log("ERROR: Could not add screenshot column to tools table. " . $mysqli->error);
    } else {
        error_log("INFO: Successfully added screenshot column to tools table.");
    }
}

$sql = "CREATE TABLE IF NOT EXISTS tool_translations (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    UNIQUE KEY tool_language (tool_id, language_code),
    UNIQUE KEY tool_slug_language (slug, language_code)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create tool_translations table. " . $mysqli->error);
}

$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'editor', 'user') DEFAULT 'user',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    avatar VARCHAR(255),
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create users table. " . $mysqli->error);
}

$sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'text',
    is_translatable BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create settings table. " . $mysqli->error);
}

$sql = "CREATE TABLE IF NOT EXISTS setting_translations (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    setting_id INT NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (setting_id) REFERENCES settings(id) ON DELETE CASCADE,
    UNIQUE KEY setting_language (setting_id, language_code)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create setting_translations table. " . $mysqli->error);
}

$sql = "CREATE TABLE IF NOT EXISTS activity_log (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create activity_log table. " . $mysqli->error);
}

// Create ratings table
$sql = "CREATE TABLE IF NOT EXISTS ratings (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_tool_rating (tool_id, user_id),
    UNIQUE KEY unique_ip_tool_rating (tool_id, ip_address, user_id)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create ratings table. " . $mysqli->error);
}

// Create reviews table
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT,
    title VARCHAR(255),
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create reviews table. " . $mysqli->error);
}

// Create upvotes table
$sql = "CREATE TABLE IF NOT EXISTS upvotes (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tool_upvote (tool_id, user_id),
    UNIQUE KEY unique_ip_tool_upvote (tool_id, ip_address, user_id)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create upvotes table. " . $mysqli->error);
}

// Create favorites table
$sql = "CREATE TABLE IF NOT EXISTS favorites (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tool_favorite (tool_id, user_id)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create favorites table. " . $mysqli->error);
}

// Create tags table
$sql = "CREATE TABLE IF NOT EXISTS tags (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_tag_name (name),
    UNIQUE KEY unique_tag_slug (slug)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create tags table. " . $mysqli->error);
}

// Create tool_tags table for many-to-many relationship
$sql = "CREATE TABLE IF NOT EXISTS tool_tags (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tool_tag (tool_id, tag_id)
)";
if($mysqli->query($sql) === false){
    die("ERROR: Could not create tool_tags table. " . $mysqli->error);
}

// Global connection variable
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if($conn === false){
    die("ERROR: Could not connect. " . $conn->connect_error);
}
?>
