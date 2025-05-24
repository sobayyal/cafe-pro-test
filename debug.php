<?php
// debug.php - Temporary file for debugging login

require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';

// Turn on error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a user object
$user = new User();

// Test username lookup
$username = 'admin';
$userFound = $user->getUserByUsername($username);

echo "<h2>User lookup test:</h2>";
echo "Looking up username: $username<br>";
if ($userFound) {
    echo "User found!<br>";
    echo "User data: <pre>";
    // Don't display the password hash for security
    $tempUser = $userFound;
    $tempUser['password'] = isset($tempUser['password']) ? "[PASSWORD HASH EXISTS]" : "NULL";
    print_r($tempUser);
    echo "</pre>";
} else {
    echo "User not found!<br>";
}

// Test password verification
echo "<h2>Password verification test:</h2>";
$password = 'admin123';
if (isset($userFound['password'])) {
    echo "Password field exists<br>";
    $passwordVerified = password_verify($password, $userFound['password']);
    echo "Password verification result: " . ($passwordVerified ? "SUCCESS" : "FAILED") . "<br>";
} else {
    echo "Password field is missing!<br>";
}

// Check Database connection 
echo "<h2>Database connection test:</h2>";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "Database connection successful!<br>";
    
    // Test a direct query
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $directUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Direct database query result: <pre>";
    $directUser['password'] = isset($directUser['password']) ? "[PASSWORD HASH EXISTS]" : "NULL";
    print_r($directUser);
    echo "</pre>";
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "<br>";
}