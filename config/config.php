<?php
// config/config.php
// Main configuration settings for the application

// Application title
define('APP_NAME', 'Cafe Management System');

// Basic site URL - update this for your environment
define('BASE_URL', '/cafe-management');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Timezone setting
date_default_timezone_set('Asia/Karachi');

// Error reporting settings (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default currency
define('CURRENCY', 'Rs.');

// Other global settings
define('ITEMS_PER_PAGE', 10);
?>