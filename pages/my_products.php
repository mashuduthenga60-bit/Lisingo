<?php
$page_title = 'My Products';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login.', 'warning');
}

$conn = getDBConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("UPDATE products SET status = 'removed' WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $del_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    redirect(SITE_URL . '/pages/my_products.php', 'Product removed.', 'success');
}

$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.seller_id = ? AND p.status != 'removed' ORDER BY p.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$products = $stmt->get_result();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="section-title mb-0"><i class="bi bi-box"></i> My Products</h3>
        <a href="<?php echo SITE_URL; ?>/pages/sell.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Listing</a>
    </div>

    <?php if ($products->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover bg-white rounded shadow-sm">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
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
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($p['image1']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $p['image1']; ?>" width="40" height="40" style="object-fit:cover;border-radius:6px;" alt="">
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></a>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                    <td><?php echo formatPrice($p['price']); ?></td>
                    <td><?php echo $p['quantity']; ?></td>
                    <td><span class="badge bg-<?php echo $p['status'] === 'active' ? 'success' : ($p['status'] === 'sold' ? 'info' : 'warning'); ?>"><?php echo ucfirst($p['status']); ?></span></td>
                    <td><?php echo $p['views']; ?></td>
                    <td><small><?php echo date('d M Y', strtotime($p['created_at'])); ?></small></td>
                    <td>
                        <a href="<?php echo SITE_URL; ?>/pages/edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="<?php echo SITE_URL; ?>/pages/my_products.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-box-seam d-block"></i>
            <h4>No products listed yet</h4>
            <p>Start selling by listing your first item.</p>
            <a href="<?php echo SITE_URL; ?>/pages/sell.php" class="btn btn-primary">List an Item</a>
        </div>
    <?php endif; ?>
</div>

<?php $stmt->close(); $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
