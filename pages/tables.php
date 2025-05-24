<?php
// pages/tables.php
// Tables management page for the Cafe Management System

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Table.php';
require_once dirname(__DIR__) . '/classes/Order.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Require login to access this page
requireLogin();

// Initialize classes
$tableObj = new Table();
$orderObj = new Order();

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Create new table
    if ($action == 'create') {
        $name = trim($_POST['name']);
        $capacity = intval($_POST['capacity']);
        
        if (!empty($name) && $capacity > 0) {
            $tableData = [
                'name' => $name,
                'capacity' => $capacity,
                'status' => 'available'
            ];
            
            if ($tableObj->createTable($tableData)) {
                setSessionMessage('success', 'Table added successfully.');
            } else {
                setSessionMessage('error', 'Failed to add table.');
            }
        } else {
            setSessionMessage('error', 'Name and capacity are required.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Update table
    if ($action == 'update' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $capacity = intval($_POST['capacity']);
        
        if (!empty($name) && $capacity > 0) {
            $tableData = [
                'name' => $name,
                'capacity' => $capacity
            ];
            
            if ($tableObj->updateTable($id, $tableData)) {
                setSessionMessage('success', 'Table updated successfully.');
            } else {
                setSessionMessage('error', 'Failed to update table.');
            }
        } else {
            setSessionMessage('error', 'Name and capacity are required.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Update table status
    if ($action == 'updateStatus' && isset($_POST['id']) && isset($_POST['status'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        if ($tableObj->updateStatus($id, $status)) {
            setSessionMessage('success', 'Table status updated successfully.');
        } else {
            setSessionMessage('error', 'Failed to update table status.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Delete table
    if ($action == 'delete' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        if ($tableObj->deleteTable($id)) {
            setSessionMessage('success', 'Table removed successfully.');
        } else {
            setSessionMessage('error', 'Failed to remove table. It may have associated orders.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get all tables with order information
$tables = $tableObj->getTablesWithOrders();

// Set page title
$pageTitle = "Table Management";
$showNotifications = true;

// Include header
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
    <div>
        <h1 class="fs-2 fw-bold">Table Management</h1>
        <p class="text-muted">Manage cafe tables and seating</p>
    </div>
    <div class="mt-3 md:mt-0">
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addTableModal">
            <i class="ri-add-line"></i>
            <span>Add Table</span>
        </button>
    </div>
</div>

<!-- Search Box -->
<div class="mb-4">
    <div class="position-relative">
        <input type="text" id="searchTables" class="form-control ps-4" placeholder="Search tables...">
        <i class="ri-search-line position-absolute start-3 top-50 translate-middle-y text-muted"></i>
    </div>
</div>

<!-- Table Status Filters -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <button class="btn btn-outline-secondary btn-sm status-filter active" data-status="all">All Tables</button>
    <button class="btn btn-outline-success btn-sm status-filter" data-status="available">Available</button>
    <button class="btn btn-outline-primary btn-sm status-filter" data-status="occupied">Occupied</button>
    <button class="btn btn-outline-warning btn-sm status-filter" data-status="reserved">Reserved</button>
</div>

<!-- Tables Grid -->
<div class="row g-4" id="tablesGrid">
    <?php if (empty($tables)): ?>
    <div class="col-12">
        <div class="alert alert-info">No tables found. Add one to get started.</div>
    </div>
    <?php else: ?>
        <?php foreach ($tables as $table): ?>
        <div class="col-md-6 col-lg-3 table-card" data-status="<?php echo $table['status']; ?>">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center text-white me-3" style="width: 48px; height: 48px;">
                            <i class="ri-table-line fs-4"></i>
                        </div>
                        <div>
                            <h3 class="fw-medium fs-5 mb-0"><?php echo htmlspecialchars($table['name']); ?></h3>
                            <span class="badge <?php 
                                echo $table['status'] == 'available' ? 'bg-success' : 
                                     ($table['status'] == 'occupied' ? 'bg-primary' : 'bg-warning'); 
                                ?>">
                                <?php echo ucfirst($table['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-0">Capacity</p>
                            <p class="fs-5 mb-0"><?php echo $table['capacity']; ?> people</p>
                        </div>
                        
                        <?php if ($table['status'] == 'occupied' && isset($table['current_order_id'])): ?>
                        <div>
                            <p class="text-muted mb-0">Current Order</p>
                            <p class="mb-0">
                                <a href="<?php echo BASE_URL; ?>/pages/orders.php?action=view&id=<?php echo $table['current_order_id']; ?>" class="text-primary">
                                    <?php echo htmlspecialchars($table['current_order_number'] ?? 'N/A'); ?>
                                </a>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($table['status'] == 'available'): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/orders.php?action=create" class="btn btn-outline-primary w-100">New Order</a>
                        <?php elseif ($table['status'] == 'occupied' && isset($table['current_order_id'])): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/orders.php?action=view&id=<?php echo $table['current_order_id']; ?>" class="btn btn-outline-secondary w-100">View Order</a>
                        <?php endif; ?>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-2-fill"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item edit-table"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTableModal" 
                                            data-id="<?php echo $table['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($table['name']); ?>"
                                            data-capacity="<?php echo $table['capacity']; ?>">
                                        <i class="ri-pencil-line me-2"></i> Edit
                                    </button>
                                </li>
                                <?php if ($table['status'] != 'occupied'): ?>
                                <li>
                                    <button class="dropdown-item text-danger delete-table"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteTableModal" 
                                            data-id="<?php echo $table['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($table['name']); ?>">
                                        <i class="ri-delete-bin-line me-2"></i> Delete
                                    </button>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <input type="hidden" name="action" value="updateStatus">
                                        <input type="hidden" name="id" value="<?php echo $table['id']; ?>">
                                        <input type="hidden" name="status" value="available">
                                        <button type="submit" class="dropdown-item <?php echo $table['status'] == 'available' ? 'active' : ''; ?>">
                                            <i class="ri-checkbox-circle-line me-2 text-success"></i> Set Available
                                        </button>
                                    </form>
                                </li>
                                <li>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <input type="hidden" name="action" value="updateStatus">
                                        <input type="hidden" name="id" value="<?php echo $table['id']; ?>">
                                        <input type="hidden" name="status" value="reserved">
                                        <button type="submit" class="dropdown-item <?php echo $table['status'] == 'reserved' ? 'active' : ''; ?>">
                                            <i class="ri-timer-line me-2 text-warning"></i> Set Reserved
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Table Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-labelledby="addTableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTableModalLabel">Add New Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Table Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="4" required>
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

<!-- Edit Table Modal -->
<div class="modal fade" id="editTableModal" tabindex="-1" aria-labelledby="editTableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTableModalLabel">Edit Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-id">
                    
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Table Name</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="edit-capacity" name="capacity" min="1" required>
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

<!-- Delete Table Modal -->
<div class="modal fade" id="deleteTableModal" tabindex="-1" aria-labelledby="deleteTableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTableModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    
                    <p>Are you sure you want to delete <span id="delete-name" class="fw-bold"></span>?</p>
                    <p class="text-muted small">This action cannot be undone. Tables with associated orders cannot be deleted.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Add page-specific scripts
$extraScripts = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Handle search functionality
        const searchInput = document.getElementById("searchTables");
        const tableCards = document.querySelectorAll(".table-card");
        
        searchInput.addEventListener("keyup", function() {
            const searchTerm = this.value.toLowerCase();
            
            tableCards.forEach(function(card) {
                const tableName = card.querySelector(".fw-medium").textContent.toLowerCase();
                
                if (tableName.includes(searchTerm)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        });
        
        // Handle status filters
        const statusFilters = document.querySelectorAll(".status-filter");
        
        statusFilters.forEach(function(filter) {
            filter.addEventListener("click", function() {
                // Remove active class from all filters
                statusFilters.forEach(btn => btn.classList.remove("active"));
                
                // Add active class to clicked filter
                this.classList.add("active");
                
                const status = this.getAttribute("data-status");
                
                tableCards.forEach(function(card) {
                    if (status === "all" || card.getAttribute("data-status") === status) {
                        card.style.display = "";
                    } else {
                        card.style.display = "none";
                    }
                });
            });
        });
        
        // Handle edit table modal
        const editTableModal = document.getElementById("editTableModal");
        if (editTableModal) {
            editTableModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                const capacity = button.getAttribute("data-capacity");
                
                const idInput = this.querySelector("#edit-id");
                const nameInput = this.querySelector("#edit-name");
                const capacityInput = this.querySelector("#edit-capacity");
                
                idInput.value = id;
                nameInput.value = name;
                capacityInput.value = capacity;
            });
        }
        
        // Handle delete table modal
        const deleteTableModal = document.getElementById("deleteTableModal");
        if (deleteTableModal) {
            deleteTableModal.addEventListener("show.bs.modal", function(event) {
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