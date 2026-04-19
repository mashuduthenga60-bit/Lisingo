<?php
$page_title = 'Manage Orders';
require_once __DIR__ . '/includes/admin_header.php';

if (!hasPermission('can_manage_orders')) {
    redirect(SITE_URL . '/admin/', 'Access denied.', 'danger');
}

$conn = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = sanitize($_POST['status'] ?? '');
    if ($order_id > 0 && in_array($new_status, ['pending', 'paid', 'shipped', 'delivered', 'cancelled', 'refunded'])) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        $stmt->close();
        redirect(SITE_URL . '/admin/orders.php', 'Order status updated.', 'success');
    }
}

$status_filter = sanitize($_GET['status'] ?? '');
$where = "";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $where = "WHERE o.status = ?";
    $params[] = $status_filter;
    $types = "s";
}

$sql = "SELECT o.*, p.title, p.image1, buyer.username as buyer_name, buyer.email as buyer_email, seller.username as seller_name 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    JOIN users buyer ON o.buyer_id = buyer.id 
    JOIN users seller ON o.seller_id = seller.id 
    $where ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <select class="form-select" name="status" style="width:auto;">
                <option value="">All Orders</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
            </select>
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                    <?php while ($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $o['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($o['image1']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $o['image1']; ?>" width="35" height="35" style="object-fit:cover;border-radius:4px;" alt="">
                                <?php endif; ?>
                                <?php echo htmlspecialchars(substr($o['title'], 0, 30)); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($o['buyer_name']); ?></td>
                        <td><?php echo htmlspecialchars($o['seller_name']); ?></td>
                        <td><?php echo formatPrice($o['total_price']); ?></td>
                        <td><small><?php echo htmlspecialchars($o['payment_method'] ?? '-'); ?></small></td>
                        <td>
                            <?php
                            $colors = ['pending'=>'warning','paid'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger','refunded'=>'secondary'];
                            ?>
                            <span class="badge bg-<?php echo $colors[$o['status']] ?? 'secondary'; ?>"><?php echo ucfirst($o['status']); ?></span>
                        </td>
                        <td><small><?php echo date('d M Y', strtotime($o['created_at'])); ?></small></td>
                        <td>
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <select name="status" class="form-select form-select-sm" style="width:auto;">
                                    <?php foreach (['pending','paid','shipped','delivered','cancelled','refunded'] as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $o['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No orders found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $stmt->close(); $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
