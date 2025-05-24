<?php
// classes/Staff.php
// Staff class for managing staff members

class Staff {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get a staff member by ID
     * @param int $id The staff ID
     * @return array|null The staff data or null if not found
     */
    public function getStaff($id) {
        return $this->db->selectOne("SELECT * FROM staff WHERE id = ?", [$id]);
    }
    
    /**
     * Get all staff members
     * @return array The list of all staff members
     */
    public function getAllStaff() {
        return $this->db->select("SELECT * FROM staff ORDER BY name");
    }
    
    /**
     * Get all active staff members
     * @return array The list of active staff members
     */
    public function getActiveStaff() {
        return $this->db->select("SELECT * FROM staff WHERE active = 1 ORDER BY name");
    }
    
    /**
     * Get the count of active staff members
     * @return int The number of active staff members
     */
    public function getActiveStaffCount() {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM staff WHERE active = 1");
        return $result ? $result['count'] : 0;
    }
    
    /**
     * Create a new staff member
     * @param array $data The staff data
     * @return int|false The new staff ID or false on failure
     */
    public function createStaff($data) {
        return $this->db->insert('staff', $data);
    }
    
    /**
     * Update a staff member
     * @param int $id The staff ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function updateStaff($id, $data) {
        $affectedRows = $this->db->update('staff', $data, 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Delete a staff member
     * @param int $id The staff ID
     * @return bool True on success, false on failure
     */
    public function deleteStaff($id) {
        // Check if the staff member has any orders
        $query = "SELECT COUNT(*) as count FROM orders WHERE staff_id = ?";
        $result = $this->db->selectOne($query, [$id]);
        
        if ($result && $result['count'] > 0) {
            // Staff member has orders, make them inactive instead of deleting
            return $this->updateStaff($id, ['active' => 0]);
        }
        
        $affectedRows = $this->db->delete('staff', 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Get staff performance statistics
     * @param int $staffId The staff ID
     * @return array Statistics about the staff member's performance
     */
    public function getStaffPerformance($staffId) {
        // Get total orders served
        $totalOrdersQuery = "SELECT COUNT(*) as total FROM orders WHERE staff_id = ?";
        $totalOrdersResult = $this->db->selectOne($totalOrdersQuery, [$staffId]);
        $totalOrders = $totalOrdersResult ? $totalOrdersResult['total'] : 0;
        
        // Get total revenue generated
        $totalRevenueQuery = "SELECT SUM(total) as revenue FROM orders WHERE staff_id = ?";
        $totalRevenueResult = $this->db->selectOne($totalRevenueQuery, [$staffId]);
        $totalRevenue = $totalRevenueResult ? floatval($totalRevenueResult['revenue']) : 0;
        
        // Get average order value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        // Get orders by status
        $statusCounts = [];
        $statusQuery = "
            SELECT status, COUNT(*) as count 
            FROM orders 
            WHERE staff_id = ? 
            GROUP BY status
        ";
        $statusResults = $this->db->select($statusQuery, [$staffId]);
        
        foreach ($statusResults as $result) {
            $statusCounts[$result['status']] = $result['count'];
        }
        
        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'status_counts' => $statusCounts
        ];
    }
    
    /**
     * Get the top performing staff members
     * @param int $limit The maximum number of staff members to return
     * @return array The list of top performing staff members
     */
    public function getTopStaff($limit = 5) {
        $query = "
            SELECT s.*, COUNT(o.id) as orders_count, SUM(o.total) as total_revenue
            FROM staff s
            LEFT JOIN orders o ON s.id = o.staff_id
            WHERE s.active = 1
            GROUP BY s.id
            ORDER BY orders_count DESC
            LIMIT ?
        ";
        
        return $this->db->select($query, [$limit]);
    }
}