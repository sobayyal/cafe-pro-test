<?php
// includes/auth.php
// Authentication functions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and user class if not already included
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/User.php';

/**
 * Check if a user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user's ID
 * @return int|null The user ID or null if not logged in
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get the current user's data
 * @return array|null The user data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user = new User();
    return $user->getUser($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin
 * @return bool True if the user is an admin, false otherwise
 */
function isAdmin() {
    $currentUser = getCurrentUser();
    return $currentUser && $currentUser['role'] === 'admin';
}

/**
 * Authenticate a user
 * @param string $username The username
 * @param string $password The password
 * @return bool True on success, false on failure
 */
function login($username, $password) {
    try {
        // Get database connection directly
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Perform a direct query to ensure we get all fields
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user exists and has a password
        if (!$user || empty($user['password'])) {
            return false;
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log out the current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Require login to access a page, redirects to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Store the requested URL for redirection after login
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        
        // Redirect to login page
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Require admin privileges to access a page, redirects to dashboard if not admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        // Set error message
        $_SESSION['error'] = 'You do not have permission to access that page.';
        
        // Redirect to dashboard
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Check if there are any session messages and return them
 * @param string $type The message type (error, success, info)
 * @return string|null The message or null if none
 */
function getSessionMessage($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    
    return null;
}

/**
 * Set a session message
 * @param string $type The message type (error, success, info)
 * @param string $message The message
 */
function setSessionMessage($type, $message) {
    $_SESSION[$type] = $message;
}