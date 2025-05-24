<!-- Updated layout in includes/header.php -->
<?php
// includes/header.php
// Header component for all pages

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include auth if not already included
require_once dirname(__DIR__) . '/includes/auth.php';

// Get current user data
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Main content container -->
    <div class="d-flex min-vh-100">
        <?php if(isLoggedIn()): ?>
            <?php include_once 'sidebar.php'; ?>
        <?php endif; ?>
        
        <main class="flex-grow-1 overflow-auto d-flex flex-column">
            <?php if(isLoggedIn()): ?>
            <!-- Header moved to main content area -->
            <header class="bg-white border-bottom sticky-top">
                <div class="container-fluid d-flex justify-content-between align-items-center p-3">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-link d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                            <i class="ri-menu-line"></i>
                        </button>
                        <h1 class="h5 mb-0"><?php echo isset($pageTitle) ? $pageTitle : APP_NAME; ?></h1>
                    </div>
                    <div class="d-flex align-items-center">
                        <?php if(isset($showNotifications) && $showNotifications): ?>
                        <button class="btn btn-outline-secondary btn-sm d-none d-sm-flex me-3">
                            <i class="ri-notification-3-line me-1"></i>
                            <span>Notifications</span>
                        </button>
                        <?php endif; ?>
                        <div class="dropdown">
                            <button class="btn d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px;">
                                    <?php 
                                    // Display user initials
                                    if($currentUser) {
                                        $initials = '';
                                        $nameParts = explode(' ', $currentUser['name']);
                                        foreach($nameParts as $part) {
                                            $initials .= substr($part, 0, 1);
                                        }
                                        echo htmlspecialchars(strtoupper($initials));
                                    }
                                    ?>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="dropdown-item-text">
                                    <div class="fw-medium"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></div>
                                    <div class="small text-muted"><?php echo ucfirst(htmlspecialchars($currentUser['role'] ?? '')); ?></div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/settings.php">Settings</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            <?php endif; ?>
            
            <div class="p-3 p-md-4 flex-grow-1">
                <!-- Display alert messages -->
                <?php if($error = getSessionMessage('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if($success = getSessionMessage('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if($info = getSessionMessage('info')): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($info); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Page content will go here -->
    
    <!-- Main content container -->
    <div class="d-flex min-vh-100">
        <?php if(isLoggedIn()): ?>
            <?php include_once 'sidebar.php'; ?>
        <?php endif; ?>
        
        <main class="flex-grow-1 p-3 overflow-auto">
            <!-- Display alert messages -->
            <?php if($error = getSessionMessage('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if($success = getSessionMessage('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if($info = getSessionMessage('info')): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($info); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>