<!-- CSS Changes to add in assets/css/style.css -->
<style>
.sidebar-sticky {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 1020;
}

/* For Firefox */
.sidebar-sticky {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

/* For Chrome, Edge, and Safari */
.sidebar-sticky::-webkit-scrollbar {
    width: 5px;
}

.sidebar-sticky::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-sticky::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    border: transparent;
}
</style>

<!-- Responsive Sidebar for larger screens -->
<div class="sidebar sidebar-sticky bg-dark text-white d-none d-md-flex flex-column" style="min-width: 250px;">
    <div class="p-3 border-bottom border-secondary">
        <h1 class="h5 mb-0 d-flex align-items-center gap-2">
            <i class="ri-cup-line"></i>
            <span>Cafe Manager</span>
        </h1>
    </div>
    
    <div class="py-3 flex-grow-1 overflow-auto">
        <div class="px-3 mb-2 text-uppercase text-white-50 small">
            Dashboard
        </div>
        
        <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link px-3 py-2 mb-1 <?php echo $currentPage == 'index.php' ? 'active bg-primary-subtle text-white' : 'text-white-50'; ?>">
            <i class="ri-dashboard-line me-2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/pages/orders.php" class="nav-link px-3 py-2 mb-1 <?php echo $currentPage == 'orders.php' ? 'active bg-primary-subtle text-white' : 'text-white-50'; ?>">
            <i class="ri-shopping-basket-2-line me-2"></i>
            <span>Orders</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/pages/menu-management.php" class="nav-link px-3 py-2 mb-1 <?php echo $currentPage == 'menu-management.php' ? 'active bg-primary-subtle text-white' : 'text-white-50'; ?>">
            <i class="ri-restaurant-line me-2"></i>
            <span>Menu</span>
        </a>
        
        <?php if (isAdmin()): ?>
        <a href="<?php echo BASE_URL; ?>/pages/staff-management.php" class="nav-link px-3 py-2 mb-1 <?php echo $currentPage == 'staff-management.php' ? 'active bg-primary-subtle text-white' : 'text-white-50'; ?>">
            <i class="ri-user-line me-2"></i>
            <span>Staff</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/pages/reports.php" class="nav-link px-3 py-2 mb-1 <?php echo $currentPage == 'reports.php' ? 'active bg-primary-subtle text-white' : 'text-white-50'; ?>">
            <i class="ri-file-chart-line me-2"></i>
            <span>Reports</span>
        </a>
        <?php endif; ?>
        
        <a href="<?php echo BASE_URL; ?>/pages/settings.php" class="nav-link px-3 py-2 mb-1 <?php echo $currentPage == 'settings.php' ? 'active bg-primary-subtle text-white' : 'text-white-50'; ?>">
            <i class="ri-settings-4-line me-2"></i>
            <span>Settings</span>
        </a>
    </div>
    
    <div class="mt-auto p-3 border-top border-secondary">
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                <?php 
                // Display user initials
                if($currentUser) {
                    $initials = '';
                    $nameParts = explode(' ', $currentUser['name']);
                    foreach($nameParts as $part) {
                        $initials .= substr($part, 0, 1);
                    }
                    echo htmlspecialchars(strtoupper($initials));
                }
                ?>
            </div>
            <div>
                <div class="small fw-medium"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></div>
                <div class="small text-white-50 text-capitalize"><?php echo htmlspecialchars($currentUser['role'] ?? ''); ?></div>
            </div>
        </div>
    </div>
</div>