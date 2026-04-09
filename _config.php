<?php
defined('ACCESS') or die('Access not allowed!');

// Database Config
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'isnguoir_user');
define('DB_PASS', 'isnguoir_pass');
define('DB_NAME', 'isnguoir_dtb');

// Create connection function
function connDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

?>