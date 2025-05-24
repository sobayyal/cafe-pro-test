<?php
// index.php
// Dashboard page for the Cafe Management System

// Include config file
require_once 'config/config.php';

// Include database and required classes
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/MenuItem.php';
require_once 'classes/Order.php';
require_once 'classes/Staff.php';
require_once 'classes/Table.php';

// Include authentication
require_once 'includes/auth.php';

// Require login to access this page
requireLogin();

// Initialize classes
$db = Database::getInstance();
$orderObj = new Order();
$staffObj = new Staff();
$menuItemObj = new MenuItem();
$tableObj = new Table();

// Get dashboard stats
$totalOrders = $orderObj->getTotalOrders();
$totalRevenue = $orderObj->getTotalRevenue();
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
$activeStaff = $staffObj->getActiveStaffCount();

// Get recent orders
$recentOrders = $orderObj->getRecentOrders(5);

// Get top menu items
$topMenuItems = $menuItemObj->getTopMenuItems(5);

// Get table status
$tables = $tableObj->getTablesWithOrders();
$availableTables = 0;
$occupiedTables = 0;

foreach ($tables as $table) {
    if ($table['status'] === 'available') {
        $availableTables++;
    } else if ($table['status'] === 'occupied') {
        $occupiedTables++;
    }
}

// Get pending orders count
$pendingOrders = $orderObj->getOrderCountByStatus('pending');

// Get today's sales
$today = date('Y-m-d');
$todaySales = $orderObj->getDailyRevenue($today);

// Set page title
$pageTitle = "Dashboard";
$showNotifications = true;

// Include header
include('includes/header.php');
?>

<div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
    <div>
        <h1 class="fs-2 fw-bold">Dashboard</h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
    </div>
    <div class="mt-3 md:mt-0 d-flex flex-column flex-sm-row gap-2">
        <a href="<?php echo BASE_URL; ?>/pages/orders.php?action=create" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="ri-add-line"></i>
            <span>New Order</span>
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Today's Sales</p>
                    <h3 class="fs-4 fw-bold"><?php echo CURRENCY . ' ' . number_format($todaySales, 2); ?></h3>
                </div>
                <div class="icon icon-success">
                    <i class="ri-money-dollar-circle-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-muted">Active Orders: <?php echo $pendingOrders; ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Total Revenue</p>
                    <h3 class="fs-4 fw-bold"><?php echo CURRENCY . ' ' . number_format($totalRevenue, 2); ?></h3>
                </div>
                <div class="icon icon-primary">
                    <i class="ri-pie-chart-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-muted">Total Orders: <?php echo $totalOrders; ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Tables</p>
                    <h3 class="fs-4 fw-bold"><?php echo $availableTables; ?> Available</h3>
                </div>
                <div class="icon icon-warning">
                    <i class="ri-table-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-muted">Occupied: <?php echo $occupiedTables; ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Active Staff</p>
                    <h3 class="fs-4 fw-bold"><?php echo $activeStaff; ?></h3>
                </div>
                <div class="icon icon-secondary">
                    <i class="ri-team-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-muted">Avg. Order: <?php echo CURRENCY . ' ' . number_format($avgOrderValue, 2); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Table Status and Recent Orders -->
