<?php
// pages/orders.php
// Orders management page for the Cafe Management System

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Order.php';
require_once dirname(__DIR__) . '/classes/Table.php';
require_once dirname(__DIR__) . '/classes/Staff.php';
require_once dirname(__DIR__) . '/classes/MenuItem.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Require login to access this page
requireLogin();

// Initialize classes
$orderObj = new Order();
$tableObj = new Table();
$staffObj = new Staff();
$menuItemObj = new MenuItem();

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Update order status
        if ($_POST['action'] === 'updateStatus' && isset($_POST['id']) && isset($_POST['status'])) {
            $id = $_POST['id'];
            $status = $_POST['status'];
            
            if ($orderObj->updateOrder($id, ['status' => $status])) {
                // If the status is "completed", set the table as available
                if ($status === 'completed') {
                    $order = $orderObj->getOrder($id);
                    if ($order && isset($order['table_id'])) {
                        $tableObj->updateStatus($order['table_id'], 'available');
                    }
                }
                
                setSessionMessage('success', 'Order status updated successfully.');
            } else {
                setSessionMessage('error', 'Failed to update order status.');
            }
            
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?action=view&id=$id");
            exit;
        }
        
        // Process payment
        if ($_POST['action'] === 'processPayment' && isset($_POST['id'])) {
            $id = $_POST['id'];
            $paymentMethod = $_POST['payment_method'];
            $tip = isset($_POST['tip']) ? floatval($_POST['tip']) : 0;
            
            // Get the current order
            $order = $orderObj->getOrder($id);
            
            if ($order) {
                // Calculate new total with tip
                $newTotal = $order['subtotal'] + $order['tax'] + $tip;
                
                // Update order with payment info
                $updateData = [
                    'payment_method' => $paymentMethod,
                    'tip' => $tip,
                    'total' => $newTotal,
                    'status' => 'completed', // Automatically mark as completed when payment is processed
                    'paid' => 1
                ];
                
                if ($orderObj->updateOrder($id, $updateData)) {
                    // Free up the table
                    if ($order['table_id']) {
                        $tableObj->updateStatus($order['table_id'], 'available');
                    }
                    
                    setSessionMessage('success', 'Payment processed successfully.');
                } else {
                    setSessionMessage('error', 'Failed to process payment.');
                }
            } else {
                setSessionMessage('error', 'Order not found.');
            }
            
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?action=view&id=$id");
            exit;
        }
        
        // Delete order
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = $_POST['id'];
            
            if ($orderObj->deleteOrder($id)) {
                setSessionMessage('success', 'Order deleted successfully.');
                
                // Redirect to orders list
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                setSessionMessage('error', 'Failed to delete order.');
                
                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?action=view&id=$id");
                exit;
            }
        }
        
        // Create a new order
        if ($_POST['action'] === 'create') {
            $tableId = isset($_POST['table_id']) ? intval($_POST['table_id']) : 0;
            $staffId = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
            $items = isset($_POST['items']) ? $_POST['items'] : [];
            
            if ($tableId > 0 && $staffId > 0 && !empty($items)) {
                // Generate order ID
                $orderId = $orderObj->generateOrderId();
                
                // Calculate totals
                $subtotal = 0;
                $orderItems = [];
                
                foreach ($items as $item) {
                    $menuItemId = $item['menu_item_id'];
                    $quantity = $item['quantity'];
                    $notes = $item['notes'] ?? '';
                    
                    // Get menu item details
                    $menuItem = $menuItemObj->getMenuItem($menuItemId);
                    
                    if ($menuItem) {
                        $price = $menuItem['price'];
                        $itemSubtotal = $price * $quantity;
                        $subtotal += $itemSubtotal;
                        
                        $orderItems[] = [
                            'menu_item_id' => $menuItemId,
                            'quantity' => $quantity,
                            'price' => $price,
                            'notes' => $notes,
                            'subtotal' => $itemSubtotal
                        ];
                    }
                }
                
                // Calculate tax and total
                $taxRate = 0.08; // 8% tax
                $tax = $subtotal * $taxRate;
                $total = $subtotal + $tax;
                
                // Prepare order data
                $orderData = [
                    'order_id' => $orderId,
                    'table_id' => $tableId,
                    'staff_id' => $staffId,
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'payment_method' => 'cash', // Default to cash
                    'paid' => 0 // Not paid yet
                ];
                
                // Create the order
                $newOrderId = $orderObj->createOrder($orderData, $orderItems);
                
                if ($newOrderId) {
                    // Update table status to 'occupied'
                    $tableObj->updateStatus($tableId, 'occupied');
                    
                    setSessionMessage('success', 'Order created successfully.');
                    
                    // Redirect to the order view
                    header("Location: " . $_SERVER['PHP_SELF'] . "?action=view&id=$newOrderId");
                    exit;
                } else {
                    setSessionMessage('error', 'Failed to create order.');
                }
            } else {
                setSessionMessage('error', 'Please select a table, staff member, and add at least one item.');
            }
            
            // Redirect to avoid form resubmission (back to create page)
            header("Location: " . $_SERVER['PHP_SELF'] . "?action=create");
            exit;
        }
    }
}

