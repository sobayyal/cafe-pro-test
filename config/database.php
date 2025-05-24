<?php
// config/database.php
// Database configuration for the Cafe Management System

// Database credentials
define('DB_HOST', 'localhost');      // Database host (usually localhost)
define('DB_USER', 'root');           // Database username
define('DB_PASS', '');               // Database password
define('DB_NAME', 'cafe_management'); // Database name

/**
 * Get PDO database connection
 * @return PDO PDO database connection object
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log the error or display a user-friendly message
        die("Database Connection Failed: " . $e->getMessage());
    }
}