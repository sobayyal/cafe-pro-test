<?php
// pages/reports.php
// Reports page for the Cafe Management System

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Order.php';
require_once dirname(__DIR__) . '/classes/MenuItem.php';
require_once dirname(__DIR__) . '/classes/Staff.php';
require_once dirname(__DIR__) . '/classes/Table.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Require admin privileges to access this page
requireAdmin();

// Initialize classes
$db = Database::getInstance();
$orderObj = new Order();
$menuItemObj = new MenuItem();
$staffObj = new Staff();
$tableObj = new Table();

// Set page title
$pageTitle = "Reports";
$showNotifications = true;

// Get report type from query parameter
$reportType = isset($_GET['type']) ? $_GET['type'] : 'daily';

// Get date range
$today = date('Y-m-d');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $today;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : $today;

// Get report data based on type
$reportData = [];

switch ($reportType) {
    case 'daily':
        // Daily sales report
        $dailyRevenue = $orderObj->getDailyRevenue($startDate);
        $orderCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?", 
            [$startDate]
        )['count'];
        
        $statusCounts = [];
        $statusQuery = "
            SELECT status, COUNT(*) as count 
            FROM orders 
            WHERE DATE(created_at) = ? 
            GROUP BY status
        ";
        $statusResults = $db->select($statusQuery, [$startDate]);
        
        foreach ($statusResults as $result) {
            $statusCounts[$result['status']] = $result['count'];
        }
        
        // Get top selling items for the day
        $topItemsQuery = "
            SELECT m.name, m.price, SUM(oi.quantity) as quantity_sold, SUM(oi.subtotal) as total_sales
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN menu_items m ON oi.menu_item_id = m.id
            WHERE DATE(o.created_at) = ?
            GROUP BY oi.menu_item_id
            ORDER BY quantity_sold DESC
            LIMIT 5
        ";
        $topItems = $db->select($topItemsQuery, [$startDate]);
        
        // Get payment method breakdown
        $paymentMethodQuery = "
            SELECT payment_method, COUNT(*) as count, SUM(total) as total_amount
            FROM orders
            WHERE DATE(created_at) = ?
            GROUP BY payment_method
        ";
        $paymentMethods = $db->select($paymentMethodQuery, [$startDate]);
        
        // Get hourly sales breakdown
        $hourlyQuery = "
            SELECT HOUR(created_at) as hour, COUNT(*) as orders, SUM(total) as revenue
            FROM orders
            WHERE DATE(created_at) = ?
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ";
        $hourlySales = $db->select($hourlyQuery, [$startDate]);
        
        // Format hourly data for chart
        $hourLabels = [];
        $hourValues = [];
        
        for ($i = 0; $i < 24; $i++) {
            $hourLabels[] = sprintf("%02d:00", $i);
            $hourValues[] = 0;
        }
        
        foreach ($hourlySales as $hourData) {
            $hourValues[$hourData['hour']] = floatval($hourData['revenue']);
        }
        
        $reportData = [
            'date' => $startDate,
            'total_revenue' => $dailyRevenue,
            'order_count' => $orderCount,
            'status_counts' => $statusCounts,
            'top_items' => $topItems,
            'payment_methods' => $paymentMethods,
            'hourly_labels' => $hourLabels,
            'hourly_values' => $hourValues
        ];
        break;
        
    case 'sales':
        // Sales report for date range
        $salesQuery = "
            SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total) as revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ";
        $salesData = $db->select($salesQuery, [$startDate, $endDate]);
        
        // Get total for the period
        $totalQuery = "
            SELECT COUNT(*) as order_count, SUM(total) as total_revenue
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
        ";
        $totals = $db->selectOne($totalQuery, [$startDate, $endDate]);
        
        // Get product category breakdown
        $categoryQuery = "
            SELECT c.name as category, SUM(oi.quantity) as quantity_sold, SUM(oi.subtotal) as total_sales
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN menu_items m ON oi.menu_item_id = m.id
            JOIN categories c ON m.category_id = c.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY total_sales DESC
        ";
        $categoryBreakdown = $db->select($categoryQuery, [$startDate, $endDate]);
        
        // Format dates for chart
        $dateLabels = [];
        $revenueValues = [];
        $orderCountValues = [];
        
        foreach ($salesData as $dayData) {
            $dateLabels[] = date('M d', strtotime($dayData['date']));
            $revenueValues[] = floatval($dayData['revenue']);
            $orderCountValues[] = intval($dayData['orders']);
        }
        
        $reportData = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sales_data' => $salesData,
            'total_orders' => $totals['order_count'] ?? 0,
            'total_revenue' => $totals['total_revenue'] ?? 0,
            'category_breakdown' => $categoryBreakdown,
            'date_labels' => $dateLabels,
            'revenue_values' => $revenueValues,
            'order_count_values' => $orderCountValues
        ];
        break;
        
    case 'items':
        // Menu items performance report
        $itemsQuery = "
            SELECT m.id, m.name, m.price, c.name as category,
                   COUNT(DISTINCT o.id) as order_count,
                   SUM(oi.quantity) as quantity_sold,
                   SUM(oi.subtotal) as total_sales
            FROM menu_items m
            LEFT JOIN order_items oi ON m.id = oi.menu_item_id
            LEFT JOIN orders o ON oi.order_id = o.id AND DATE(o.created_at) BETWEEN ? AND ?
            LEFT JOIN categories c ON m.category_id = c.id
            GROUP BY m.id
            ORDER BY quantity_sold DESC
        ";
        $itemsData = $db->select($itemsQuery, [$startDate, $endDate]);
        
        // Get top 10 items for the chart
        $top10Items = array_slice($itemsData, 0, 10);
        
        // Format data for chart
        $itemLabels = [];
        $itemValues = [];
        
        foreach ($top10Items as $item) {
            if ($item['quantity_sold'] > 0) {
                $itemLabels[] = $item['name'];
                $itemValues[] = intval($item['quantity_sold']);
            }
        }
        
        $reportData = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'items_data' => $itemsData,
            'item_labels' => $itemLabels,
            'item_values' => $itemValues
        ];
        break;
        
    case 'staff':
        // Staff performance report
        $staffQuery = "
            SELECT s.id, s.name, COUNT(o.id) as order_count, SUM(o.total) as total_revenue,
                   AVG(o.total) as avg_order_value
            FROM staff s
            LEFT JOIN orders o ON s.id = o.staff_id AND DATE(o.created_at) BETWEEN ? AND ?
            WHERE s.active = 1
            GROUP BY s.id
            ORDER BY total_revenue DESC
        ";
        $staffData = $db->select($staffQuery, [$startDate, $endDate]);
        
        // Format data for chart
        $staffLabels = [];
        $staffRevenue = [];
        $staffOrders = [];
        
        foreach ($staffData as $staff) {
            if ($staff['order_count'] > 0) {
                $staffLabels[] = $staff['name'];
                $staffRevenue[] = floatval($staff['total_revenue']);
                $staffOrders[] = intval($staff['order_count']);
            }
        }
        
        $reportData = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'staff_data' => $staffData,
            'staff_labels' => $staffLabels,
            'staff_revenue' => $staffRevenue,
            'staff_orders' => $staffOrders
        ];
        break;
        
    case 'tables':
        // Table utilization report
        $tableUtilization = $tableObj->getTableUtilization($startDate);
        
        // Get orders per table for date range
        $ordersPerTableQuery = "
            SELECT t.id, t.name, COUNT(o.id) as order_count, SUM(o.total) as total_revenue
            FROM tables t
            LEFT JOIN orders o ON t.id = o.table_id AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY t.id
            ORDER BY order_count DESC
        ";
        $tablesData = $db->select($ordersPerTableQuery, [$startDate, $endDate]);
        
        // Format data for chart
        $tableLabels = [];
        $tableValues = [];
        
        foreach ($tablesData as $table) {
            $tableLabels[] = $table['name'];
            $tableValues[] = intval($table['order_count']);
        }
        
        $reportData = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'utilization_rate' => $tableUtilization['utilization_rate'],
            'used_tables' => $tableUtilization['used_tables'],
            'total_tables' => $tableUtilization['total_tables'],
            'tables_data' => $tablesData,
            'table_labels' => $tableLabels,
            'table_values' => $tableValues
        ];
        break;
}

