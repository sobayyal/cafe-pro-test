<?php
// logout.php
// Logout script for the Cafe Management System

// Include config file
require_once 'config/config.php';

// Include auth file
require_once 'includes/auth.php';

// Log out the user
logout();

// Set success message
setSessionMessage('success', 'You have been logged out successfully.');

// Redirect to login page
header("Location: " . BASE_URL . "/login.php");
exit;
?>