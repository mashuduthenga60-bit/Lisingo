<?php
require_once __DIR__ . '/../../includes/functions.php';

// Check admin access
if (!isLoggedIn() || (!isAdmin() && !isModerator())) {
    redirect(SITE_URL . '/pages/login.php', 'Access denied. Admin privileges required.', 'danger');
}

$admin_user = getUserById($_SESSION['user_id']);
$admin_role = getRolePermissions($_SESSION['role_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .admin-sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.3s;
        }
        .admin-sidebar .nav-link {
            color: #94a3b8;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .admin-sidebar .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }
        .admin-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            background: #f1f5f9;
        }
        .admin-topbar {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar" id="adminSidebar">
        <div class="p-3 text-center border-bottom border-secondary">
            <a href="<?php echo SITE_URL; ?>/admin/" class="text-white text-decoration-none">
                <h4 class="mb-0"><i class="bi bi-speedometer2"></i> <?php echo SITE_NAME; ?></h4>
                <small class="text-muted">Admin Panel</small>
            </a>
        </div>
        <nav class="mt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <?php if (hasPermission('can_manage_users')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('can_manage_products')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/products.php">
                        <i class="bi bi-box-seam"></i> Products
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('can_manage_orders')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/orders.php">
                        <i class="bi bi-receipt"></i> Orders
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('can_manage_categories')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/categories.php">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('can_manage_roles')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'roles.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/roles.php">
                        <i class="bi bi-shield-lock"></i> Roles & Permissions
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('can_view_reports')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/reports.php">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item mt-3 border-top border-secondary pt-3">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/">
                        <i class="bi bi-house"></i> View Site
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/pages/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <!-- Top Bar -->
        <div class="admin-topbar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-secondary d-md-none me-2" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="d-inline mb-0"><?php echo $page_title ?? 'Dashboard'; ?></h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary"><?php echo htmlspecialchars($admin_role['role_name'] ?? 'Admin'); ?></span>
                <span class="text-muted"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php echo displayFlashMessage(); ?>

        <!-- Page Content -->
