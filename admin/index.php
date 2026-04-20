<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';

$conn = getDBConnection();

// Stats
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status != 'removed'")->fetch_assoc()['c'];
$total_orders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_price), 0) as r FROM orders WHERE status IN ('paid','shipped','delivered')")->fetch_assoc()['r'];
$active_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status = 'active'")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'pending'")->fetch_assoc()['c'];

// Recent orders
$recent_orders = $conn->query("SELECT o.*, p.title, u.username as buyer_name FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.buyer_id = u.id ORDER BY o.created_at DESC LIMIT 5");

// Recent users
$recent_users = $conn->query("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC LIMIT 5");
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label mb-1">Total Users</p>
                    <p class="stat-value mb-0"><?php echo $total_users; ?></p>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label mb-1">Products</p>
                    <p class="stat-value mb-0"><?php echo $total_products; ?></p>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-box-seam"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label mb-1">Orders</p>
                    <p class="stat-value mb-0"><?php echo $total_orders; ?></p>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label mb-1">Revenue</p>
                    <p class="stat-value mb-0"><?php echo formatPrice($total_revenue); ?></p>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-currency-dollar"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label mb-1">Active Listings</p>
                    <p class="stat-value mb-0"><?php echo $active_products; ?></p>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label mb-1">Pending Orders</p>
                    <p class="stat-value mb-0"><?php echo $pending_orders; ?></p>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-clock"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Recent Orders</strong>
                <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Product</th><th>Buyer</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars(substr($order['title'], 0, 30)); ?></td>
                                <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                <td><?php echo formatPrice($order['total_price']); ?></td>
                                <td><span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info'); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($total_orders == 0): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No orders yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>New Members</strong>
                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>User</th><th>Role</th><th>Joined</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['role_name'] ?? 'user'); ?></span></td>
                                <td><small><?php echo date('d M', strtotime($u['created_at'])); ?></small></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