// Include header
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
    <div>
        <h1 class="fs-2 fw-bold">Reports</h1>
        <p class="text-muted">View and analyze cafe performance</p>
    </div>
</div>

<!-- Report Selection and Date Range -->
<div class="card mb-4">
    <div class="card-body">
        <form id="reportForm" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="reportType" class="form-label">Report Type</label>
                <select id="reportType" name="type" class="form-select">
                    <option value="daily" <?php echo $reportType == 'daily' ? 'selected' : ''; ?>>Daily Sales Report</option>
                    <option value="sales" <?php echo $reportType == 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                    <option value="items" <?php echo $reportType == 'items' ? 'selected' : ''; ?>>Menu Items Performance</option>
                    <option value="staff" <?php echo $reportType == 'staff' ? 'selected' : ''; ?>>Staff Performance</option>
                    <option value="tables" <?php echo $reportType == 'tables' ? 'selected' : ''; ?>>Table Utilization</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" id="startDate" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" id="endDate" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<?php if ($reportType == 'daily'): ?>
<!-- Daily Sales Report -->
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Sales Summary - <?php echo date('F d, Y', strtotime($reportData['date'])); ?></h5>
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Total Revenue</span>
                        <span class="fw-bold fs-5"><?php echo CURRENCY . ' ' . number_format($reportData['total_revenue'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Orders</span>
                        <span class="fw-bold"><?php echo $reportData['order_count']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Average Order Value</span>
                        <span class="fw-bold">
                            <?php 
                            $avgOrderValue = $reportData['order_count'] > 0 ? 
                                $reportData['total_revenue'] / $reportData['order_count'] : 0;
                            echo CURRENCY . ' ' . number_format($avgOrderValue, 2); 
                            ?>
                        </span>
                    </div>
                </div>
                
                <h6 class="mt-4 mb-3">Order Status</h6>
                <div>
                    <?php 
                    $statuses = ['completed', 'in_progress', 'preparing', 'pending', 'cancelled'];
                    $statusLabels = [
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'preparing' => 'Preparing',
                        'pending' => 'Pending',
                        'cancelled' => 'Cancelled'
                    ];
                    
                    foreach ($statuses as $status): 
                        $count = $reportData['status_counts'][$status] ?? 0;
                        $percentage = $reportData['order_count'] > 0 ? 
                            ($count / $reportData['order_count']) * 100 : 0;
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo $statusLabels[$status]; ?></span>
                            <span><?php echo $count; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo $status == 'completed' ? 'success' : 
                                                            ($status == 'in_progress' ? 'primary' : 
                                                            ($status == 'preparing' ? 'warning' : 
                                                            ($status == 'pending' ? 'secondary' : 'danger'))); ?>" 
                                role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <h6 class="mt-4 mb-3">Payment Methods</h6>
                <div>
                    <?php foreach ($reportData['payment_methods'] as $payment): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>
                            <?php 
                            echo $payment['payment_method'] == 'credit_card' ? 'Credit Card' : 
                                ($payment['payment_method'] == 'cash' ? 'Cash' : ucfirst($payment['payment_method']));
                            ?>
                        </span>
                        <span><?php echo CURRENCY . ' ' . number_format($payment['total_amount'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Hourly Sales</h5>
                <canvas id="hourlySalesChart" height="250"></canvas>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Top Selling Items</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity Sold</th>
                                <th>Total Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['top_items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity_sold']; ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($item['total_sales'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportData['top_items'])): ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available for this date</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType == 'sales'): ?>
<!-- Sales Report -->
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Sales Summary</h5>
                <p class="text-muted">
                    <?php echo date('M d, Y', strtotime($reportData['start_date'])); ?> - 
                    <?php echo date('M d, Y', strtotime($reportData['end_date'])); ?>
                </p>
                
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Total Revenue</span>
                        <span class="fw-bold fs-5"><?php echo CURRENCY . ' ' . number_format($reportData['total_revenue'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Orders</span>
                        <span class="fw-bold"><?php echo $reportData['total_orders']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Average Order Value</span>
                        <span class="fw-bold">
                            <?php 
                            $avgOrderValue = $reportData['total_orders'] > 0 ? 
                                $reportData['total_revenue'] / $reportData['total_orders'] : 0;
                            echo CURRENCY . ' ' . number_format($avgOrderValue, 2); 
                            ?>
                        </span>
                    </div>
                </div>
                
                <h6 class="mt-4 mb-3">Category Breakdown</h6>
                <div>
                    <?php foreach ($reportData['category_breakdown'] as $category): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo htmlspecialchars($category['category']); ?></span>
                        <span><?php echo CURRENCY . ' ' . number_format($category['total_sales'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($reportData['category_breakdown'])): ?>
                    <div class="text-center text-muted">No data available for this period</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Sales Trend</h5>
                <canvas id="salesTrendChart" height="250"></canvas>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Daily Sales</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Average Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['sales_data'] as $day): ?>
                            <tr>
                                <td><?php echo date('D, M d, Y', strtotime($day['date'])); ?></td>
                                <td><?php echo $day['orders']; ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($day['revenue'], 2); ?></td>
                                <td>
                                    <?php 
                                    $avgDayOrder = $day['orders'] > 0 ? $day['revenue'] / $day['orders'] : 0;
                                    echo CURRENCY . ' ' . number_format($avgDayOrder, 2); 
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportData['sales_data'])): ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available for this period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType == 'items'): ?>
<!-- Menu Items Performance Report -->
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Top Selling Items</h5>
                <p class="text-muted">
                    <?php echo date('M d, Y', strtotime($reportData['start_date'])); ?> - 
                    <?php echo date('M d, Y', strtotime($reportData['end_date'])); ?>
                </p>
                
                <canvas id="itemsSalesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Menu Items Performance</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Qty Sold</th>
                                <th>Total Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['items_data'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity_sold'] ?? 0; ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($item['total_sales'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportData['items_data'])): ?>
                            <tr>
                                <td colspan="5" class="text-center">No data available for this period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType == 'staff'): ?>
<!-- Staff Performance Report -->
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Staff Performance Comparison</h5>
                <p class="text-muted">
                    <?php echo date('M d, Y', strtotime($reportData['start_date'])); ?> - 
                    <?php echo date('M d, Y', strtotime($reportData['end_date'])); ?>
                </p>
                
                <canvas id="staffPerformanceChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Staff Performance Details</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Orders</th>
                                <th>Total Revenue</th>
                                <th>Avg Order Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['staff_data'] as $staff): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                <td><?php echo $staff['order_count']; ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($staff['total_revenue'] ?? 0, 2); ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($staff['avg_order_value'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportData['staff_data'])): ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available for this period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType == 'tables'): ?>
