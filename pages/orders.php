<?php
$page_title = 'My Orders';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login.', 'warning');
}

$conn = getDBConnection();
$tab = sanitize($_GET['tab'] ?? 'purchases');

if ($tab === 'sales') {
    $stmt = $conn->prepare("SELECT o.*, p.title, p.image1, u.username as buyer_name, u.email as buyer_email 
        FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.buyer_id = u.id 
        WHERE o.seller_id = ? ORDER BY o.created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT o.*, p.title, p.image1, u.username as seller_name 
        FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.seller_id = u.id 
        WHERE o.buyer_id = ? ORDER BY o.created_at DESC");
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result();

// Handle status update for sellers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['new_status']);
    $allowed = ['paid', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed)) {
        $upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
        $upd->bind_param("sii", $new_status, $order_id, $_SESSION['user_id']);
        $upd->execute();
        $upd->close();
        redirect(SITE_URL . '/pages/orders.php?tab=sales', 'Order status updated.', 'success');
    }
}
?>

<div class="container py-4">
    <h3 class="section-title"><i class="bi bi-receipt"></i> My Orders</h3>

    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'purchases' ? 'active' : ''; ?>" href="?tab=purchases">My Purchases</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'sales' ? 'active' : ''; ?>" href="?tab=sales">My Sales</a>
        </li>
    </ul>

    <?php if ($orders->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover bg-white rounded shadow-sm">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th><?php echo $tab === 'sales' ? 'Buyer' : 'Seller'; ?></th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <?php if ($tab === 'sales'): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($order['image1']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $order['image1']; ?>" width="40" height="40" style="object-fit:cover;border-radius:6px;" alt="">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($order['title']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($tab === 'sales' ? $order['buyer_name'] : $order['seller_name']); ?></td>
                        <td><?php echo formatPrice($order['total_price']); ?></td>
                        <td>
                            <?php
                            $badge_colors = ['pending' => 'warning', 'paid' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'refunded' => 'secondary'];
                            ?>
                            <span class="badge bg-<?php echo $badge_colors[$order['status']] ?? 'secondary'; ?>"><?php echo ucfirst($order['status']); ?></span>
                        </td>
                        <td><small><?php echo date('d M Y', strtotime($order['created_at'])); ?></small></td>
                        <?php if ($tab === 'sales'): ?>
                        <td>
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="new_status" class="form-select form-select-sm" style="width:auto;">
                                    <option value="paid" <?php echo $order['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Update</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-receipt-cutoff d-block"></i>
            <h4>No <?php echo $tab === 'sales' ? 'sales' : 'purchases'; ?> yet</h4>
            <p><?php echo $tab === 'sales' ? 'Start selling to see your orders here.' : 'Browse products and make your first purchase.'; ?></p>
        </div>
    <?php endif; ?>
</div>

<?php $stmt->close(); $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
