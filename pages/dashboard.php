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

// Include authentication
require_once 'includes/auth.php';

// Require login to access this page
requireLogin();

// Initialize classes
$db = Database::getInstance();
$orderObj = new Order();
$staffObj = new Staff();
$menuItemObj = new MenuItem();

// Get dashboard stats
$totalOrders = $orderObj->getTotalOrders();
$totalRevenue = $orderObj->getTotalRevenue();
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
$activeStaff = $staffObj->getActiveStaffCount();

// Get recent orders
$recentOrders = $orderObj->getRecentOrders(5);

// Get top menu items
$topMenuItems = $menuItemObj->getTopMenuItems(5);

// Set page title
$pageTitle = "Dashboard";
$showNotifications = true;

// Include header
include('includes/header.php');
?>

<div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
    <div>
        <h1 class="fs-2 fw-bold">Dashboard</h1>
        <p class="text-muted">Overview of your cafe operations</p>
    </div>
    <div class="mt-3 md:mt-0 d-flex flex-column flex-sm-row gap-2">
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="ri-calendar-line"></i>
            <span>Today</span>
        </button>
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
                    <p class="text-muted small mb-1">Total Orders</p>
                    <h3 class="fs-4 fw-bold"><?php echo $totalOrders; ?></h3>
                </div>
                <div class="icon icon-secondary">
                    <i class="ri-shopping-bag-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-success d-flex align-items-center">
                    <i class="ri-arrow-up-line me-1"></i>
                    12.5%
                </span>
                <span class="text-muted ms-2">vs yesterday</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Revenue</p>
                    <h3 class="fs-4 fw-bold"><?php echo CURRENCY . ' ' . number_format($totalRevenue, 2); ?></h3>
                </div>
                <div class="icon icon-success">
                    <i class="ri-money-dollar-circle-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-success d-flex align-items-center">
                    <i class="ri-arrow-up-line me-1"></i>
                    8.2%
                </span>
                <span class="text-muted ms-2">vs yesterday</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Avg. Order Value</p>
                    <h3 class="fs-4 fw-bold"><?php echo CURRENCY . ' ' . number_format($avgOrderValue, 2); ?></h3>
                </div>
                <div class="icon icon-warning">
                    <i class="ri-pie-chart-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-danger d-flex align-items-center">
                    <i class="ri-arrow-down-line me-1"></i>
                    3.1%
                </span>
                <span class="text-muted ms-2">vs yesterday</span>
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
                <div class="icon icon-primary">
                    <i class="ri-team-line"></i>
                </div>
            </div>
            <div class="d-flex align-items-center mt-3 small">
                <span class="text-success d-flex align-items-center">
                    <i class="ri-arrow-up-line me-1"></i>
                    1
                </span>
                <span class="text-muted ms-2">vs yesterday</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent orders and menu performance -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold">Recent Orders</h5>
            <a href="<?php echo BASE_URL; ?>/pages/orders.php" class="btn btn-link text-secondary p-0">View all</a>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Table</th>
                            <th>Server</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No orders found</td>
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
                                <td class="text-end">
                                    <button class="btn btn-sm btn-icon btn-ghost text-muted">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                <p class="small text-muted mb-0">
                    <?php echo count($recentOrders); ?> of <?php echo count($recentOrders); ?> orders
                </p>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="ri-arrow-left-s-line"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold">Top Menu Items</h5>
            <a href="<?php echo BASE_URL; ?>/pages/menu-management.php" class="btn btn-link text-secondary p-0">View all</a>
        </div>
        <div class="card">
            <div class="card-body p-3">
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($topMenuItems)): ?>
                    <div class="py-4 text-center text-muted">No data available</div>
                    <?php else: ?>
                        <?php foreach ($topMenuItems as $item): ?>
                        <div class="d-flex justify-content-between align-items-center">
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
                                <p class="fw-medium mb-0"><?php echo htmlspecialchars($item['sold_count']); ?></p>
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
    </div>
</div>

<?php
// Add page-specific scripts
$extraScripts = '
<script>
    // Dashboard charts can be added here
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Dashboard loaded");
    });
</script>
';

// Include footer
include('includes/footer.php');
?>