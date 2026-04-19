<?php
$page_title = 'Shopping Cart';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login to view your cart.', 'warning');
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT c.*, p.title, p.price, p.image1, p.quantity as stock, p.seller_id, u.username 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    JOIN users u ON p.seller_id = u.id 
    WHERE c.user_id = ? AND p.status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$items = $stmt->get_result();

$total = 0;
$cart_items = [];
while ($item = $items->fetch_assoc()) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
    $cart_items[] = $item;
}
$stmt->close();
?>

<div class="container py-4">
    <h3 class="section-title"><i class="bi bi-cart3"></i> Shopping Cart</h3>

    <?php if (!empty($cart_items)): ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <?php foreach ($cart_items as $item): ?>
            <div class="cart-item d-flex align-items-center gap-3">
                <?php if ($item['image1']): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $item['image1']; ?>" alt="">
                <?php else: ?>
                    <div class="placeholder-img" style="width:100px;height:100px;border-radius:8px;"><i class="bi bi-image"></i></div>
                <?php endif; ?>
                <div class="flex-grow-1">
                    <h6 class="mb-1"><a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h6>
                    <small class="text-muted">Seller: <?php echo htmlspecialchars($item['username']); ?></small>
                    <p class="product-price mb-0"><?php echo formatPrice($item['price']); ?></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm cart-qty" data-product-id="<?php echo $item['product_id']; ?>" style="width:70px;">
                        <?php for ($i = 1; $i <= min(10, $item['stock']); $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $item['quantity'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <strong><?php echo formatPrice($item['subtotal']); ?></strong>
                    <button class="btn btn-outline-danger btn-sm btn-remove-cart" data-product-id="<?php echo $item['product_id']; ?>"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="col-lg-4">
            <div class="order-summary">
                <h5 class="mb-3">Order Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal (<?php echo count($cart_items); ?> items)</span>
                    <strong><?php echo formatPrice($total); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping</span>
                    <span class="text-success">Arranged with seller</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total</strong>
                    <strong class="text-primary fs-5"><?php echo formatPrice($total); ?></strong>
                </div>
                <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn btn-primary w-100 btn-lg"><i class="bi bi-credit-card"></i> Proceed to Checkout</a>
                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-cart-x d-block"></i>
            <h4>Your cart is empty</h4>
            <p>Browse our products and add items to your cart.</p>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary">Browse Products</a>
        </div>
    <?php endif; ?>
</div>

<script>var siteUrl = '<?php echo SITE_URL; ?>';</script>

<?php $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