<!-- Table Utilization Report -->
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Table Usage Comparison</h5>
                <p class="text-muted">
                    <?php echo date('M d, Y', strtotime($reportData['start_date'])); ?> - 
                    <?php echo date('M d, Y', strtotime($reportData['end_date'])); ?>
                </p>
                
                <canvas id="tableUtilizationChart" height="300"></canvas>
                
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Table Utilization Rate</span>
                        <span class="fw-bold"><?php echo number_format($reportData['utilization_rate'], 1); ?>%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tables Used</span>
                        <span class="fw-bold"><?php echo $reportData['used_tables']; ?> of <?php echo $reportData['total_tables']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Table Performance Details</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Orders</th>
                                <th>Total Revenue</th>
                                <th>Avg Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['tables_data'] as $table): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($table['name']); ?></td>
                                <td><?php echo $table['order_count']; ?></td>
                                <td><?php echo CURRENCY . ' ' . number_format($table['total_revenue'] ?? 0, 2); ?></td>
                                <td>
                                    <?php 
                                    $avgTableRevenue = $table['order_count'] > 0 ? 
                                        $table['total_revenue'] / $table['order_count'] : 0;
                                    echo CURRENCY . ' ' . number_format($avgTableRevenue, 2); 
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportData['tables_data'])): ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available for this period</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Print and Export buttons -->
<div class="mt-4 mb-5 d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
        <i class="ri-printer-line me-2"></i> Print Report
    </button>
    <button type="button" class="btn btn-outline-primary" onclick="exportReport();">
        <i class="ri-file-excel-line me-2"></i> Export to Excel
    </button>
