<?php
// api/orders.php
// API endpoint for order operations

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Order.php';
require_once dirname(__DIR__) . '/classes/Table.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user is logged in
if (!isLoggedIn()) {
    $response['message'] = 'Unauthorized access';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initialize classes
$orderObj = new Order();
$tableObj = new Table();

// Process request based on method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($method === 'GET') {
        // GET requests - retrieve data
        if ($action === 'getAll') {
            // Get all orders
            $orders = $orderObj->getAllOrders();
            $response['success'] = true;
            $response['data'] = $orders;
        } elseif ($action === 'getRecent') {
            // Get recent orders
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
            $orders = $orderObj->getRecentOrders($limit);
            $response['success'] = true;
            $response['data'] = $orders;
        } elseif ($action === 'getOrder' && isset($_GET['id'])) {
            // Get specific order with details
            $order = $orderObj->getOrderWithDetails($_GET['id']);
            if ($order) {
                $response['success'] = true;
                $response['data'] = $order;
            } else {
                $response['message'] = 'Order not found';
            }
        } elseif ($action === 'getStats') {
            // Get order statistics
            $stats = [
                'total_orders' => $orderObj->getTotalOrders(),
                'total_revenue' => $orderObj->getTotalRevenue(),
                'pending_orders' => $orderObj->getOrderCountByStatus('pending'),
                'completed_orders' => $orderObj->getOrderCountByStatus('completed')
            ];
            
            // Get daily revenue for current date if requested
            if (isset($_GET['daily']) && $_GET['daily'] === 'true') {
                $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
                $stats['daily_revenue'] = $orderObj->getDailyRevenue($date);
            }
            
            $response['success'] = true;
            $response['data'] = $stats;
        } else {
            $response['message'] = 'Invalid action';
        }
    } elseif ($method === 'POST') {
        // Decode JSON input if content-type is application/json
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }
        } else {
            $input = $_POST;
        }

        if ($action === 'create') {
            // Create order
            if (isset($input['table_id']) && isset($input['staff_id']) && isset($input['items']) && !empty($input['items'])) {
                // Generate order ID
                $orderId = $orderObj->generateOrderId();
                
                // Calculate totals
                $subtotal = 0;
                $orderItems = [];
                
                foreach ($input['items'] as $item) {
                    if (isset($item['menu_item_id']) && isset($item['quantity']) && isset($item['price'])) {
                        $itemSubtotal = $item['price'] * $item['quantity'];
                        $subtotal += $itemSubtotal;
                        
                        $orderItems[] = [
                            'menu_item_id' => $item['menu_item_id'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                            'notes' => isset($item['notes']) ? $item['notes'] : '',
                            'subtotal' => $itemSubtotal
                        ];
                    }
                }
                
                // Calculate tax and total
                $taxRate = isset($input['tax_rate']) ? $input['tax_rate'] : 0.05; // 5% tax default
                $tax = $subtotal * $taxRate;
                $total = $subtotal + $tax;
                
                // Prepare order data
                $orderData = [
                    'order_id' => $orderId,
                    'table_id' => $input['table_id'],
                    'staff_id' => $input['staff_id'],
                    'status' => isset($input['status']) ? $input['status'] : 'pending',
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'payment_method' => isset($input['payment_method']) ? $input['payment_method'] : 'cash'
                ];
                
                // Add tip if provided
                if (isset($input['tip'])) {
                    $orderData['tip'] = $input['tip'];
                    $orderData['total'] += $input['tip'];
                }
                
                // Create the order
                $newOrderId = $orderObj->createOrder($orderData, $orderItems);
                
                if ($newOrderId) {
                    // Update table status to 'occupied'
                    $tableObj->updateStatus($input['table_id'], 'occupied');
                    
                    $response['success'] = true;
                    $response['message'] = 'Order created successfully';
                    $response['data'] = [
                        'id' => $newOrderId,
                        'order_id' => $orderId
                    ];
                } else {
                    $response['message'] = 'Failed to create order';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
        } elseif ($action === 'update' && isset($input['id'])) {
            // Remove any admin-only restrictions for updating orders
            // Allow all logged-in users to update orders
            
            $updateData = [];
            
            // Only allow updating specific fields
            $allowedFields = ['status', 'payment_method', 'tip'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }
            }
            
            // If tip is updated, recalculate total
            if (isset($updateData['tip'])) {
                $order = $orderObj->getOrder($input['id']);
                if ($order) {
                    $updateData['total'] = $order['subtotal'] + $order['tax'] + $updateData['tip'];
                }
            }
            
            if (!empty($updateData)) {
                $result = $orderObj->updateOrder($input['id'], $updateData);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Order updated successfully';
                    
                    // If status changed to 'completed', update the table status to available
                    if (isset($updateData['status']) && $updateData['status'] === 'completed') {
                        $order = $orderObj->getOrder($input['id']);
                        if ($order) {
                            $tableObj->updateStatus($order['table_id'], 'available');
                        }
                    }
                } else {
                    $response['message'] = 'Failed to update order';
                }
            } else {
                $response['message'] = 'No data to update';
            }
        } elseif ($action === 'delete' && isset($input['id'])) {
            // Delete order - requires admin privileges
            if (!isAdmin()) {
                $response['message'] = 'Unauthorized action';
            } else {
                // Get order to retrieve table ID before deletion
                $order = $orderObj->getOrder($input['id']);
                $tableId = $order ? $order['table_id'] : null;
                
                $result = $orderObj->deleteOrder($input['id']);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Order deleted successfully';
                    
                    // If there was a table, update its status
                    if ($tableId) {
                        $tableObj->updateStatus($tableId, 'available');
                    }
                } else {
                    $response['message'] = 'Failed to delete order';
                }
            }
        } else {
            $response['message'] = 'Invalid action';
        }
    } else {
        $response['message'] = 'Invalid request method';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);