<?php
// classes/Order.php
// Order class for managing orders

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get an order by ID
     * @param int $id The order ID
     * @return array|null The order data or null if not found
     */
    public function getOrder($id) {
        return $this->db->selectOne("SELECT * FROM orders WHERE id = ?", [$id]);
    }
    
    /**
     * Get an order with all its details
     * @param int $id The order ID
     * @return array|null The order data with details or null if not found
     */
    public function getOrderWithDetails($id) {
        $order = $this->getOrder($id);
        
        if (!$order) {
            return null;
        }
        
        // Get the table and staff information
        $table = $this->db->selectOne("SELECT * FROM tables WHERE id = ?", [$order['table_id']]);
        $staff = $this->db->selectOne("SELECT * FROM staff WHERE id = ?", [$order['staff_id']]);
        
        // Get the order items
        $query = "
            SELECT oi.*, mi.name as menu_item_name, mi.price as menu_item_price, mi.icon as menu_item_icon, 
                   mi.category_id, c.name as category_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN categories c ON mi.category_id = c.id
            WHERE oi.order_id = ?
        ";
        $items = $this->db->select($query, [$order['id']]);
        
        // Combine all the data
        $orderWithDetails = $order;
        $orderWithDetails['table'] = $table;
        $orderWithDetails['staff'] = $staff;
        $orderWithDetails['items'] = $items;
        
        return $orderWithDetails;
    }
    
    /**
     * Get recent orders with basic details
     * @param int $limit The maximum number of orders to return
     * @return array The list of recent orders
     */
    public function getRecentOrders($limit = 5) {
        $query = "
            SELECT o.*, t.name as table_name, s.name as staff_name
            FROM orders o
            JOIN tables t ON o.table_id = t.id
            JOIN staff s ON o.staff_id = s.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ";
        
        return $this->db->select($query, [$limit]);
    }
    
    /**
     * Get all orders with basic details
     * @return array The list of all orders
     */
    public function getAllOrders() {
        $query = "
            SELECT o.*, t.name as table_name, s.name as staff_name
            FROM orders o
            JOIN tables t ON o.table_id = t.id
            JOIN staff s ON o.staff_id = s.id
            ORDER BY o.created_at DESC
        ";
        
        return $this->db->select($query);
    }
    
    /**
     * Create a new order
     * @param array $orderData The order data
     * @param array $items The order items
     * @return int|false The new order ID or false on failure
     */
    public function createOrder($orderData, $items) {
        try {
            $this->db->beginTransaction();
            
            // Insert order
            $orderId = $this->db->insert('orders', $orderData);
            
            if (!$orderId) {
                throw new Exception("Failed to insert order");
            }
            
            // Insert order items
            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                $result = $this->db->insert('order_items', $item);
                
                if (!$result) {
                    throw new Exception("Failed to insert order item");
                }
                
                // If tracking inventory, update stock
                $menuItem = $this->db->selectOne("SELECT * FROM menu_items WHERE id = ?", [$item['menu_item_id']]);
                if ($menuItem && $menuItem['track_inventory']) {
                    $newStock = $menuItem['stock_quantity'] - $item['quantity'];
                    $this->db->update('menu_items', 
                        ['stock_quantity' => $newStock], 
                        'id = ?', 
                        [$item['menu_item_id']]
                    );
                    
                    // Record inventory change in history
                    $inventoryHistoryData = [
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity_change' => -$item['quantity'],
                        'reason' => "Order #{$orderData['order_id']}",
                        'previous_stock' => $menuItem['stock_quantity'],
                        'new_stock' => $newStock,
                        'user_id' => $_SESSION['user_id'] ?? 1, // Default to admin if no user in session
                    ];
                    
                    $this->db->insert('inventory_history', $inventoryHistoryData);
                }
            }
            
            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an order
     * @param int $id The order ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function updateOrder($id, $data) {
        $affectedRows = $this->db->update('orders', $data, 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Delete an order and its items
     * @param int $id The order ID
     * @return bool True on success, false on failure
     */
    public function deleteOrder($id) {
        try {
            $this->db->beginTransaction();
            
            // First delete all order items
            $this->db->delete('order_items', 'order_id = ?', [$id]);
            
            // Then delete the order
            $result = $this->db->delete('orders', 'id = ?', [$id]);
            
            $this->db->commit();
            return $result > 0;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the total number of orders
     * @return int The total number of orders
     */
    public function getTotalOrders() {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM orders");
        return $result ? $result['count'] : 0;
    }
    
    /**
     * Get the total revenue from all orders
     * @return float The total revenue
     */
    public function getTotalRevenue() {
        $result = $this->db->selectOne("SELECT SUM(total) as total FROM orders");
        return $result ? floatval($result['total']) : 0;
    }
    
    /**
     * Generate a new order ID
     * @return string The new order ID (e.g., #ORD-5186)
     */
    public function generateOrderId() {
        // Get the current highest order number
        $query = "SELECT SUBSTRING_INDEX(order_id, '-', -1) as last_num FROM orders ORDER BY id DESC LIMIT 1";
        $result = $this->db->selectOne($query);
        
        $lastNum = $result ? intval($result['last_num']) : 5181; // Start at 5182 if no orders yet
        $newNum = $lastNum + 1;
        
        return "#ORD-{$newNum}";
    }
    
    /**
     * Check if a table is available
     * @param int $tableId The table ID
     * @return bool True if the table is available, false otherwise
     */
    public function isTableAvailable($tableId) {
        $table = $this->db->selectOne("SELECT status FROM tables WHERE id = ?", [$tableId]);
        return $table && $table['status'] === 'available';
    }
    
    /**
     * Update table status
     * @param int $tableId The table ID
     * @param string $status The new status
     * @return bool True on success, false on failure
     */
    public function updateTableStatus($tableId, $status) {
        $affectedRows = $this->db->update('tables', ['status' => $status], 'id = ?', [$tableId]);
        return $affectedRows > 0;
    }
    
    /**
     * Get order count by status
     * @param string $status The status to count
     * @return int The number of orders with the given status
     */
    public function getOrderCountByStatus($status) {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM orders WHERE status = ?", [$status]);
        return $result ? $result['count'] : 0;
    }
    
    /**
     * Get daily revenue
     * @param string $date The date to get revenue for (Y-m-d format)
     * @return float The revenue for the given date
     */
    public function getDailyRevenue($date) {
        $query = "SELECT SUM(total) as daily_revenue FROM orders WHERE DATE(created_at) = ?";
        $result = $this->db->selectOne($query, [$date]);
        return $result ? floatval($result['daily_revenue']) : 0;
    }
}