</div>

<?php
// Add page-specific scripts
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Handle report type change
        const reportType = document.getElementById("reportType");
        const startDate = document.getElementById("startDate");
        const endDate = document.getElementById("endDate");
        
        if (reportType) {
            reportType.addEventListener("change", function() {
                // If daily report, disable end date
                if (this.value === "daily") {
                    endDate.disabled = true;
                    endDate.value = startDate.value;
                } else {
                    endDate.disabled = false;
                }
            });
            
            // Trigger change event to set initial state
            reportType.dispatchEvent(new Event("change"));
        }
        
        // Initialize charts based on report type
        const currentReport = "' . $reportType . '";
        
        if (currentReport === "daily") {
            // Hourly sales chart
            const hourlySalesCtx = document.getElementById("hourlySalesChart").getContext("2d");
            new Chart(hourlySalesCtx, {
                type: "bar",
                data: {
                    labels: ' . json_encode($reportData['hourly_labels']) . ',
                    datasets: [{
                        label: "Revenue",
                        data: ' . json_encode($reportData['hourly_values']) . ',
                        backgroundColor: "rgba(133, 77, 14, 0.7)",
                        borderColor: "rgba(133, 77, 14, 1)",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return "' . CURRENCY . ' " + value;
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return "Revenue: ' . CURRENCY . ' " + context.raw.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        } else if (currentReport === "sales") {
            // Sales trend chart
            const salesTrendCtx = document.getElementById("salesTrendChart").getContext("2d");
            new Chart(salesTrendCtx, {
                type: "line",
                data: {
                    labels: ' . json_encode($reportData['date_labels'] ?? []) . ',
                    datasets: [{
                        label: "Revenue",
                        data: ' . json_encode($reportData['revenue_values'] ?? []) . ',
                        backgroundColor: "rgba(133, 77, 14, 0.1)",
                        borderColor: "rgba(133, 77, 14, 1)",
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: "y"
                    }, {
                        label: "Orders",
                        data: ' . json_encode($reportData['order_count_values'] ?? []) . ',
                        backgroundColor: "rgba(59, 130, 246, 0.1)",
                        borderColor: "rgba(59, 130, 246, 1)",
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        yAxisID: "y1"
                    }]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: "index",
                        intersect: false,
                    },
                    scales: {
                        y: {
                            position: "left",
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return "' . CURRENCY . ' " + value;
                                }
                            },
                            title: {
                                display: true,
                                text: "Revenue"
                            }
                        },
                        y1: {
                            position: "right",
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: "Orders"
                            }
                        }
                    }
                }
            });
        } else if (currentReport === "items") {
            // Items sales chart
            const itemsSalesCtx = document.getElementById("itemsSalesChart").getContext("2d");
            new Chart(itemsSalesCtx, {
                type: "bar",
                data: {
                    labels: ' . json_encode($reportData['item_labels'] ?? []) . ',
                    datasets: [{
                        label: "Quantity Sold",
                        data: ' . json_encode($reportData['item_values'] ?? []) . ',
                        backgroundColor: [
                            "rgba(133, 77, 14, 0.7)",
                            "rgba(59, 130, 246, 0.7)",
                            "rgba(16, 185, 129, 0.7)",
                            "rgba(245, 158, 11, 0.7)",
                            "rgba(239, 68, 68, 0.7)",
                            "rgba(139, 92, 246, 0.7)",
                            "rgba(236, 72, 153, 0.7)",
                            "rgba(249, 115, 22, 0.7)",
                            "rgba(75, 85, 99, 0.7)",
                            "rgba(20, 184, 166, 0.7)"
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        } else if (currentReport === "staff") {
            // Staff performance chart
            const staffPerformanceCtx = document.getElementById("staffPerformanceChart").getContext("2d");
            new Chart(staffPerformanceCtx, {
                type: "bar",
                data: {
                    labels: ' . json_encode($reportData['staff_labels'] ?? []) . ',
                    datasets: [{
                        label: "Revenue",
                        data: ' . json_encode($reportData['staff_revenue'] ?? []) . ',
                        backgroundColor: "rgba(133, 77, 14, 0.7)",
                        borderColor: "rgba(133, 77, 14, 1)",
                        borderWidth: 1,
                        yAxisID: "y"
                    }, {
                        label: "Orders",
                        data: ' . json_encode($reportData['staff_orders'] ?? []) . ',
                        backgroundColor: "rgba(59, 130, 246, 0.7)",
                        borderColor: "rgba(59, 130, 246, 1)",
                        borderWidth: 1,
                        type: "line",
                        yAxisID: "y1"
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            position: "left",
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return "' . CURRENCY . ' " + value;
                                }
                            },
                            title: {
                                display: true,
                                text: "Revenue"
                            }
                        },
                        y1: {
                            position: "right",
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false,
                            },
                            title: {
                                display: true,
                                text: "Orders"
                            }
                        }
                    }
                }
            });
        } else if (currentReport === "tables") {
            // Table utilization chart
            const tableUtilizationCtx = document.getElementById("tableUtilizationChart").getContext("2d");
            new Chart(tableUtilizationCtx, {
                type: "pie",
                data: {
                    labels: ' . json_encode($reportData['table_labels'] ?? []) . ',
                    datasets: [{
                        data: ' . json_encode($reportData['table_values'] ?? []) . ',
                        backgroundColor: [
                            "rgba(133, 77, 14, 0.7)",
                            "rgba(59, 130, 246, 0.7)",
                            "rgba(16, 185, 129, 0.7)",
                            "rgba(245, 158, 11, 0.7)",
                            "rgba(239, 68, 68, 0.7)",
                            "rgba(139, 92, 246, 0.7)",
                            "rgba(236, 72, 153, 0.7)",
                            "rgba(249, 115, 22, 0.7)",
                            "rgba(75, 85, 99, 0.7)",
                            "rgba(20, 184, 166, 0.7)",
                            "rgba(244, 114, 182, 0.7)",
                            "rgba(168, 85, 247, 0.7)"
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: "right"
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ": " + context.raw + " orders";
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    
    // Export report to Excel function
    function exportReport() {
        // In a real application, this would send an AJAX request to an API endpoint
        // that would generate and return an Excel file
        alert("This feature would export the current report to Excel format.");
        
        // Example API call that would be used in a real application:
        // const reportType = document.getElementById("reportType").value;
        // const startDate = document.getElementById("startDate").value;
        // const endDate = document.getElementById("endDate").value;
        // 
        // window.location.href = `' . BASE_URL . '/api/reports.php?action=export&type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    }
</script>

<style>
    @media print {
        header, .sidebar, .offcanvas, form, button {
            display: none !important;
        }
        
        /* Ensure charts are visible */
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
        
        /* Adjust layout for printing */
        .container-fluid, .row, .col-lg-8, .col-lg-4, .col-lg-5, .col-lg-7 {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
        }
        
        .card {
            page-break-inside: avoid;
            margin-bottom: 20px;
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>
';

// Include footer
include dirname(__DIR__) . '/includes/footer.php';
?>