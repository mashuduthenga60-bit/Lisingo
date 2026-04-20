<?php
$page_title = 'Reports';
require_once __DIR__ . '/includes/admin_header.php';

if (!hasPermission('can_view_reports')) {
    redirect(SITE_URL . '/admin/', 'Access denied.', 'danger');
}

$conn = getDBConnection();

// User stats
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$active_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='active'")->fetch_assoc()['c'];
$suspended_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='suspended'")->fetch_assoc()['c'];
$banned_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='banned'")->fetch_assoc()['c'];

// Product stats
$total_products = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$active_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='active'")->fetch_assoc()['c'];
$sold_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='sold'")->fetch_assoc()['c'];

// Order stats
$total_orders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$delivered_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='delivered'")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_price), 0) as r FROM orders WHERE status IN ('paid','shipped','delivered')")->fetch_assoc()['r'];

// Top sellers
$top_sellers = $conn->query("SELECT u.username, u.first_name, u.last_name, COUNT(p.id) as product_count, COALESCE(SUM(o.total_price), 0) as total_sales 
    FROM users u 
    LEFT JOIN products p ON u.id = p.seller_id 
    LEFT JOIN orders o ON u.id = o.seller_id AND o.status IN ('paid','shipped','delivered')
    GROUP BY u.id 
    HAVING product_count > 0 
    ORDER BY total_sales DESC LIMIT 10");

// Top products by views
$top_products = $conn->query("SELECT p.title, p.views, p.price, u.username FROM products p JOIN users u ON p.seller_id = u.id WHERE p.status = 'active' ORDER BY p.views DESC LIMIT 10");

// Products per category
$cat_stats = $conn->query("SELECT c.name, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY count DESC");

// Role distribution
$role_stats = $conn->query("SELECT r.role_name, COUNT(u.id) as count FROM roles r LEFT JOIN users u ON r.id = u.role_id GROUP BY r.id ORDER BY count DESC");
?>

<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-2"><i class="bi bi-people"></i></div>
            <p class="stat-value mb-0"><?php echo $total_users; ?></p>
            <p class="stat-label">Total Users</p>
            <small class="text-success"><?php echo $active_users; ?> active</small> &middot;
            <small class="text-warning"><?php echo $suspended_users; ?> suspended</small> &middot;
            <small class="text-danger"><?php echo $banned_users; ?> banned</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-success bg-opacity-10 text-success mb-2"><i class="bi bi-box-seam"></i></div>
            <p class="stat-value mb-0"><?php echo $total_products; ?></p>
            <p class="stat-label">Total Products</p>
            <small class="text-success"><?php echo $active_products; ?> active</small> &middot;
            <small class="text-info"><?php echo $sold_products; ?> sold</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning mb-2"><i class="bi bi-receipt"></i></div>
            <p class="stat-value mb-0"><?php echo $total_orders; ?></p>
            <p class="stat-label">Total Orders</p>
            <small class="text-warning"><?php echo $pending_orders; ?> pending</small> &middot;
            <small class="text-success"><?php echo $delivered_orders; ?> delivered</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-icon bg-info bg-opacity-10 text-info mb-2"><i class="bi bi-cash-coin"></i></div>
            <p class="stat-value mb-0"><?php echo formatPrice($total_revenue); ?></p>
            <p class="stat-label">Total Revenue</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Top Sellers -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong><i class="bi bi-trophy"></i> Top Sellers</strong></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Seller</th><th>Products</th><th>Revenue</th></tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; while ($seller = $top_sellers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?><br><small class="text-muted">@<?php echo htmlspecialchars($seller['username']); ?></small></td>
                            <td><?php echo $seller['product_count']; ?></td>
                            <td><?php echo formatPrice($seller['total_sales']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($rank == 1): ?><tr><td colspan="4" class="text-center text-muted">No data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong><i class="bi bi-eye"></i> Most Viewed Products</strong></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Product</th><th>Seller</th><th>Views</th><th>Price</th></tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; while ($p = $top_products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars(substr($p['title'], 0, 25)); ?></td>
                            <td><small><?php echo htmlspecialchars($p['username']); ?></small></td>
                            <td><?php echo $p['views']; ?></td>
                            <td><?php echo formatPrice($p['price']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($rank == 1): ?><tr><td colspan="5" class="text-center text-muted">No data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Distribution -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong><i class="bi bi-tags"></i> Products per Category</strong></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Category</th><th>Products</th></tr></thead>
                    <tbody>
                        <?php while ($c = $cat_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><span class="badge bg-primary"><?php echo $c['count']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Role Distribution -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong><i class="bi bi-shield-lock"></i> User Role Distribution</strong></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Role</th><th>Users</th></tr></thead>
                    <tbody>
                        <?php while ($r = $role_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['role_name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $r['count']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
