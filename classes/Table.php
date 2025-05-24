<?php
// classes/Table.php
// Table class for managing tables

class Table {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get a table by ID
     * @param int $id The table ID
     * @return array|null The table data or null if not found
     */
    public function getTable($id) {
        return $this->db->selectOne("SELECT * FROM tables WHERE id = ?", [$id]);
    }
    
    /**
     * Get all tables
     * @return array The list of all tables
     */
    public function getAllTables() {
        return $this->db->select("SELECT * FROM tables ORDER BY name");
    }
    
    /**
     * Get available tables
     * @return array The list of available tables
     */
    public function getAvailableTables() {
        return $this->db->select("SELECT * FROM tables WHERE status = 'available' ORDER BY name");
    }
    
    /**
     * Get occupied tables
     * @return array The list of occupied tables
     */
    public function getOccupiedTables() {
        return $this->db->select("SELECT * FROM tables WHERE status = 'occupied' ORDER BY name");
    }
    
    /**
     * Create a new table
     * @param array $data The table data
     * @return int|false The new table ID or false on failure
     */
    public function createTable($data) {
        return $this->db->insert('tables', $data);
    }
    
    /**
     * Update a table
     * @param int $id The table ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function updateTable($id, $data) {
        $affectedRows = $this->db->update('tables', $data, 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Delete a table
     * @param int $id The table ID
     * @return bool True on success, false on failure
     */
    public function deleteTable($id) {
        // Check if the table has any orders
        $query = "SELECT COUNT(*) as count FROM orders WHERE table_id = ?";
        $result = $this->db->selectOne($query, [$id]);
        
        if ($result && $result['count'] > 0) {
            return false; // Cannot delete a table with orders
        }
        
        $affectedRows = $this->db->delete('tables', 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Update table status
     * @param int $id The table ID
     * @param string $status The new status
     * @return bool True on success, false on failure
     */
    public function updateStatus($id, $status) {
        if (!in_array($status, ['available', 'occupied', 'reserved'])) {
            return false;
        }
        
        return $this->updateTable($id, ['status' => $status]);
    }
    
    /**
     * Get tables with current order information
     * @return array The list of tables with order information
     */
    public function getTablesWithOrders() {
        $query = "
            SELECT t.*, 
                   o.id as current_order_id, 
                   o.order_id as current_order_number,
                   o.status as order_status,
                   o.created_at as order_time,
                   s.name as server_name
            FROM tables t
            LEFT JOIN (
                SELECT * FROM orders 
                WHERE id IN (
                    SELECT MAX(id) FROM orders GROUP BY table_id
                )
            ) o ON t.id = o.table_id
            LEFT JOIN staff s ON o.staff_id = s.id
            ORDER BY t.name
        ";
        
        return $this->db->select($query);
    }
    
    /**
     * Get table utilization statistics
     * @param string $date The date to get statistics for (Y-m-d format)
     * @return array Statistics about table utilization
     */
    public function getTableUtilization($date) {
        // Get the total number of tables
        $totalTables = count($this->getAllTables());
        
        // Get the number of distinct tables used on the given date
        $usedTablesQuery = "
            SELECT COUNT(DISTINCT table_id) as count
            FROM orders
            WHERE DATE(created_at) = ?
        ";
        $usedTablesResult = $this->db->selectOne($usedTablesQuery, [$date]);
        $usedTables = $usedTablesResult ? $usedTablesResult['count'] : 0;
        
        // Calculate utilization percentage
        $utilizationRate = $totalTables > 0 ? ($usedTables / $totalTables) * 100 : 0;
        
        // Get orders per table
        $ordersPerTableQuery = "
            SELECT table_id, COUNT(*) as orders_count
            FROM orders
            WHERE DATE(created_at) = ?
            GROUP BY table_id
            ORDER BY orders_count DESC
        ";
        $ordersPerTable = $this->db->select($ordersPerTableQuery, [$date]);
        
        // Get table with most orders
        $mostUsedTable = !empty($ordersPerTable) ? $ordersPerTable[0] : null;
        if ($mostUsedTable) {
            $tableDetails = $this->getTable($mostUsedTable['table_id']);
            $mostUsedTable['table_name'] = $tableDetails ? $tableDetails['name'] : 'Unknown';
        }
        
        return [
            'total_tables' => $totalTables,
            'used_tables' => $usedTables,
            'utilization_rate' => $utilizationRate,
            'orders_per_table' => $ordersPerTable,
            'most_used_table' => $mostUsedTable
        ];
    }
}