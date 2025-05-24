<?php
// login.php
// Login page for the Cafe Management System

// Include config file
require_once 'config/config.php';

// Include auth
require_once 'includes/auth.php';

// Initialize variables
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check input errors before attempting to login
    if (empty($username_err) && empty($password_err)) {
        // Attempt to login
        if (login($username, $password)) {
            // Check if there's a redirect URL
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
            unset($_SESSION['redirect_after_login']);
            
            header("Location: " . $redirect);
            exit;
        } else {
            $login_err = "Invalid username or password.";
        }
    }
}

// Set page title
$pageTitle = "Login";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-page">
        <div class="card login-card">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="login-logo">
                        <i class="ri-cup-line"></i>
                    </div>
                    <h4 class="card-title"><?php echo APP_NAME; ?></h4>
                    <p class="text-muted">Sign in to your account to continue</p>
                </div>
                
                <?php if(!empty($login_err)): ?>
                <div class="alert alert-danger"><?php echo $login_err; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" placeholder="Enter your username">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Enter your password">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Sign In</button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-muted small">
                        Demo login - 
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="document.getElementById('username').value='admin'; document.getElementById('password').value='password123';">
                            Admin User
                        </button>
                        or
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="document.getElementById('username').value='staff'; document.getElementById('password').value='password123';">
                            Staff User
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>