<div class="row g-4">
    <!-- Table Status Section -->
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold">Table Status</h5>
            <a href="<?php echo BASE_URL; ?>/pages/tables.php" class="btn btn-link text-secondary p-0">Manage Tables</a>
        </div>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Server</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tables)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No tables found</td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                // Sort tables by name for better display
                                usort($tables, function($a, $b) {
                                    return strnatcmp($a['name'], $b['name']);
                                });
                                
                                // Display only the first 8 tables to keep the dashboard clean
                                $displayTables = array_slice($tables, 0, 8);
                                
                                foreach ($displayTables as $table): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($table['name']); ?></td>
                                    <td>
                                        <?php if ($table['status'] === 'available'): ?>
                                        <span class="badge-status badge-completed">Available</span>
                                        <?php elseif ($table['status'] === 'occupied'): ?>
                                        <span class="badge-status badge-in-progress">Occupied</span>
                                        <?php else: ?>
                                        <span class="badge-status badge-pending">Reserved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($table['current_order_number']) && !empty($table['current_order_number'])) {
                                            echo htmlspecialchars($table['current_order_number']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($table['server_name']) && !empty($table['server_name'])) {
                                            echo htmlspecialchars($table['server_name']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($table['status'] === 'available'): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/orders.php?action=create" class="btn btn-sm btn-outline-primary">New Order</a>
                                        <?php elseif (isset($table['current_order_id']) && !empty($table['current_order_id'])): ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/orders.php?action=view&id=<?php echo $table['current_order_id']; ?>" class="btn btn-sm btn-outline-secondary">View Order</a>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled>No Action</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($tables) > 8): ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <a href="<?php echo BASE_URL; ?>/pages/tables.php" class="btn btn-link">View All Tables</a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders Section -->
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold">Recent Orders</h5>
            <a href="<?php echo BASE_URL; ?>/pages/orders.php" class="btn btn-link text-secondary p-0">View All</a>
        </div>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Table</th>
                                <th>Server</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No orders found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr class="cursor-pointer" onclick="window.location.href='<?php echo BASE_URL; ?>/pages/orders.php?action=view&id=<?php echo $order['id']; ?>'">
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['table_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['staff_name']); ?></td>
                                    <td class="fw-medium"><?php echo CURRENCY . ' ' . number_format($order['total'], 2); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
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
                                        $statusText = ucfirst(str_replace('_', ' ', $order['status']));
                                        ?>
                                        <span class="badge-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Selling Items and Quick Actions -->
<div class="row g-4 mt-1">
    <!-- Top Selling Items Section -->
    <div class="col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold">Top Selling Items</h5>
            <a href="<?php echo BASE_URL; ?>/pages/menu-management.php" class="btn btn-link text-secondary p-0">Manage Menu</a>
        </div>
        <div class="card">
            <div class="card-body">
                <?php if (empty($topMenuItems)): ?>
                <div class="py-4 text-center text-muted">No data available</div>
                <?php else: ?>
                    <?php foreach ($topMenuItems as $index => $item): ?>
                    <div class="d-flex justify-content-between align-items-center <?php echo $index < count($topMenuItems) - 1 ? 'mb-3 pb-3 border-bottom' : ''; ?>">
                        <div class="d-flex align-items-center">
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
                            <div class="rounded bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="<?php echo htmlspecialchars($item['icon']); ?> <?php echo $iconColorClass; ?>"></i>
                            </div>
                            <div>
                                <p class="fw-medium mb-0"><?php echo htmlspecialchars($item['name']); ?></p>
                                <p class="text-sm text-muted mb-0"><?php echo CURRENCY . ' ' . number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                        <div class="text-end">
                            <p class="fw-medium mb-0"><?php echo htmlspecialchars($item['sold_count']); ?> sold</p>
                            <p class="text-sm <?php echo $item['trend'] > 0 ? 'text-success' : 'text-danger'; ?> mb-0">
                                <?php echo $item['trend'] > 0 ? '+' : ''; ?><?php echo $item['trend']; ?>%
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Section -->
    <div class="col-lg-5">
        <div class="mb-3">
            <h5 class="fw-semibold">Quick Actions</h5>
        </div>
        <div class="row g-3">
            <div class="col-6">
                <a href="<?php echo BASE_URL; ?>/pages/orders.php?action=create" class="card h-100 text-decoration-none">
                    <div class="card-body p-3 text-center">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="ri-add-line text-primary fs-4"></i>
                        </div>
                        <h6 class="mb-1">New Order</h6>
                        <p class="text-muted small mb-0">Create a new order</p>
                    </div>
                </a>
            </div>
            
            <div class="col-6">
                <a href="<?php echo BASE_URL; ?>/pages/menu-management.php" class="card h-100 text-decoration-none">
                    <div class="card-body p-3 text-center">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="ri-restaurant-line text-primary fs-4"></i>
                        </div>
                        <h6 class="mb-1">Menu</h6>
                        <p class="text-muted small mb-0">Manage menu items</p>
                    </div>
                </a>
            </div>
            
            <div class="col-6">
                <a href="<?php echo BASE_URL; ?>/pages/reports.php" class="card h-100 text-decoration-none">
                    <div class="card-body p-3 text-center">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="ri-file-chart-line text-primary fs-4"></i>
                        </div>
                        <h6 class="mb-1">Reports</h6>
                        <p class="text-muted small mb-0">View sales reports</p>
                    </div>
                </a>
            </div>
            
            <div class="col-6">
                <a href="<?php echo BASE_URL; ?>/pages/settings.php" class="card h-100 text-decoration-none">
                    <div class="card-body p-3 text-center">
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="ri-settings-4-line text-primary fs-4"></i>
                        </div>
                        <h6 class="mb-1">Settings</h6>
                        <p class="text-muted small mb-0">Configure system</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Add page-specific scripts
$extraScripts = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Auto-refresh dashboard every 2 minutes
        setTimeout(function() {
            location.reload();
        }, 120000);
    });
</script>
';

// Include footer
include('includes/footer.php');
?>