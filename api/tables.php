<?php
// api/tables.php
// API endpoint for table operations

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
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

// Table management operations require admin privileges
$requiresAdmin = ['create', 'update', 'delete'];
if (in_array($_GET['action'] ?? '', $requiresAdmin) && !isAdmin()) {
    $response['message'] = 'Unauthorized action';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initialize classes
$tableObj = new Table();

// Process request based on method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($method === 'GET') {
        // GET requests - retrieve data
        if ($action === 'getAll') {
            // Get all tables
            $tables = $tableObj->getAllTables();
            $response['success'] = true;
            $response['data'] = $tables;
        } elseif ($action === 'getAvailable') {
            // Get available tables
            $tables = $tableObj->getAvailableTables();
            $response['success'] = true;
            $response['data'] = $tables;
        } elseif ($action === 'getOccupied') {
            // Get occupied tables
            $tables = $tableObj->getOccupiedTables();
            $response['success'] = true;
            $response['data'] = $tables;
        } elseif ($action === 'getTable' && isset($_GET['id'])) {
            // Get specific table
            $table = $tableObj->getTable($_GET['id']);
            if ($table) {
                $response['success'] = true;
                $response['data'] = $table;
            } else {
                $response['message'] = 'Table not found';
            }
        } elseif ($action === 'getTablesWithOrders') {
            // Get tables with current order information
            $tables = $tableObj->getTablesWithOrders();
            $response['success'] = true;
            $response['data'] = $tables;
        } elseif ($action === 'getUtilization') {
            // Get table utilization stats
            $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
            $utilization = $tableObj->getTableUtilization($date);
            $response['success'] = true;
            $response['data'] = $utilization;
        } else {
            $response['message'] = 'Invalid action';
        }
    } elseif ($method === 'POST') {
        // POST requests - create, update, delete

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
            // Create table - admin only
            if (isset($input['name']) && isset($input['capacity'])) {
                $tableData = [
                    'name' => $input['name'],
                    'capacity' => $input['capacity'],
                    'status' => isset($input['status']) ? $input['status'] : 'available'
                ];
                
                $result = $tableObj->createTable($tableData);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Table added successfully';
                    $response['data'] = ['id' => $result];
                } else {
                    $response['message'] = 'Failed to add table';
                }
            } else {
                $response['message'] = 'Name and capacity are required';
            }
        } elseif ($action === 'update' && isset($input['id'])) {
            // Update table - admin only
            $tableData = [];
            
            if (isset($input['name'])) {
                $tableData['name'] = $input['name'];
            }
            
            if (isset($input['capacity'])) {
                $tableData['capacity'] = $input['capacity'];
            }
            
            if (!empty($tableData)) {
                $result = $tableObj->updateTable($input['id'], $tableData);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Table updated successfully';
                } else {
                    $response['message'] = 'Failed to update table';
                }
            } else {
                $response['message'] = 'No data to update';
            }
        } elseif ($action === 'updateStatus' && isset($input['id']) && isset($input['status'])) {
            // Update table status - this can be done by any logged-in user
            $result = $tableObj->updateStatus($input['id'], $input['status']);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Table status updated successfully';
            } else {
                $response['message'] = 'Failed to update table status';
            }
        } elseif ($action === 'delete' && isset($input['id'])) {
            // Delete table - admin only
            $result = $tableObj->deleteTable($input['id']);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Table deleted successfully';
            } else {
                $response['message'] = 'Failed to delete table. It may have associated orders.';
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