<?php
$page_title = 'Manage Products';
require_once __DIR__ . '/includes/admin_header.php';

if (!hasPermission('can_manage_products')) {
    redirect(SITE_URL . '/admin/', 'Access denied.', 'danger');
}

$conn = getDBConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($action === 'update_status' && $product_id > 0) {
        $new_status = sanitize($_POST['status']);
        if (in_array($new_status, ['active', 'pending', 'sold', 'removed'])) {
            $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $product_id);
            $stmt->execute();
            $stmt->close();
            redirect(SITE_URL . '/admin/products.php', 'Product status updated.', 'success');
        }
    }

    if ($action === 'delete' && $product_id > 0) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        redirect(SITE_URL . '/admin/products.php', 'Product deleted.', 'success');
    }
}

// Get products
$search = sanitize($_GET['search'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (p.title LIKE ? OR u.username LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s];
    $types = "ss";
}
if (!empty($status_filter)) {
    $where .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql = "SELECT p.*, u.username, c.name as category_name FROM products p LEFT JOIN users u ON p.seller_id = u.id LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:300px;">
            <select class="form-select" name="status" style="width:auto;">
                <option value="">All Status</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                <option value="removed" <?php echo $status_filter === 'removed' ? 'selected' : ''; ?>>Removed</option>
            </select>
            <button class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($p['image1']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $p['image1']; ?>" width="40" height="40" style="object-fit:cover;border-radius:6px;" alt="">
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $p['id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars(substr($p['title'], 0, 40)); ?>
                                </a>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['username']); ?></td>
                        <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                        <td><?php echo formatPrice($p['price']); ?></td>
                        <td><?php echo $p['quantity']; ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    <option value="active" <?php echo $p['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $p['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="sold" <?php echo $p['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                    <option value="removed" <?php echo $p['status'] === 'removed' ? 'selected' : ''; ?>>Removed</option>
                                </select>
                            </form>
                        </td>
                        <td><?php echo $p['views']; ?></td>
                        <td><small><?php echo date('d M Y', strtotime($p['created_at'])); ?></small></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/pages/edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $stmt->close(); $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
