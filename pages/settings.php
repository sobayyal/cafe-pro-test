<?php
// pages/settings.php
// Settings page for the Cafe Management System

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/User.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Require login to access this page
requireLogin();

// Initialize database
$db = Database::getInstance();
$userObj = new User();

// Get current user
$currentUser = getCurrentUser();

// Get cafe settings
$cafeSettings = $db->selectOne("SELECT * FROM cafe_settings WHERE id = 1");
if (!$cafeSettings) {
    $cafeSettings = [
        'cafe_name' => 'Cafe Management System',
        'cafe_address' => '',
        'cafe_phone' => '',
        'receipt_header' => 'Thank you for visiting our cafe!',
        'receipt_footer' => 'Please come again!',
        'tax_rate' => 0.10
    ];
}

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'updateProfile') {
        // Update user profile
        $name = trim($_POST['name']);
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        $updateData = [];
        
        // Update name if provided
        if (!empty($name)) {
            $updateData['name'] = $name;
        }
        
        // Update password if provided
        if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
            if ($newPassword !== $confirmPassword) {
                setSessionMessage('error', 'New password and confirmation do not match.');
            } else {
                $result = $userObj->changePassword($currentUser['id'], $currentPassword, $newPassword);
                
                if ($result) {
                    setSessionMessage('success', 'Password updated successfully.');
                } else {
                    setSessionMessage('error', 'Current password is incorrect.');
                }
            }
        } else if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
            // If any password field is filled but not all are filled
            setSessionMessage('error', 'All password fields are required to change password.');
        }
        
        // Update user data if there's anything to update
        if (!empty($updateData)) {
            $result = $userObj->updateUser($currentUser['id'], $updateData);
            
            if ($result) {
                setSessionMessage('success', 'Profile updated successfully.');
            } else {
                setSessionMessage('error', 'Failed to update profile.');
            }
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=profile");
        exit;
    } elseif ($action === 'updateCafe' && isAdmin()) {
        // Update cafe settings - requires admin access
        $cafeName = trim($_POST['cafe_name']);
        $cafeAddress = trim($_POST['cafe_address']);
        $cafePhone = trim($_POST['cafe_phone']);
        $receiptHeader = trim($_POST['receipt_header']);
        $receiptFooter = trim($_POST['receipt_footer']);
        $taxRate = floatval($_POST['tax_rate']) / 100; // Convert from percentage to decimal
        
        $cafeData = [
            'cafe_name' => $cafeName,
            'cafe_address' => $cafeAddress,
            'cafe_phone' => $cafePhone,
            'receipt_header' => $receiptHeader,
            'receipt_footer' => $receiptFooter,
            'tax_rate' => $taxRate
        ];
        
        // Check if settings record exists
        if ($db->selectOne("SELECT id FROM cafe_settings WHERE id = 1")) {
            $result = $db->update('cafe_settings', $cafeData, 'id = ?', [1]);
        } else {
            $cafeData['id'] = 1;
            $result = $db->insert('cafe_settings', $cafeData);
        }
        
        if ($result) {
            setSessionMessage('success', 'Cafe settings updated successfully.');
        } else {
            setSessionMessage('error', 'Failed to update cafe settings.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=cafe");
        exit;
    }
}

// Get active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Set page title
$pageTitle = "Settings";
$showNotifications = true;

// Include header
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
    <div>
        <h1 class="fs-2 fw-bold">Settings</h1>
        <p class="text-muted">Configure your account and system preferences</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-3" style="width: 50px; height: 50px;">
                        <?php 
                        // Display user initials
                        $initials = '';
                        $nameParts = explode(' ', $currentUser['name']);
                        foreach($nameParts as $part) {
                            $initials .= substr($part, 0, 1);
                        }
                        echo htmlspecialchars(strtoupper($initials));
                        ?>
                    </div>
                    <div>
                        <h3 class="fw-medium mb-0"><?php echo htmlspecialchars($currentUser['name']); ?></h3>
                        <span class="badge <?php echo $currentUser['role'] === 'admin' ? 'bg-primary' : 'bg-secondary'; ?> mt-1">
                            <?php echo ucfirst($currentUser['role']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="nav flex-column nav-pills">
                    <a class="nav-link <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" href="?tab=profile">
                        <i class="ri-user-settings-line me-2"></i> Profile Settings
                    </a>
                    <?php if (isAdmin()): ?>
                    <a class="nav-link <?php echo $activeTab === 'cafe' ? 'active' : ''; ?>" href="?tab=cafe">
                        <i class="ri-store-2-line me-2"></i> Cafe Settings
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <?php if ($activeTab === 'profile'): ?>
        <!-- Profile Settings Tab -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Profile Settings</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="action" value="updateProfile">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" readonly disabled>
                        <div class="form-text">Username cannot be changed</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>">
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3">Change Password</h6>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php elseif ($activeTab === 'cafe' && isAdmin()): ?>
        <!-- Cafe Settings Tab (Admin only) -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Cafe Settings</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="action" value="updateCafe">
                    
                    <div class="mb-3">
                        <label for="cafe_name" class="form-label">Cafe Name</label>
                        <input type="text" class="form-control" id="cafe_name" name="cafe_name" value="<?php echo htmlspecialchars($cafeSettings['cafe_name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="cafe_address" class="form-label">Address</label>
                        <textarea class="form-control" id="cafe_address" name="cafe_address" rows="2"><?php echo htmlspecialchars($cafeSettings['cafe_address']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cafe_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="cafe_phone" name="cafe_phone" value="<?php echo htmlspecialchars($cafeSettings['cafe_phone']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($cafeSettings['tax_rate'] * 100); ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3">Receipt Customization</h6>
                    
                    <div class="mb-3">
                        <label for="receipt_header" class="form-label">Receipt Header</label>
                        <textarea class="form-control" id="receipt_header" name="receipt_header" rows="2"><?php echo htmlspecialchars($cafeSettings['receipt_header']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="receipt_footer" class="form-label">Receipt Footer</label>
                        <textarea class="form-control" id="receipt_footer" name="receipt_footer" rows="2"><?php echo htmlspecialchars($cafeSettings['receipt_footer']); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-2"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include dirname(__DIR__) . '/includes/footer.php';
?>