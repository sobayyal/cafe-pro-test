<?php
// classes/Database.php
// Database class for handling database connections and queries

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor to prevent direct object creation
     * Establishes the database connection
     */
    private function __construct() {
        require_once dirname(__DIR__) . '/config/database.php';
        $this->connection = getDbConnection();
    }
    
    /**
     * Static method to get the database instance (Singleton pattern)
     * @return Database The database instance
     */
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Get the database connection
     * @return PDO The PDO database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query and return the result
     * @param string $query The SQL query to execute
     * @param array $params The parameters to bind to the query
     * @return PDOStatement The result of the query
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log error or throw exception as needed
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute a SELECT query and return all results
     * @param string $query The SQL query to execute
     * @param array $params The parameters to bind to the query
     * @return array The query results
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database select error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Execute a SELECT query and return the first result
     * @param string $query The SQL query to execute
     * @param array $params The parameters to bind to the query
     * @return array|null The first result or null if not found
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Database selectOne error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Insert data into a table
     * @param string $table The table to insert into
     * @param array $data The data to insert as column => value
     * @return int|false The last inserted ID or false on failure
     */
    public function insert($table, $data) {
        try {
            // Build column and placeholder lists
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->query($query, array_values($data));
            
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database insert error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update data in a table
     * @param string $table The table to update
     * @param array $data The data to update as column => value
     * @param string $where The WHERE clause
     * @param array $params The parameters for the WHERE clause
     * @return int The number of affected rows
     */
    public function update($table, $data, $where, $params = []) {
        try {
            // Build SET clause
            $set = [];
            foreach (array_keys($data) as $column) {
                $set[] = "{$column} = ?";
            }
            $setClause = implode(', ', $set);
            
            $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            
            // Combine data values and where params
            $allParams = array_merge(array_values($data), $params);
            
            $stmt = $this->query($query, $allParams);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete data from a table
     * @param string $table The table to delete from
     * @param string $where The WHERE clause
     * @param array $params The parameters for the WHERE clause
     * @return int The number of affected rows
     */
    public function delete($table, $where, $params = []) {
        try {
            $query = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->query($query, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database delete error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
}