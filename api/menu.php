<?php
// api/menu.php
// API endpoint for menu item operations

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/MenuItem.php';

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

// Initialize MenuItem class
$menuItemObj = new MenuItem();

// Process request based on method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($method === 'GET') {
        // GET requests - retrieve data
        if ($action === 'getAll') {
            // Get all menu items
            $items = $menuItemObj->getAllMenuItems();
            $response['success'] = true;
            $response['data'] = $items;
        } elseif ($action === 'getActive') {
            // Get active menu items
            $items = $menuItemObj->getActiveMenuItems();
            $response['success'] = true;
            $response['data'] = $items;
        } elseif ($action === 'getItem' && isset($_GET['id'])) {
            // Get specific menu item
            $item = $menuItemObj->getMenuItem($_GET['id']);
            if ($item) {
                $response['success'] = true;
                $response['data'] = $item;
            } else {
                $response['message'] = 'Menu item not found';
            }
        } elseif ($action === 'getCategories') {
            // Get all categories
            $categories = $menuItemObj->getAllCategories();
            $response['success'] = true;
            $response['data'] = $categories;
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
            // Create menu item
            if (isset($input['name']) && isset($input['price']) && isset($input['category_id'])) {
                $result = $menuItemObj->createMenuItem($input);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Menu item created successfully';
                    $response['data'] = ['id' => $result];
                } else {
                    $response['message'] = 'Failed to create menu item';
                }
            } else {
                $response['message'] = 'Missing required fields';
            }
        } elseif ($action === 'update' && isset($input['id'])) {
            // Update menu item
            $result = $menuItemObj->updateMenuItem($input['id'], $input);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Menu item updated successfully';
            } else {
                $response['message'] = 'Failed to update menu item';
            }
        } elseif ($action === 'delete' && isset($input['id'])) {
            // Delete menu item - requires admin privileges
            if (!isAdmin()) {
                $response['message'] = 'Unauthorized action';
            } else {
                $result = $menuItemObj->deleteMenuItem($input['id']);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Menu item deleted successfully';
                } else {
                    $response['message'] = 'Failed to delete menu item';
                }
            }
        } elseif ($action === 'createCategory') {
            // Create category - requires admin privileges
            if (!isAdmin()) {
                $response['message'] = 'Unauthorized action';
            } else if (isset($input['name'])) {
                $result = $menuItemObj->createCategory($input);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Category created successfully';
                    $response['data'] = ['id' => $result];
                } else {
                    $response['message'] = 'Failed to create category';
                }
            } else {
                $response['message'] = 'Missing category name';
            }
        } elseif ($action === 'updateStock' && isset($input['id']) && isset($input['quantity'])) {
            // Update stock quantity - requires admin privileges
            if (!isAdmin()) {
                $response['message'] = 'Unauthorized action';
            } else {
                $userId = getCurrentUserId();
                $reason = isset($input['reason']) ? $input['reason'] : 'Stock adjustment';
                
                $result = $menuItemObj->updateStock(
                    $input['id'],
                    $input['quantity'],
                    $reason,
                    $userId
                );
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Stock updated successfully';
                    $response['data'] = $result;
                } else {
                    $response['message'] = 'Failed to update stock';
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