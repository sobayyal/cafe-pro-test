<?php
// classes/User.php
// User class for authentication and user management

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get a user by ID
     * @param int $id The user ID
     * @return array|null The user data or null if not found
     */
    public function getUser($id) {
        return $this->db->selectOne("SELECT id, username, name, role FROM users WHERE id = ?", [$id]);
    }
    
    /**
     * Get a user by username
     * @param string $username The username
     * @return array|null The user data or null if not found
     */
    public function getUserByUsername($username) {
        return $this->db->selectOne("SELECT * FROM users WHERE username = ?", [$username]);
    }
    
    /**
     * Create a new user
     * @param string $username The username
     * @param string $password The password (will be hashed)
     * @param string $name The user's full name
     * @param string $role The user's role (admin or staff)
     * @return int|false The new user ID or false on failure
     */
    public function createUser($username, $password, $name, $role = 'staff') {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $data = [
            'username' => $username,
            'password' => $hashedPassword,
            'name' => $name,
            'role' => $role
        ];
        
        return $this->db->insert('users', $data);
    }
    
    /**
     * Update a user
     * @param int $id The user ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    public function updateUser($id, $data) {
        // If password is being updated, hash it
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $affectedRows = $this->db->update('users', $data, 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Delete a user
     * @param int $id The user ID
     * @return bool True on success, false on failure
     */
    public function deleteUser($id) {
        $affectedRows = $this->db->delete('users', 'id = ?', [$id]);
        return $affectedRows > 0;
    }
    
    /**
     * Get all users
     * @return array The list of users
     */
    public function getAllUsers() {
        return $this->db->select("SELECT id, username, name, role FROM users ORDER BY name");
    }
    
    /**
     * Authenticate a user
     * @param string $username The username
     * @param string $password The password
     * @return array|false The user data on success, false on failure
     */
    public function login($username, $password) {
        try {
            // Get the database connection directly
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Perform a direct query to ensure we get all fields
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user exists and has a password
            if (!$user || empty($user['password'])) {
                return false;
            }
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Remove password from user array before returning
                unset($user['password']);
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a user has admin privileges
     * @param int $userId The user ID
     * @return bool True if the user is an admin, false otherwise
     */
    public function isAdmin($userId) {
        $user = $this->getUser($userId);
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Change a user's password
     * @param int $userId The user ID
     * @param string $currentPassword The current password
     * @param string $newPassword The new password
     * @return bool True on success, false on failure
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return false;
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $affectedRows = $this->db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
        
        return $affectedRows > 0;
    }
}
