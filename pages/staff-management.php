<?php
// pages/staff-management.php
// Staff management page for the Cafe Management System

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Staff.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Require admin privileges to access this page
requireAdmin();

// Initialize classes
$staffObj = new Staff();

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Create new staff
    if ($action == 'create') {
        $name = trim($_POST['name']);
        $active = isset($_POST['active']) ? 1 : 0;
        
        if (!empty($name)) {
            $staffData = [
                'name' => $name,
                'active' => $active
            ];
            
            if ($staffObj->createStaff($staffData)) {
                setSessionMessage('success', 'Staff member added successfully.');
            } else {
                setSessionMessage('error', 'Failed to add staff member.');
            }
        } else {
            setSessionMessage('error', 'Name cannot be empty.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Update staff
    if ($action == 'update' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $active = isset($_POST['active']) ? 1 : 0;
        
        if (!empty($name)) {
            $staffData = [
                'name' => $name,
                'active' => $active
            ];
            
            if ($staffObj->updateStaff($id, $staffData)) {
                setSessionMessage('success', 'Staff member updated successfully.');
            } else {
                setSessionMessage('error', 'Failed to update staff member.');
            }
        } else {
            setSessionMessage('error', 'Name cannot be empty.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Delete staff
    if ($action == 'delete' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        if ($staffObj->deleteStaff($id)) {
            setSessionMessage('success', 'Staff member removed successfully.');
        } else {
            setSessionMessage('error', 'Failed to remove staff member. They may have associated orders.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get staff list
$staffList = $staffObj->getAllStaff();

// Set page title
$pageTitle = "Staff Management";
$showNotifications = true;

// Include header
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
    <div>
        <h1 class="fs-2 fw-bold">Staff Management</h1>
        <p class="text-muted">Manage waiters and service staff</p>
    </div>
    <div class="mt-3 md:mt-0">
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="ri-user-add-line"></i>
            <span>Add Staff</span>
        </button>
    </div>
</div>

<!-- Search Box -->
<div class="mb-4">
    <div class="position-relative">
        <input type="text" id="searchStaff" class="form-control ps-4" placeholder="Search staff...">
        <i class="ri-search-line position-absolute start-3 top-50 translate-middle-y text-muted"></i>
    </div>
</div>

<!-- Staff Grid -->
<div class="row g-4" id="staffGrid">
    <?php if (empty($staffList)): ?>
    <div class="col-12">
        <div class="alert alert-info">No staff members found. Add one to get started.</div>
    </div>
    <?php else: ?>
        <?php foreach ($staffList as $staff): ?>
        <div class="col-md-6 col-lg-3 staff-card">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-3" style="width: 40px; height: 40px;">
                            <?php 
                            // Display staff initials
                            $initials = '';
                            $nameParts = explode(' ', $staff['name']);
                            foreach($nameParts as $part) {
                                $initials .= substr($part, 0, 1);
                            }
                            echo htmlspecialchars(strtoupper($initials));
                            ?>
                        </div>
                        <div>
                            <h3 class="fw-medium mb-0"><?php echo htmlspecialchars($staff['name']); ?></h3>
                            <span class="badge <?php echo $staff['active'] ? 'bg-success' : 'bg-secondary'; ?> mt-1">
                                <?php echo $staff['active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editStaffModal" 
                                data-id="<?php echo $staff['id']; ?>"
                                data-name="<?php echo htmlspecialchars($staff['name']); ?>"
                                data-active="<?php echo $staff['active']; ?>">
                            <i class="ri-pencil-line"></i>
                            Edit
                        </button>
                        <button class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteStaffModal" 
                                data-id="<?php echo $staff['id']; ?>"
                                data-name="<?php echo htmlspecialchars($staff['name']); ?>">
                            <i class="ri-delete-bin-line"></i>
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStaffModalLabel">Add New Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                        <label class="form-check-label" for="active">Active Status</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-id">
                    
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit-active" name="active">
                        <label class="form-check-label" for="edit-active">Active Status</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Staff Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteStaffModalLabel">Confirm Removal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    
                    <p>Are you sure you want to remove <span id="delete-name" class="fw-bold"></span>?</p>
                    <p class="text-muted small">If this staff member has associated orders, they will be marked as inactive instead of being removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Remove</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Add page-specific scripts
$extraScripts = '
<script>
    // Handle search functionality
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById("searchStaff");
        const staffCards = document.querySelectorAll(".staff-card");
        
        searchInput.addEventListener("keyup", function() {
            const searchTerm = this.value.toLowerCase();
            
            staffCards.forEach(function(card) {
                const staffName = card.querySelector(".fw-medium").textContent.toLowerCase();
                
                if (staffName.includes(searchTerm)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        });
        
        // Handle edit modal data
        const editStaffModal = document.getElementById("editStaffModal");
        if (editStaffModal) {
            editStaffModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                const active = button.getAttribute("data-active") === "1";
                
                const idInput = this.querySelector("#edit-id");
                const nameInput = this.querySelector("#edit-name");
                const activeInput = this.querySelector("#edit-active");
                
                idInput.value = id;
                nameInput.value = name;
                activeInput.checked = active;
            });
        }
        
        // Handle delete modal data
        const deleteStaffModal = document.getElementById("deleteStaffModal");
        if (deleteStaffModal) {
            deleteStaffModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                
                const idInput = this.querySelector("#delete-id");
                const nameSpan = this.querySelector("#delete-name");
                
                idInput.value = id;
                nameSpan.textContent = name;
            });
        }
    });
</script>
';

// Include footer
include dirname(__DIR__) . '/includes/footer.php';
?>