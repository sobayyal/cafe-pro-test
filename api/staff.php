<?php
// api/staff.php
// API endpoint for staff operations

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Staff.php';

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

// Most staff operations require admin privileges
if (!isAdmin() && $_SERVER['REQUEST_METHOD'] != 'GET') {
    $response['message'] = 'Unauthorized action';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initialize classes
$staffObj = new Staff();

// Process request based on method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($method === 'GET') {
        // GET requests - retrieve data
        if ($action === 'getAll') {
            // Get all staff members
            $staff = $staffObj->getAllStaff();
            $response['success'] = true;
            $response['data'] = $staff;
        } elseif ($action === 'getActive') {
            // Get active staff members
            $staff = $staffObj->getActiveStaff();
            $response['success'] = true;
            $response['data'] = $staff;
        } elseif ($action === 'getStaff' && isset($_GET['id'])) {
            // Get specific staff member
            $staff = $staffObj->getStaff($_GET['id']);
            if ($staff) {
                $response['success'] = true;
                $response['data'] = $staff;
            } else {
                $response['message'] = 'Staff member not found';
            }
        } elseif ($action === 'getTopStaff') {
            // Get top performing staff
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
            $staff = $staffObj->getTopStaff($limit);
            $response['success'] = true;
            $response['data'] = $staff;
        } elseif ($action === 'getPerformance' && isset($_GET['id'])) {
            // Get staff performance metrics - requires admin privileges
            if (!isAdmin()) {
                $response['message'] = 'Unauthorized action';
            } else {
                $performance = $staffObj->getStaffPerformance($_GET['id']);
                $response['success'] = true;
                $response['data'] = $performance;
            }
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
            // Create staff member
            if (isset($input['name'])) {
                $staffData = [
                    'name' => $input['name'],
                    'active' => isset($input['active']) ? $input['active'] : 1
                ];
                
                $result = $staffObj->createStaff($staffData);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Staff member added successfully';
                    $response['data'] = ['id' => $result];
                } else {
                    $response['message'] = 'Failed to add staff member';
                }
            } else {
                $response['message'] = 'Name is required';
            }
        } elseif ($action === 'update' && isset($input['id'])) {
            // Update staff member
            $staffData = [];
            
            if (isset($input['name'])) {
                $staffData['name'] = $input['name'];
            }
            
            if (isset($input['active'])) {
                $staffData['active'] = $input['active'] ? 1 : 0;
            }
            
            if (!empty($staffData)) {
                $result = $staffObj->updateStaff($input['id'], $staffData);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Staff member updated successfully';
                } else {
                    $response['message'] = 'Failed to update staff member';
                }
            } else {
                $response['message'] = 'No data to update';
            }
        } elseif ($action === 'delete' && isset($input['id'])) {
            // Delete staff member
            $result = $staffObj->deleteStaff($input['id']);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Staff member removed successfully';
            } else {
                $response['message'] = 'Failed to remove staff member. They may have associated orders.';
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