// Set page title based on action
$pageTitle = "Orders";
if ($action === 'create') {
    $pageTitle = "Create Order";
} elseif ($action === 'view' && isset($_GET['id'])) {
    $pageTitle = "Order Details";
}

$showNotifications = true;

// Include header
include dirname(__DIR__) . '/includes/header.php';

// Display the appropriate view based on the action
if ($action === 'list') {
    // Get all orders
    $orders = $orderObj->getAllOrders();
    
    // Display orders list
    ?>
    <div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
        <div>
            <h1 class="fs-2 fw-bold">Orders</h1>
            <p class="text-muted">View and manage all cafe orders</p>
        </div>
        <div class="mt-3 md:mt-0">
            <a href="?action=create" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="ri-add-line"></i>
                <span>New Order</span>
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="position-relative">
                <input type="text" id="searchOrders" class="form-control ps-4" placeholder="Search orders...">
                <i class="ri-search-line position-absolute start-3 top-50 translate-middle-y text-muted"></i>
            </div>
        </div>
        <div class="col-md-4">
            <select id="statusFilter" class="form-select">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="preparing">Preparing</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="ordersTable">
                <thead class="bg-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Table</th>
                        <th>Server</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No orders found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr class="order-row" data-status="<?php echo $order['status']; ?>">
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo date('M d, h:i a', strtotime($order['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($order['table_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['staff_name']); ?></td>
                            <td class="fw-medium"><?php echo CURRENCY . ' ' . number_format($order['total'], 2); ?></td>
                            <td>
                                <?php
                                $statusClass = '';
                                $statusText = ucfirst(str_replace('_', ' ', $order['status']));
                                
                                switch ($order['status']) {
                                    case 'completed':
                                        $statusClass = 'badge-completed';
                                        break;
                                    case 'in_progress':
                                        $statusClass = 'badge-in-progress';
                                        break;
                                    case 'preparing':
                                        $statusClass = 'badge-preparing';
                                        break;
                                    default:
                                        $statusClass = 'badge-pending';
                                }
                                ?>
                                <span class="badge-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <?php if (isset($order['paid']) && $order['paid'] == 1): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="?action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="ri-eye-line me-1"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php
    // Add script for search and filter functionality
    $extraScripts = '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("searchOrders");
            const statusFilter = document.getElementById("statusFilter");
            const orderRows = document.querySelectorAll(".order-row");
            
            function filterOrders() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedStatus = statusFilter.value;
                
                orderRows.forEach(function(row) {
                    const orderData = [
                        row.cells[0].textContent, // Order ID
                        row.cells[1].textContent, // Date
                        row.cells[2].textContent, // Table
                        row.cells[3].textContent  // Server
                    ].join(" ").toLowerCase();
                    
                    const status = row.getAttribute("data-status");
                    
                    const matchesSearch = orderData.includes(searchTerm);
                    const matchesStatus = selectedStatus === "all" || status === selectedStatus;
                    
                    if (matchesSearch && matchesStatus) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            }
            
            if (searchInput) {
                searchInput.addEventListener("keyup", filterOrders);
            }
            
            if (statusFilter) {
                statusFilter.addEventListener("change", filterOrders);
            }
        });
    </script>
    ';
} elseif ($action === 'view' && isset($_GET['id'])) {
    // Get order details
    $orderId = $_GET['id'];
    $order = $orderObj->getOrderWithDetails($orderId);
    
    if (!$order) {
        setSessionMessage('error', 'Order not found.');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Calculate if payment is complete
    $isPaid = isset($order['paid']) && $order['paid'] == 1;
    
    // Display order details
    ?>
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary btn-sm">
                <i class="ri-arrow-left-line"></i> Back to Orders
            </a>
            <h1 class="fs-4 fw-bold mb-0 ms-2">Order <?php echo htmlspecialchars($order['order_id']); ?></h1>
            
            <?php if (!$isPaid): ?>
            <span class="badge bg-warning text-dark ms-2">Unpaid</span>
            <?php else: ?>
            <span class="badge bg-success ms-2">Paid</span>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted text-uppercase fs-6 mb-2">Order Details</h5>
                        <div class="mb-2">
                            <span class="fw-bold">Table:</span> <?php echo htmlspecialchars($order['table']['name']); ?>
                        </div>
                        <div class="mb-2">
                            <span class="fw-bold">Server:</span> <?php echo htmlspecialchars($order['staff']['name']); ?>
                        </div>
                        <div class="mb-2">
                            <span class="fw-bold">Date:</span> <?php echo date('F d, Y - H:i', strtotime($order['created_at'])); ?>
                        </div>
                        <div class="mb-2">
                            <span class="fw-bold">Status:</span>
                            <form id="updateStatusForm" class="d-inline-block" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?action=view&id=" . $order['id']); ?>">
                                <input type="hidden" name="action" value="updateStatus">
                                <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                <select name="status" id="orderStatus" class="form-select form-select-sm d-inline-block w-auto ms-2" onchange="document.getElementById('updateStatusForm').submit();">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-muted text-uppercase fs-6 mb-2">Payment Info</h5>
                        <?php if ($isPaid): ?>
                        <div class="mb-2">
                            <span class="fw-bold">Payment Status:</span> 
                            <span class="badge bg-success">Paid</span>
                        </div>
                        <div class="mb-2">
                            <span class="fw-bold">Payment Method:</span> 
                            <?php
                            $paymentMethod = $order['payment_method'];
                            echo $paymentMethod === 'credit_card' ? 'Credit Card' : 
                                 ($paymentMethod === 'cash' ? 'Cash' : ucfirst($paymentMethod));
                            ?>
                        </div>
                        <?php else: ?>
                        <div class="mb-2">
                            <span class="fw-bold">Payment Status:</span> 
                            <span class="badge bg-warning text-dark">Unpaid</span>
                        </div>
                        <div class="mb-2">
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#processPaymentModal">
                                <i class="ri-bank-card-line me-1"></i> Process Payment
                            </button>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="fw-bold">Receipt:</span>
                            <a href="javascript:void(0);" onclick="printReceipt(<?php echo $order['id']; ?>)" class="ms-2">
                                <i class="ri-printer-line me-1"></i> Print Receipt
                            </a>
                        </div>
                    </div>
                </div>
                
                <h5 class="fw-medium border-bottom pb-2 mb-3">Order Items</h5>
                <div class="mb-4">
                    <?php foreach ($order['items'] as $item): ?>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="mb-0 fw-medium">
                                <?php echo $item['quantity']; ?> × <?php echo htmlspecialchars($item['menu_item_name']); ?>
                            </p>
                            <?php if (!empty($item['notes'])): ?>
                            <p class="small text-muted mb-0"><?php echo htmlspecialchars($item['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <p class="fw-medium mb-0"><?php echo CURRENCY . ' ' . number_format($item['subtotal'], 2); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="border-top pt-3 mb-4">
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-medium"><?php echo CURRENCY . ' ' . number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Tax</span>
                        <span class="fw-medium"><?php echo CURRENCY . ' ' . number_format($order['tax'], 2); ?></span>
                    </div>
                    <?php if ($order['tip'] > 0): ?>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Tip</span>
                        <span class="fw-medium"><?php echo CURRENCY . ' ' . number_format($order['tip'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between py-2 fs-5 fw-semibold">
                        <span>Total</span>
                        <span><?php echo CURRENCY . ' ' . number_format($order['total'], 2); ?></span>
                    </div>
                </div>
                
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary" onclick="printReceipt(<?php echo $order['id']; ?>)">
                        <i class="ri-printer-line me-2"></i>
                        Print Receipt
                    </button>
                    
                    <?php if (!$isPaid): ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#processPaymentModal">
                        <i class="ri-bank-card-line me-2"></i>
                        Process Payment
                    </button>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal">
                        <i class="ri-delete-bin-line me-2"></i>
                        Delete Order
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Process Payment Modal -->
    <div class="modal fade" id="processPaymentModal" tabindex="-1" aria-labelledby="processPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="processPaymentModalLabel">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?action=view&id=" . $order['id']); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="processPayment">
                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="mobile_payment">Mobile Payment</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tip" class="form-label">Tip Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                                <input type="number" class="form-control" id="tip" name="tip" min="0" step="0.01" value="0">
                            </div>
                        </div>
                        
                        <div class="mb-0">
                            <p class="mb-1">Payment Summary:</p>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?php echo CURRENCY . ' ' . number_format($order['subtotal'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Tax:</span>
                                <span><?php echo CURRENCY . ' ' . number_format($order['tax'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Tip:</span>
                                <span id="tipDisplay"><?php echo CURRENCY . ' 0.00'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold mt-1 pt-1 border-top">
                                <span>Total:</span>
                                <span id="totalWithTip"><?php echo CURRENCY . ' ' . number_format($order['subtotal'] + $order['tax'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Complete Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Order Modal -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteOrderModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                        
                        <p>Are you sure you want to delete order <strong><?php echo htmlspecialchars($order['order_id']); ?></strong>?</p>
                        <p class="text-danger small">This action cannot be undone.</p>
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
    // Add script for receipt printing and payment processing
    $extraScripts = '
    <script>
        // Print receipt function
        function printReceipt(orderId) {
            // Open receipt in a new window
            const receiptWindow = window.open("' . BASE_URL . '/pages/print-receipt.php?id=" + orderId, "_blank");
            
            // Focus on the new window
            if (receiptWindow) {
                receiptWindow.focus();
            } else {
                alert("Pop-up blocked. Please allow pop-ups for this site to print receipts.");
            }
        }
        
        // Update payment total when tip changes
        document.addEventListener("DOMContentLoaded", function() {
            const tipInput = document.getElementById("tip");
            const tipDisplay = document.getElementById("tipDisplay");
            const totalWithTip = document.getElementById("totalWithTip");
            const subtotal = ' . $order['subtotal'] . ';
            const tax = ' . $order['tax'] . ';
            
            if (tipInput && tipDisplay && totalWithTip) {
                tipInput.addEventListener("input", function() {
                    const tipAmount = parseFloat(this.value) || 0;
                    const total = subtotal + tax + tipAmount;
                    
                    tipDisplay.textContent = "' . CURRENCY . ' " + tipAmount.toFixed(2);
                    totalWithTip.textContent = "' . CURRENCY . ' " + total.toFixed(2);
                });
                
                // Initialize quick tip buttons if needed
                const quickTipButtons = document.querySelectorAll(".quick-tip-btn");
                quickTipButtons.forEach(function(btn) {
                    btn.addEventListener("click", function() {
                        const tipPercentage = parseFloat(this.getAttribute("data-percentage")) || 0;
                        const tipAmount = (subtotal * (tipPercentage / 100)).toFixed(2);
                        
                        tipInput.value = tipAmount;
                        tipInput.dispatchEvent(new Event("input"));
                    });
                });
            }
        });
    </script>
    ';
} elseif ($action === 'create') {
    // Get available tables, staff, and menu items
    $tables = $tableObj->getAvailableTables();
    $staffMembers = $staffObj->getActiveStaff();
    $menuItems = $menuItemObj->getActiveMenuItems();
    $categories = $menuItemObj->getAllCategories();
    
    // Organize menu items by category
    $menuItemsByCategory = [];
    foreach ($categories as $category) {
        $menuItemsByCategory[$category['id']] = [
            'name' => $category['name'],
            'items' => []
        ];
    }
    
    foreach ($menuItems as $item) {
        if (isset($menuItemsByCategory[$item['category_id']])) {
            $menuItemsByCategory[$item['category_id']]['items'][] = $item;
        }
    }
    
    // Display create order form
    ?>
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary btn-sm">
                <i class="ri-arrow-left-line"></i> Back to Orders
            </a>
            <h1 class="fs-4 fw-bold mb-0 ms-2">Create New Order</h1>
        </div>
        
        <div class="row g-4">
            <!-- Menu Items Selection -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-semibold mb-0">Menu Items</h5>
                    <div class="position-relative">
                        <input type="text" id="searchMenuItems" class="form-control form-control-sm ps-4" placeholder="Search menu...">
                        <i class="ri-search-line position-absolute start-3 top-50 translate-middle-y text-muted"></i>
                    </div>
                </div>
                
                <!-- Category tabs -->
                <ul class="nav nav-tabs mb-3" id="menuCategoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-tab-pane" type="button" role="tab" aria-controls="all-tab-pane" aria-selected="true">All items</button>
                    </li>
                    <?php foreach ($categories as $category): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="category-<?php echo $category['id']; ?>-tab" data-bs-toggle="tab" data-bs-target="#category-<?php echo $category['id']; ?>-tab-pane" type="button" role="tab" aria-controls="category-<?php echo $category['id']; ?>-tab-pane" aria-selected="false"><?php echo htmlspecialchars($category['name']); ?></button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="tab-content" id="menuCategoryTabsContent">
                    <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab" tabindex="0">
                        <div class="row g-3 menu-items-container">
                            <?php foreach ($menuItems as $item): ?>
                            <div class="col-md-6 col-lg-4 menu-item" data-category="<?php echo $item['category_id']; ?>">
                                <div class="card h-100 menu-item-card cursor-pointer" onclick="addItemToOrder(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <p class="card-text text-muted small mb-2" style="min-height: 2em;"><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></p>
                                                <div class="text-primary fw-medium"><?php echo CURRENCY . ' ' . number_format($item['price'], 2); ?></div>
                                            </div>
                                            <?php
                                            $iconColorClass = '';
                                            switch ($item['category_id'] % 4) {
                                                case 0:
                                                    $iconColorClass = 'text-primary';
                                                    break;
                                                case 1:
                                                    $iconColorClass = 'text-secondary';
                                                    break;
                                                case 2:
                                                    $iconColorClass = 'text-accent';
                                                    break;
                                                case 3:
                                                    $iconColorClass = 'text-warning';
                                                    break;
                                            }
                                            ?>
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="<?php echo htmlspecialchars($item['icon']); ?> <?php echo $iconColorClass; ?>"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php foreach ($menuItemsByCategory as $categoryId => $category): ?>
                    <div class="tab-pane fade" id="category-<?php echo $categoryId; ?>-tab-pane" role="tabpanel" aria-labelledby="category-<?php echo $categoryId; ?>-tab" tabindex="0">
                        <div class="row g-3">
                            <?php if (empty($category['items'])): ?>
                            <div class="col-12 text-center py-4 text-muted">
                                No items available in this category
                            </div>
                            <?php else: ?>
                                <?php foreach ($category['items'] as $item): ?>
                                <div class="col-md-6 col-lg-4 menu-item" data-category="<?php echo $item['category_id']; ?>">
                                    <div class="card h-100 menu-item-card cursor-pointer" onclick="addItemToOrder(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <p class="card-text text-muted small mb-2" style="min-height: 2em;"><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></p>
                                                    <div class="text-primary fw-medium"><?php echo CURRENCY . ' ' . number_format($item['price'], 2); ?></div>
                                                </div>
                                                <?php
                                                $iconColorClass = '';
                                                switch ($item['category_id'] % 4) {
                                                    case 0:
                                                        $iconColorClass = 'text-primary';
                                                        break;
                                                    case 1:
                                                        $iconColorClass = 'text-secondary';
                                                        break;
                                                    case 2:
                                                        $iconColorClass = 'text-accent';
                                                        break;
                                                    case 3:
                                                        $iconColorClass = 'text-warning';
                                                        break;
                                                }
                                                ?>
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <i class="<?php echo htmlspecialchars($item['icon']); ?> <?php echo $iconColorClass; ?>"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 fw-semibold">Current Order</h5>
                    </div>
                    <div class="card-body p-3">
                        <form id="orderForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="table_id" class="form-label">Table Number</label>
                                <select class="form-select" id="table_id" name="table_id" required>
                                    <option value="">Select table</option>
                                    <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo $table['id']; ?>"><?php echo htmlspecialchars($table['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="staff_id" class="form-label">Server</label>
                                <select class="form-select" id="staff_id" name="staff_id" required>
                                    <option value="">Assign server</option>
                                    <?php foreach ($staffMembers as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <hr>
                            
                            <h6 class="mb-2">Order Items</h6>
                            <div id="orderItemsContainer" class="mb-3">
                                <div id="emptyOrderMessage" class="text-center text-muted py-4 border border-dashed rounded-3">
                                    No items added yet
                                </div>
                                <div id="orderItemsList" class="d-none">
                                    <!-- Order items will be added here dynamically -->
                                </div>
                            </div>
                            
                            <div id="orderSummary" class="border-top pt-3 mt-4 d-none">
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted">Subtotal</span>
                                    <span id="subtotalValue" class="fw-medium">Rs. 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted">Tax (8%)</span>
                                    <span id="taxValue" class="fw-medium">Rs. 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between py-2 fs-5 fw-semibold">
                                    <span>Total</span>
                                    <span id="totalValue">Rs. 0.00</span>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-3">
                                <button type="submit" id="saveOrderBtn" class="btn btn-primary" disabled>
                                    <i class="ri-save-line me-2"></i>
                                    Save Order
                                </button>
                                <button type="button" id="directPaymentBtn" class="btn btn-success" disabled onclick="openPaymentScreen()">
                                    <i class="ri-bank-card-line me-2"></i>
                                    Create & Process Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Item Notes Modal -->
    <div class="modal fade" id="itemNotesModal" tabindex="-1" aria-labelledby="itemNotesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemNotesModalLabel">Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="noteItemId">
                    <div class="mb-3">
                        <label for="itemNotes" class="form-label">Special Instructions</label>
                        <textarea class="form-control" id="itemNotes" rows="3" placeholder="Add special instructions here..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveItemNoteBtn">Save Note</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Direct Payment Modal -->
    <div class="modal fade" id="directPaymentModal" tabindex="-1" aria-labelledby="directPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="directPaymentModalLabel">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="direct_payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="direct_payment_method">
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="mobile_payment">Mobile Payment</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="direct_tip" class="form-label">Tip Amount</label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo CURRENCY; ?></span>
                            <input type="number" class="form-control" id="direct_tip" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-1">Quick Tip:</p>
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-outline-secondary quick-tip-btn" data-percentage="0">No Tip</button>
                            <button type="button" class="btn btn-outline-secondary quick-tip-btn" data-percentage="10">10%</button>
                            <button type="button" class="btn btn-outline-secondary quick-tip-btn" data-percentage="15">15%</button>
                            <button type="button" class="btn btn-outline-secondary quick-tip-btn" data-percentage="20">20%</button>
                        </div>
                    </div>
                    
                    <div class="mb-0">
                        <p class="mb-1">Payment Summary:</p>
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span id="directSubtotalValue">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tax:</span>
                            <span id="directTaxValue">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tip:</span>
                            <span id="directTipDisplay">Rs. 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold mt-1 pt-1 border-top">
                            <span>Total:</span>
                            <span id="directTotalWithTip">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createAndPayBtn">Complete Payment</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Add script for order creation and payment processing
    $extraScripts = '
    <script>
        // Order items
        let orderItems = [];
        
        // Format currency
        function formatCurrency(amount) {
            return "' . CURRENCY . ' " + parseFloat(amount).toFixed(2);
        }
        
        // Calculate order totals
        function calculateTotals() {
            let subtotal = 0;
            
            orderItems.forEach(item => {
                subtotal += item.subtotal;
            });
            
            const taxRate = 0.08; // 8%
            const tax = subtotal * taxRate;
            const total = subtotal + tax;
            
            document.getElementById("subtotalValue").textContent = formatCurrency(subtotal);
            document.getElementById("taxValue").textContent = formatCurrency(tax);
            document.getElementById("totalValue").textContent = formatCurrency(total);
            
            // Show order summary
            if (orderItems.length > 0) {
                document.getElementById("orderSummary").classList.remove("d-none");
                document.getElementById("directPaymentBtn").disabled = false;
            } else {
                document.getElementById("orderSummary").classList.add("d-none");
                document.getElementById("directPaymentBtn").disabled = true;
            }
            
            return { subtotal, tax, total };
        }
        
        // Add item to order
        function addItemToOrder(item) {
            // Check if item already exists in order
            const existingItemIndex = orderItems.findIndex(orderItem => 
                orderItem.menu_item_id === item.id
            );
            
            if (existingItemIndex >= 0) {
                // Increment quantity if item already exists
                orderItems[existingItemIndex].quantity += 1;
                orderItems[existingItemIndex].subtotal = orderItems[existingItemIndex].price * orderItems[existingItemIndex].quantity;
            } else {
                // Add new item
                orderItems.push({
                    menu_item_id: item.id,
                    name: item.name,
                    price: parseFloat(item.price),
                    quantity: 1,
                    notes: "",
                    subtotal: parseFloat(item.price)
                });
            }
            
            // Update UI
            updateOrderItemsUI();
            
            // Show notification
            showToast(`Added ${item.name} to order`);
        }
        
        // Remove item from order
        function removeItemFromOrder(index) {
            orderItems.splice(index, 1);
            updateOrderItemsUI();
        }
        
        // Update item quantity
        function updateItemQuantity(index, newQuantity) {
            if (newQuantity <= 0) {
                removeItemFromOrder(index);
                return;
            }
            
            orderItems[index].quantity = newQuantity;
            orderItems[index].subtotal = orderItems[index].price * newQuantity;
            updateOrderItemsUI();
        }
        
        // Add note to item
        function addNoteToItem(index) {
            document.getElementById("noteItemId").value = index;
            document.getElementById("itemNotes").value = orderItems[index].notes || "";
            
            const itemNotesModal = new bootstrap.Modal(document.getElementById("itemNotesModal"));
            itemNotesModal.show();
        }
        
        // Show toast notification
        function showToast(message) {
            // Create toast element
            const toastContainer = document.createElement("div");
            toastContainer.className = "toast-container position-fixed bottom-0 end-0 p-3";
            toastContainer.style.zIndex = "5";
            
            const toast = document.createElement("div");
            toast.className = "toast show";
            toast.setAttribute("role", "alert");
            toast.setAttribute("aria-live", "assertive");
            toast.setAttribute("aria-atomic", "true");
            
            const toastBody = document.createElement("div");
            toastBody.className = "toast-body d-flex align-items-center";
            toastBody.innerHTML = `
                <i class="ri-check-line text-success me-2"></i>
                ${message}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            `;
            
            toast.appendChild(toastBody);
            toastContainer.appendChild(toast);
            document.body.appendChild(toastContainer);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove("show");
                setTimeout(() => {
                    document.body.removeChild(toastContainer);
                }, 150);
            }, 3000);
        }
        
        // Open payment screen
        function openPaymentScreen() {
            // Calculate current totals
            const totals = calculateTotals();
            
            // Update the payment modal with current values
            document.getElementById("directSubtotalValue").textContent = formatCurrency(totals.subtotal);
            document.getElementById("directTaxValue").textContent = formatCurrency(totals.tax);
            updateDirectTipDisplay();
            
            // Show the payment modal
            const paymentModal = new bootstrap.Modal(document.getElementById("directPaymentModal"));
            paymentModal.show();
        }
        
        // Update direct payment tip display
        function updateDirectTipDisplay() {
            const tipAmount = parseFloat(document.getElementById("direct_tip").value) || 0;
            const subtotal = parseFloat(document.getElementById("subtotalValue").textContent.replace(/[^0-9.]/g, "")) || 0;
            const tax = parseFloat(document.getElementById("taxValue").textContent.replace(/[^0-9.]/g, "")) || 0;
            const total = subtotal + tax + tipAmount;
            
            document.getElementById("directTipDisplay").textContent = formatCurrency(tipAmount);
            document.getElementById("directTotalWithTip").textContent = formatCurrency(total);
        }
        
        // Update order items UI
        function updateOrderItemsUI() {
            const orderItemsList = document.getElementById("orderItemsList");
            const emptyOrderMessage = document.getElementById("emptyOrderMessage");
            const saveOrderBtn = document.getElementById("saveOrderBtn");
            
            if (orderItems.length === 0) {
                orderItemsList.classList.add("d-none");
                emptyOrderMessage.classList.remove("d-none");
                saveOrderBtn.disabled = true;
            } else {
                orderItemsList.classList.remove("d-none");
                emptyOrderMessage.classList.add("d-none");
                saveOrderBtn.disabled = false;
                
                // Clear current items
                orderItemsList.innerHTML = "";
                
                // Create hidden inputs for form submission
                const form = document.getElementById("orderForm");
                
                // Remove existing item inputs
                const existingInputs = form.querySelectorAll(".order-item-input");
                existingInputs.forEach(input => input.remove());
                
                // Add items to UI
                orderItems.forEach((item, index) => {
                    const itemElement = document.createElement("div");
                    itemElement.className = "d-flex justify-content-between align-items-start border-bottom pb-2 mb-2";
                    
                    const itemContent = `
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="updateItemQuantity(${index}, ${item.quantity - 1})">
                                            <i class="ri-subtract-line"></i>
                                        </button>
                                        <span class="btn btn-outline-secondary disabled">${item.quantity}</span>
                                        <button type="button" class="btn btn-outline-secondary" 
                                                onclick="updateItemQuantity(${index}, ${item.quantity + 1})">
                                            <i class="ri-add-line"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-0 fw-medium">${item.name}</p>
                                    ${item.notes ? 
                                        `<button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="addNoteToItem(${index})">
                                            <i class="ri-edit-line me-1"></i>Edit note
                                         </button>` : 
                                        `<button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="addNoteToItem(${index})">
                                            <i class="ri-add-line me-1"></i>Add note
                                         </button>`
                                    }
                                </div>
                            </div>
                        </div>
                        <div class="ms-2 d-flex flex-column align-items-end">
                            <div class="fw-medium">${formatCurrency(item.subtotal)}</div>
                            <button type="button" class="btn btn-sm text-danger p-0" onclick="removeItemFromOrder(${index})">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    `;
                    
                    itemElement.innerHTML = itemContent;
                    orderItemsList.appendChild(itemElement);
                    
                    // Add hidden inputs for each item property
                    const itemIdInput = document.createElement("input");
                    itemIdInput.type = "hidden";
                    itemIdInput.name = `items[${index}][menu_item_id]`;
                    itemIdInput.value = item.menu_item_id;
                    itemIdInput.className = "order-item-input";
                    form.appendChild(itemIdInput);
                    
                    const quantityInput = document.createElement("input");
                    quantityInput.type = "hidden";
                    quantityInput.name = `items[${index}][quantity]`;
                    quantityInput.value = item.quantity;
                    quantityInput.className = "order-item-input";
                    form.appendChild(quantityInput);
                    
                    if (item.notes) {
                        const notesInput = document.createElement("input");
                        notesInput.type = "hidden";
                        notesInput.name = `items[${index}][notes]`;
                        notesInput.value = item.notes;
                        notesInput.className = "order-item-input";
                        form.appendChild(notesInput);
                    }
                });
            }
            
            // Update totals
            calculateTotals();
        }
        
        // Document ready function
        document.addEventListener("DOMContentLoaded", function() {
            // Search menu items
            const searchInput = document.getElementById("searchMenuItems");
            const menuItems = document.querySelectorAll(".menu-item");
            
            searchInput.addEventListener("keyup", function() {
                const searchTerm = this.value.toLowerCase();
                
                menuItems.forEach(function(item) {
                    const itemName = item.querySelector(".card-title").textContent.toLowerCase();
                    const itemDescription = item.querySelector(".card-text").textContent.toLowerCase();
                    
                    if (itemName.includes(searchTerm) || itemDescription.includes(searchTerm)) {
                        item.style.display = "";
                    } else {
                        item.style.display = "none";
                    }
                });
            });
            
            // Save note button
            document.getElementById("saveItemNoteBtn").addEventListener("click", function() {
                const index = parseInt(document.getElementById("noteItemId").value);
                const notes = document.getElementById("itemNotes").value.trim();
                
                orderItems[index].notes = notes;
                updateOrderItemsUI();
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById("itemNotesModal"));
                modal.hide();
            });
            
            // Direct payment tip handling
            const directTipInput = document.getElementById("direct_tip");
            if (directTipInput) {
                directTipInput.addEventListener("input", updateDirectTipDisplay);
                
                // Quick tip buttons
                const quickTipButtons = document.querySelectorAll(".quick-tip-btn");
                quickTipButtons.forEach(function(btn) {
                    btn.addEventListener("click", function() {
                        const percentage = parseFloat(this.getAttribute("data-percentage"));
                        const subtotal = parseFloat(document.getElementById("subtotalValue").textContent.replace(/[^0-9.]/g, "")) || 0;
                        
                        const tipAmount = (subtotal * (percentage / 100)).toFixed(2);
                        directTipInput.value = tipAmount;
                        updateDirectTipDisplay();
                    });
                });
            }
            
            // Create and pay button
            const createAndPayBtn = document.getElementById("createAndPayBtn");
            if (createAndPayBtn) {
                createAndPayBtn.addEventListener("click", function() {
                    // Get form data
                    const form = document.getElementById("orderForm");
                    const tableId = document.getElementById("table_id").value;
                    const staffId = document.getElementById("staff_id").value;
                    
                    if (!tableId || !staffId || orderItems.length === 0) {
                        alert("Please select a table, staff member, and add at least one item.");
                        return;
                    }
                    
                    // Add payment details to form
                    const paymentMethod = document.getElementById("direct_payment_method").value;
                    const tip = parseFloat(document.getElementById("direct_tip").value) || 0;
                    
                    // Add hidden fields for payment
                    let paymentMethodInput = document.querySelector("input[name=\'payment_method\']");
                    if (!paymentMethodInput) {
                        paymentMethodInput = document.createElement("input");
                        paymentMethodInput.type = "hidden";
                        paymentMethodInput.name = "payment_method";
                        form.appendChild(paymentMethodInput);
                    }
                    paymentMethodInput.value = paymentMethod;
                    
                    let tipInput = document.querySelector("input[name=\'tip\']");
                    if (!tipInput) {
                        tipInput = document.createElement("input");
                        tipInput.type = "hidden";
                        tipInput.name = "tip";
                        form.appendChild(tipInput);
                    }
                    tipInput.value = tip;
                    
                    let paidInput = document.querySelector("input[name=\'paid\']");
                    if (!paidInput) {
                        paidInput = document.createElement("input");
                        paidInput.type = "hidden";
                        paidInput.name = "paid";
                        paidInput = document.createElement("input");
                        paidInput.type = "hidden";
                        paidInput.name = "paid";
                        form.appendChild(paidInput);
                    }
                    paidInput.value = 1;
                    
                    // Submit the form
                    form.submit();
                });
            }
        });
    </script>
    ';
}

// Include footer
include dirname(__DIR__) . '/includes/footer.php';
?>