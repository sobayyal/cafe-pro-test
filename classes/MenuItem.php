<?php
// classes/MenuItem.php
// MenuItem class for managing menu items

class MenuItem {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get a menu item by ID
     * @param int $id The menu item ID
     * @return array|null The menu item data or null if not found
     */
    public function getMenuItem($id) {
        return $this->db->selectOne("SELECT * FROM menu_items WHERE id = ?", [$id]);
    }
    
    /**
     * Get a menu item with its category
     * @param int $id The menu item ID
     * @return array|null The menu item data with category or null if not found
     */
    public function getMenuItemWithCategory($id) {
        $query = "
            SELECT mi.*, c.name as category_name, c.icon as category_icon
            FROM menu_items mi
            JOIN categories c ON mi.category_id = c.id
            WHERE mi.id = ?
        ";
        
        return $this->db->selectOne($query, [$id]);
    }
    
    /**
     * Get all menu items
     * @return array The list of all menu items
     */
    public function getAllMenuItems() {
        $query = "
            SELECT mi.*, c.name as category_name
            FROM menu_items mi
            JOIN categories c ON mi.category_id = c.id
            ORDER BY mi.name
        ";
        
        return $this->db->select($query);
    }
    
    /**
     * Get all active menu items
     * @return array The list of active menu items
     */
    public function getActiveMenuItems() {
        $query = "
            SELECT mi.*, c.name as category_name
            FROM menu_items mi
            JOIN categories c ON mi.category_id = c.id
            WHERE mi.active = 1
            ORDER BY mi.name
        ";
        
        return $this->db->select($query);
    }
    
    /**
     * Get menu items by category
     * @param int $categoryId The category ID
     * @return array The list of menu items in the given category
     */
    public function getMenuItemsByCategory($categoryId) {
        $query = "
            SELECT mi.*, c.name as category_name
            FROM menu_items mi
            JOIN categories c ON mi.category_id = c.id
            WHERE mi.category_id = ?
            ORDER BY mi.name
        ";
        
        return $this->db->select($query, [$categoryId]);
    }
    
    /**
     * Create a new menu item
     * @param array $data The menu item data
     * @return int|false The new menu item ID or false on failure
     */
    public function createMenuItem($data) {
        return $this->db->insert('menu_items', $data);
    }
    
    /**
     * Update a menu item
     * @param int $id The menu item ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function updateMenuItem($id, $data) {
        $affectedRows = $this->db->update('menu_items', $data, 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Delete a menu item
     * @param int $id The menu item ID
     * @return bool True on success, false on failure
     */
    public function deleteMenuItem($id) {
        $affectedRows = $this->db->delete('menu_items', 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Get the top menu items by sales
     * @param int $limit The maximum number of items to return
     * @return array The list of top menu items
     */
    public function getTopMenuItems($limit = 5) {
        // In a real application, this would calculate based on actual sales data
        // For this demo, we'll use a simulated approach
        
        $query = "
            SELECT mi.*, c.name as category_name,
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.menu_item_id = mi.id) as sold_count,
                   FLOOR(RAND() * 25 - 10) as trend
            FROM menu_items mi
            JOIN categories c ON mi.category_id = c.id
            WHERE mi.active = 1
            ORDER BY sold_count DESC
            LIMIT ?
        ";
        
        return $this->db->select($query, [$limit]);
    }
    
    /**
     * Update menu item stock
     * @param int $id The menu item ID
     * @param int $quantity The quantity to add (positive) or remove (negative)
     * @param string $reason The reason for the stock change
     * @param int $userId The user making the change
     * @return array|false The updated menu item or false on failure
     */
    public function updateStock($id, $quantity, $reason, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get the current menu item
            $menuItem = $this->getMenuItem($id);
            if (!$menuItem) {
                throw new Exception("Menu item not found");
            }
            
            // Calculate new stock quantity
            $previousStock = $menuItem['stock_quantity'] ?? 0;
            $newStock = $previousStock + $quantity;
            
            // Ensure stock doesn't go below zero
            if ($newStock < 0) {
                $newStock = 0;
            }
            
            // Update the menu item
            $updateData = [
                'stock_quantity' => $newStock,
                'track_inventory' => 1 // Enable inventory tracking if updating stock
            ];
            
            $updated = $this->db->update('menu_items', $updateData, 'id = ?', [$id]);
            
            if (!$updated) {
                throw new Exception("Failed to update menu item stock");
            }
            
            // Record the inventory change in history
            $inventoryHistoryData = [
                'menu_item_id' => $id,
                'quantity_change' => $quantity,
                'reason' => $reason,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'user_id' => $userId
            ];
            
            $result = $this->db->insert('inventory_history', $inventoryHistoryData);
            
            if (!$result) {
                throw new Exception("Failed to record inventory history");
            }
            
            $this->db->commit();
            
            // Return the updated menu item
            return $this->getMenuItem($id);
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Stock update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check menu item stock
     * @param int $id The menu item ID
     * @return array Information about the item's stock status
     */
    public function checkStock($id) {
        $menuItem = $this->getMenuItem($id);
        
        if (!$menuItem) {
            return ['available' => false, 'quantity' => 0];
        }
        
        if (!$menuItem['track_inventory']) {
            return ['available' => true, 'quantity' => -1]; // -1 indicates unlimited stock
        }
        
        $quantity = $menuItem['stock_quantity'] ?? 0;
        return [
            'available' => $quantity > 0,
            'quantity' => $quantity
        ];
    }
    
    /**
     * Get inventory history for a menu item
     * @param int|null $menuItemId The menu item ID (optional)
     * @return array The inventory history
     */
    public function getInventoryHistory($menuItemId = null) {
        if ($menuItemId) {
            $query = "
                SELECT ih.*, mi.name as menu_item_name, u.name as user_name
                FROM inventory_history ih
                JOIN menu_items mi ON ih.menu_item_id = mi.id
                JOIN users u ON ih.user_id = u.id
                WHERE ih.menu_item_id = ?
                ORDER BY ih.created_at DESC
            ";
            return $this->db->select($query, [$menuItemId]);
        } else {
            $query = "
                SELECT ih.*, mi.name as menu_item_name, u.name as user_name
                FROM inventory_history ih
                JOIN menu_items mi ON ih.menu_item_id = mi.id
                JOIN users u ON ih.user_id = u.id
                ORDER BY ih.created_at DESC
            ";
            return $this->db->select($query);
        }
    }
    
    /**
     * Get all categories
     * @return array The list of all categories
     */
    public function getAllCategories() {
        return $this->db->select("SELECT * FROM categories ORDER BY name");
    }
    
    /**
     * Get a category by ID
     * @param int $id The category ID
     * @return array|null The category data or null if not found
     */
    public function getCategory($id) {
        return $this->db->selectOne("SELECT * FROM categories WHERE id = ?", [$id]);
    }
    
    /**
     * Create a new category
     * @param array $data The category data
     * @return int|false The new category ID or false on failure
     */
    public function createCategory($data) {
        return $this->db->insert('categories', $data);
    }
    
    /**
     * Update a category
     * @param int $id The category ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function updateCategory($id, $data) {
        $affectedRows = $this->db->update('categories', $data, 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Delete a category
     * @param int $id The category ID
     * @return bool True on success, false on failure
     */
    public function deleteCategory($id) {
        // Check if the category has menu items
        $menuItems = $this->getMenuItemsByCategory($id);
        if (!empty($menuItems)) {
            return false; // Cannot delete a category with menu items
        }
        
        $affectedRows = $this->db->delete('categories', 'id = ?', [$id]);
        return $affectedRows > 0;
    }
}