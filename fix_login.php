<?php
// fix_login.php - Reset user credentials without dropping the table
// IMPORTANT: Delete this file after fixing the login issues!

// Include config
require_once 'config/config.php';
require_once 'config/database.php';

// Turn on error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connect directly to database
try {
    // Create database connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    echo "<h2>Database Connection</h2>";
    echo "Database connection successful!<br><br>";
    
    // Check if users table exists
    echo "<h2>Updating User Credentials</h2>";
    
    // Create fresh password hashes
    $adminPassword = password_hash('password123', PASSWORD_DEFAULT);
    $staffPassword = password_hash('password123', PASSWORD_DEFAULT);
    
    echo "Admin password hash: " . $adminPassword . "<br>";
    echo "Staff password hash: " . $staffPassword . "<br><br>";
    
    // Get current users
    $stmt = $pdo->query("SELECT id, username FROM users");
    $existingUsers = $stmt->fetchAll();
    
    if (count($existingUsers) > 0) {
        echo "Found existing users - updating their passwords<br>";
        
        // Update admin user if exists
        $adminExists = false;
        $staffExists = false;
        
        foreach ($existingUsers as $user) {
            if ($user['username'] === 'admin') {
                $adminExists = true;
                $stmt = $pdo->prepare("UPDATE users SET password = ?, name = ?, role = ? WHERE username = ?");
                $stmt->execute([$adminPassword, 'Admin User', 'admin', 'admin']);
                echo "Admin user updated<br>";
            }
            
            if ($user['username'] === 'staff') {
                $staffExists = true; 
                $stmt = $pdo->prepare("UPDATE users SET password = ?, name = ?, role = ? WHERE username = ?");
                $stmt->execute([$staffPassword, 'Staff User', 'staff', 'staff']);
                echo "Staff user updated<br>";
            }
        }
        
        // Create admin if doesn't exist
        if (!$adminExists) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', $adminPassword, 'Admin User', 'admin']);
            echo "Admin user created<br>";
        }
        
        // Create staff if doesn't exist
        if (!$staffExists) {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['staff', $staffPassword, 'Staff User', 'staff']);
            echo "Staff user created<br>";
        }
    } else {
        echo "No existing users found - creating new users<br>";
        
        // Insert admin
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $adminPassword, 'Admin User', 'admin']);
        
        // Insert staff
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['staff', $staffPassword, 'Staff User', 'staff']);
        
        echo "Users created successfully!<br>";
    }
    
    echo "<br>User credentials updated successfully!<br><br>";
    
    // Display users for verification
    echo "<h2>Verifying Users</h2>";
    $stmt = $pdo->query("SELECT id, username, name, role FROM users");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Test login function
    echo "<h2>Test Login Form</h2>";
    echo "Use this form to test the login functionality:<br><br>";
    
    echo '<form method="post" style="max-width: 300px; margin-bottom: 20px;">';
    echo '<div style="margin-bottom: 10px;">';
    echo '<label for="username" style="display: block; margin-bottom: 5px;">Username:</label>';
    echo '<input type="text" id="username" name="username" value="admin" style="width: 100%; padding: 5px;">';
    echo '</div>';
    
    echo '<div style="margin-bottom: 10px;">';
    echo '<label for="password" style="display: block; margin-bottom: 5px;">Password:</label>';
    echo '<input type="password" id="password" name="password" value="password123" style="width: 100%; padding: 5px;">';
    echo '</div>';
    
    echo '<button type="submit" name="test_login" style="padding: 5px 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer;">Test Login</button>';
    echo '</form>';
    
    // Process test login
    if (isset($_POST['test_login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        echo "Testing login for: " . htmlspecialchars($username) . "<br>";
        
        // Check credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            echo "<div style='color: green; font-weight: bold; margin: 10px 0;'>Login Success!</div>";
            echo "User ID: " . $user['id'] . "<br>";
            echo "Name: " . $user['name'] . "<br>";
            echo "Role: " . $user['role'] . "<br><br>";
            
            echo "This means your credentials are correct. If the regular login page still doesn't work, the issue is in your login.php or auth.php files.";
        } else {
            echo "<div style='color: red; font-weight: bold; margin: 10px 0;'>Login Failed!</div>";
            if (!$user) {
                echo "User not found in database.<br>";
            } else {
                echo "Password verification failed.<br>";
            }
        }
    }
    
    echo "<br><hr><br>";
    echo "<h2>Next Steps</h2>";
    echo "1. If the test login above succeeded, you have correct user credentials in the database.<br>";
    echo "2. Use the credentials below with your regular login page:<br>";
    echo "   - Admin: username = 'admin', password = 'password123'<br>";
    echo "   - Staff: username = 'staff', password = 'password123'<br><br>";
    echo "3. If login still fails, check your login.php and auth.php files.<br>";
    echo "4. <strong>Important:</strong> Delete this file after fixing your login issues!<br>";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
?>