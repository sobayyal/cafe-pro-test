<?php
// pages/menu-management.php
// Menu management page for the Cafe Management System

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/MenuItem.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Require login to access this page
requireLogin();

// Initialize classes
$menuItemObj = new MenuItem();

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Create new menu item
    if ($action == 'create') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $categoryId = intval($_POST['category_id']);
        $icon = trim($_POST['icon']);
        $active = isset($_POST['active']) ? 1 : 0;
        $trackInventory = isset($_POST['track_inventory']) ? 1 : 0;
        $stockQuantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
        
        if (!empty($name) && $price > 0 && $categoryId > 0) {
            $menuItemData = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'category_id' => $categoryId,
                'icon' => $icon,
                'active' => $active,
                'track_inventory' => $trackInventory,
                'stock_quantity' => $stockQuantity
            ];
            
            if ($menuItemObj->createMenuItem($menuItemData)) {
                setSessionMessage('success', 'Menu item added successfully.');
            } else {
                setSessionMessage('error', 'Failed to add menu item.');
            }
        } else {
            setSessionMessage('error', 'Please check your input. Name, price, and category are required.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Update menu item
    if ($action == 'update' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $categoryId = intval($_POST['category_id']);
        $icon = trim($_POST['icon']);
        $active = isset($_POST['active']) ? 1 : 0;
        $trackInventory = isset($_POST['track_inventory']) ? 1 : 0;
        $stockQuantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 0;
        
        if (!empty($name) && $price > 0 && $categoryId > 0) {
            $menuItemData = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'category_id' => $categoryId,
                'icon' => $icon,
                'active' => $active,
                'track_inventory' => $trackInventory,
                'stock_quantity' => $stockQuantity
            ];
            
            if ($menuItemObj->updateMenuItem($id, $menuItemData)) {
                setSessionMessage('success', 'Menu item updated successfully.');
            } else {
                setSessionMessage('error', 'Failed to update menu item.');
            }
        } else {
            setSessionMessage('error', 'Please check your input. Name, price, and category are required.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Delete menu item
    if ($action == 'delete' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        if ($menuItemObj->deleteMenuItem($id)) {
            setSessionMessage('success', 'Menu item deleted successfully.');
        } else {
            setSessionMessage('error', 'Failed to delete menu item.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Category actions
    if ($action == 'createCategory') {
        $categoryName = trim($_POST['name']);
        $categoryIcon = trim($_POST['icon']);
        
        if (!empty($categoryName)) {
            $categoryData = [
                'name' => $categoryName,
                'icon' => $categoryIcon
            ];
            
            if ($menuItemObj->createCategory($categoryData)) {
                setSessionMessage('success', 'Category added successfully.');
            } else {
                setSessionMessage('error', 'Failed to add category.');
            }
        } else {
            setSessionMessage('error', 'Category name cannot be empty.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Update category
    if ($action == 'updateCategory' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $icon = trim($_POST['icon']);
        
        if (!empty($name)) {
            $categoryData = [
                'name' => $name,
                'icon' => $icon
            ];
            
            if ($menuItemObj->updateCategory($id, $categoryData)) {
                setSessionMessage('success', 'Category updated successfully.');
            } else {
                setSessionMessage('error', 'Failed to update category.');
            }
        } else {
            setSessionMessage('error', 'Category name cannot be empty.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Delete category
    if ($action == 'deleteCategory' && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        if ($menuItemObj->deleteCategory($id)) {
            setSessionMessage('success', 'Category deleted successfully.');
        } else {
            setSessionMessage('error', 'Failed to delete category. It may have associated menu items.');
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get menu items and categories
$menuItems = $menuItemObj->getAllMenuItems();
$categories = $menuItemObj->getAllCategories();

// Set page title
$pageTitle = "Menu Management";
$showNotifications = true;

// Include header
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-column md:flex-row align-items-start md:align-items-center justify-content-between mb-4">
    <div>
        <h1 class="fs-2 fw-bold">Menu Management</h1>
        <p class="text-muted">Manage menu items and categories</p>
    </div>
    <div class="mt-3 md:mt-0 d-flex gap-2">
        <?php if (isAdmin()): ?>
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="ri-folder-add-line"></i>
            <span>Manage Categories</span>
        </button>
        <?php endif; ?>
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
            <i class="ri-add-line"></i>
            <span>Add Menu Item</span>
        </button>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="position-relative">
            <input type="text" id="searchMenuItems" class="form-control ps-4" placeholder="Search menu items...">
            <i class="ri-search-line position-absolute start-3 top-50 translate-middle-y text-muted"></i>
        </div>
    </div>
    <div class="col-md-4">
        <select id="categoryFilter" class="form-select">
            <option value="all">All Categories</option>
            <?php foreach ($categories as $category): ?>
            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Menu Items Grid -->
<div class="row g-4" id="menuItemsGrid">
    <?php if (empty($menuItems)): ?>
    <div class="col-12">
        <div class="alert alert-info">No menu items found. Add one to get started.</div>
    </div>
    <?php else: ?>
        <?php foreach ($menuItems as $item): ?>
        <div class="col-md-6 col-lg-4 menu-item-card" data-category="<?php echo $item['category_id']; ?>">
            <div class="card h-100 menu-item-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="h5 card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="card-text text-muted small mb-2" style="min-height: 3em;"><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></p>
                            <div class="d-flex align-items-center mt-2">
                                <span class="text-primary fw-medium"><?php echo CURRENCY . ' ' . number_format($item['price'], 2); ?></span>
                                <span class="badge bg-light text-muted ms-2"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            </div>
                        </div>
                        <?php
                        $iconColorClass = '';
                        switch ($item['category_id'] % 4) {
                            case 0:
                                $iconColorClass = 'text-primary';
                                break;
                            case 1:
                                $iconColorClass = 'text-secondary';
                                break;
                            case 2:
                                $iconColorClass = 'text-accent';
                                break;
                            case 3:
                                $iconColorClass = 'text-warning';
                                break;
                        }
                        ?>
                        <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="<?php echo htmlspecialchars($item['icon']); ?> <?php echo $iconColorClass; ?> fs-4"></i>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="badge <?php echo $item['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $item['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-secondary edit-menu-item" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editMenuItemModal" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($item['description'] ?: ''); ?>"
                                    data-price="<?php echo $item['price']; ?>"
                                    data-category-id="<?php echo $item['category_id']; ?>"
                                    data-icon="<?php echo htmlspecialchars($item['icon']); ?>"
                                    data-active="<?php echo $item['active']; ?>"
                                    data-track-inventory="<?php echo $item['track_inventory'] ?? 0; ?>"
                                    data-stock-quantity="<?php echo $item['stock_quantity'] ?? 0; ?>">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-menu-item"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteMenuItemModal" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Menu Item Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-labelledby="addMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMenuItemModalLabel">Add New Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="icon" class="form-label">Icon</label>
                            <select class="form-select" id="icon" name="icon">
                                <option value="ri-restaurant-line">General Food</option>
                                <option value="ri-cup-line">Hot Drink</option>
                                <option value="ri-goblet-line">Espresso</option>
                                <option value="ri-ice-cream-line">Cold Drink</option>
                                <option value="ri-cake-3-line">Cake</option>
                                <option value="ri-bread-line">Bakery</option>
                                <option value="ri-water-flash-line">Sheesha/Hookah</option>
                                <option value="ri-beer-line">Beer</option>
                                <option value="ri-glass-line">Cocktail</option>
                                <option value="ri-wine-line">Wine</option>
                                <option value="ri-sparkling-line">Sparkling Drink</option>
                                <option value="ri-juice-line">Juice</option>
                                <option value="ri-soda-line">Soda</option>
                                <option value="ri-cigarette-line">Smoking</option>
                                <option value="ri-tv-line">Entertainment</option>
                                <option value="ri-game-line">Games</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                <label class="form-check-label" for="active">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="track_inventory" name="track_inventory">
                                <label class="form-check-label" for="track_inventory">Track Inventory</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 stock-quantity-container d-none">
                        <label for="stock_quantity" class="form-label">Initial Stock Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Menu Item Modal -->
<div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-labelledby="editMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMenuItemModalLabel">Edit Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit-name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-price" class="form-label">Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo CURRENCY; ?></span>
                                <input type="number" class="form-control" id="edit-price" name="price" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit-description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit-category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit-category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-icon" class="form-label">Icon</label>
                            <select class="form-select" id="edit-icon" name="icon">
                                <option value="ri-restaurant-line">General Food</option>
                                <option value="ri-cup-line">Hot Drink</option>
                                <option value="ri-goblet-line">Espresso</option>
                                <option value="ri-ice-cream-line">Cold Drink</option>
                                <option value="ri-cake-3-line">Cake</option>
                                <option value="ri-bread-line">Bakery</option>
                                <option value="ri-water-flash-line">Sheesha/Hookah</option>
                                <option value="ri-beer-line">Beer</option>
                                <option value="ri-glass-line">Cocktail</option>
                                <option value="ri-wine-line">Wine</option>
                                <option value="ri-sparkling-line">Sparkling Drink</option>
                                <option value="ri-juice-line">Juice</option>
                                <option value="ri-soda-line">Soda</option>
                                <option value="ri-cigarette-line">Smoking</option>
                                <option value="ri-tv-line">Entertainment</option>
                                <option value="ri-game-line">Games</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit-active" name="active">
                                <label class="form-check-label" for="edit-active">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit-track_inventory" name="track_inventory">
                                <label class="form-check-label" for="edit-track_inventory">Track Inventory</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 edit-stock-quantity-container">
                        <label for="edit-stock_quantity" class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" id="edit-stock_quantity" name="stock_quantity" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Menu Item Modal -->
<div class="modal fade" id="deleteMenuItemModal" tabindex="-1" aria-labelledby="deleteMenuItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMenuItemModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-id">
                    
                    <p>Are you sure you want to delete <span id="delete-name" class="fw-bold"></span>?</p>
                    <p class="text-danger small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category Management Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-4">
                    <input type="hidden" name="action" value="createCategory">
                    
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label for="category-name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="category-name" name="name" required>
                        </div>
                        <div class="col-md-5">
                            <label for="category-icon" class="form-label">Icon</label>
                            <select class="form-select" id="category-icon" name="icon">
                                <option value="ri-restaurant-line">General Food</option>
                                <option value="ri-cup-line">Hot Drink</option>
                                <option value="ri-goblet-line">Espresso</option>
                                <option value="ri-ice-cream-line">Cold Drink</option>
                                <option value="ri-cake-3-line">Cake</option>
                                <option value="ri-bread-line">Bakery</option>
                                <option value="ri-water-flash-line">Sheesha/Hookah</option>
                                <option value="ri-beer-line">Beer</option>
                                <option value="ri-glass-line">Cocktail</option>
                                <option value="ri-wine-line">Wine</option>
                                <option value="ri-sparkling-line">Sparkling Drink</option>
                                <option value="ri-juice-line">Juice</option>
                                <option value="ri-soda-line">Soda</option>
                                <option value="ri-cigarette-line">Smoking</option>
                                <option value="ri-tv-line">Entertainment</option>
                                <option value="ri-game-line">Games</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </div>
                </form>

                <h6 class="mb-3">Existing Categories</h6>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Name</th>
                                <th>Icon</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No categories found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><i class="<?php echo htmlspecialchars($category['icon']); ?>"></i> <?php echo htmlspecialchars($category['icon']); ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary edit-category"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCategoryModal" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-icon="<?php echo htmlspecialchars($category['icon']); ?>">
                                                <i class="ri-pencil-line"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-category"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteCategoryModal" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="updateCategory">
                    <input type="hidden" name="id" id="edit-category-id">
                    
                    <div class="mb-3">
                        <label for="edit-category-name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit-category-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-category-icon" class="form-label">Icon</label>
                        <select class="form-select" id="edit-category-icon" name="icon">
                            <option value="ri-restaurant-line">General Food</option>
                            <option value="ri-cup-line">Hot Drink</option>
                            <option value="ri-goblet-line">Espresso</option>
                            <option value="ri-ice-cream-line">Cold Drink</option>
                            <option value="ri-cake-3-line">Cake</option>
                            <option value="ri-bread-line">Bakery</option>
                            <option value="ri-beer-line">Beer</option>
                                <option value="ri-glass-line">Cocktail</option>
                                <option value="ri-wine-line">Wine</option>
                                <option value="ri-sparkling-line">Sparkling Drink</option>
                                <option value="ri-juice-line">Juice</option>
                                <option value="ri-soda-line">Soda</option>
                                <option value="ri-cigarette-line">Smoking</option>
                                <option value="ri-tv-line">Entertainment</option>
                                <option value="ri-game-line">Games</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="deleteCategory">
                        <input type="hidden" name="id" id="delete-category-id">
                        
                        <p>Are you sure you want to delete the category <span id="delete-category-name" class="fw-bold"></span>?</p>
                        <p class="text-danger small">This is only possible if there are no menu items using this category.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Add page-specific scripts
$extraScripts = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Show/hide stock quantity based on inventory tracking
        const trackInventoryCheckbox = document.getElementById("track_inventory");
        const stockQuantityContainer = document.querySelector(".stock-quantity-container");
        
        if (trackInventoryCheckbox && stockQuantityContainer) {
            trackInventoryCheckbox.addEventListener("change", function() {
                stockQuantityContainer.classList.toggle("d-none", !this.checked);
            });
        }
        
        // Same for edit form
        const editTrackInventoryCheckbox = document.getElementById("edit-track_inventory");
        const editStockQuantityContainer = document.querySelector(".edit-stock-quantity-container");
        
        if (editTrackInventoryCheckbox && editStockQuantityContainer) {
            editTrackInventoryCheckbox.addEventListener("change", function() {
                editStockQuantityContainer.classList.toggle("d-none", !this.checked);
            });
        }
        
        // Handle search functionality
        const searchInput = document.getElementById("searchMenuItems");
        const menuItemCards = document.querySelectorAll(".menu-item-card");
        const categoryFilter = document.getElementById("categoryFilter");
        
        function filterMenuItems() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCategory = categoryFilter.value;
            
            menuItemCards.forEach(function(card) {
                const cardTitle = card.querySelector(".card-title").textContent.toLowerCase();
                const cardDescription = card.querySelector(".card-text").textContent.toLowerCase();
                const cardCategory = card.getAttribute("data-category");
                
                const matchesSearch = cardTitle.includes(searchTerm) || cardDescription.includes(searchTerm);
                const matchesCategory = selectedCategory === "all" || cardCategory === selectedCategory;
                
                if (matchesSearch && matchesCategory) {
                    card.parentElement.style.display = "";
                } else {
                    card.parentElement.style.display = "none";
                }
            });
        }
        
        if (searchInput) {
            searchInput.addEventListener("keyup", filterMenuItems);
        }
        
        if (categoryFilter) {
            categoryFilter.addEventListener("change", filterMenuItems);
        }
        
        // Handle edit menu item modal
        const editMenuItemModal = document.getElementById("editMenuItemModal");
        if (editMenuItemModal) {
            editMenuItemModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                
                // Extract data from button
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                const description = button.getAttribute("data-description");
                const price = button.getAttribute("data-price");
                const categoryId = button.getAttribute("data-category-id");
                const icon = button.getAttribute("data-icon");
                const active = button.getAttribute("data-active") === "1";
                const trackInventory = button.getAttribute("data-track-inventory") === "1";
                const stockQuantity = button.getAttribute("data-stock-quantity");
                
                // Populate form fields
                document.getElementById("edit-id").value = id;
                document.getElementById("edit-name").value = name;
                document.getElementById("edit-description").value = description;
                document.getElementById("edit-price").value = price;
                document.getElementById("edit-category_id").value = categoryId;
                document.getElementById("edit-icon").value = icon;
                document.getElementById("edit-active").checked = active;
                document.getElementById("edit-track_inventory").checked = trackInventory;
                document.getElementById("edit-stock_quantity").value = stockQuantity;
                
                // Show/hide stock quantity field
                editStockQuantityContainer.classList.toggle("d-none", !trackInventory);
            });
        }
        
        // Handle delete menu item modal
        const deleteMenuItemModal = document.getElementById("deleteMenuItemModal");
        if (deleteMenuItemModal) {
            deleteMenuItemModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                
                document.getElementById("delete-id").value = id;
                document.getElementById("delete-name").textContent = name;
            });
        }
        
        // Handle edit category modal
        const editCategoryModal = document.getElementById("editCategoryModal");
        if (editCategoryModal) {
            editCategoryModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                const icon = button.getAttribute("data-icon");
                
                document.getElementById("edit-category-id").value = id;
                document.getElementById("edit-category-name").value = name;
                document.getElementById("edit-category-icon").value = icon;
            });
        }
        
        // Handle delete category modal
        const deleteCategoryModal = document.getElementById("deleteCategoryModal");
        if (deleteCategoryModal) {
            deleteCategoryModal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute("data-id");
                const name = button.getAttribute("data-name");
                
                document.getElementById("delete-category-id").value = id;
                document.getElementById("delete-category-name").textContent = name;
            });
        }
    });
</script>
';

// Include footer
include dirname(__DIR__) . '/includes/footer.php';
?>