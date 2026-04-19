<?php
$page_title = 'Checkout';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login to checkout.', 'warning');
}

$conn = getDBConnection();

// Get cart items
$stmt = $conn->prepare("SELECT c.*, p.title, p.price, p.image1, p.seller_id, p.quantity as stock 
    FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND p.status = 'active'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$items = $stmt->get_result();
$cart_items = [];
$total = 0;
while ($item = $items->fetch_assoc()) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
    $cart_items[] = $item;
}
$stmt->close();

if (empty($cart_items)) {
    redirect(SITE_URL . '/pages/cart.php', 'Your cart is empty.', 'warning');
}

// Get user details
$user = getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $zip = sanitize($_POST['zip_code'] ?? '');
    $payment = sanitize($_POST['payment_method'] ?? 'EFT');
    $shipping_address = "$address, $city, $province, $zip";

    $errors = [];
    if (empty($address)) $errors[] = 'Shipping address is required.';

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("INSERT INTO orders (buyer_id, product_id, seller_id, quantity, total_price, status, shipping_address, payment_method) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)");
                $stmt->bind_param("iiiddss", $_SESSION['user_id'], $item['product_id'], $item['seller_id'], $item['quantity'], $item['subtotal'], $shipping_address, $payment);
                $stmt->execute();
                $stmt->close();

                // Update product quantity
                $new_qty = $item['stock'] - $item['quantity'];
                if ($new_qty <= 0) {
                    $conn->query("UPDATE products SET quantity = 0, status = 'sold' WHERE id = " . $item['product_id']);
                } else {
                    $conn->query("UPDATE products SET quantity = quantity - " . $item['quantity'] . " WHERE id = " . $item['product_id']);
                }
            }

            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            redirect(SITE_URL . '/pages/orders.php', 'Order placed successfully! The seller will contact you.', 'success');
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Order failed. Please try again.';
        }
    }
}
?>

<div class="container py-4">
    <h3 class="section-title"><i class="bi bi-credit-card"></i> Checkout</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <form method="POST">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Shipping Information</h5>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Street Address *</label>
                            <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">City *</label>
                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Province</label>
                                <input type="text" class="form-control" name="province" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Zip Code</label>
                                <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Payment Method</h5>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="EFT" id="eft" checked>
                            <label class="form-check-label" for="eft"><i class="bi bi-bank"></i> EFT / Bank Transfer</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" value="Cash" id="cash">
                            <label class="form-check-label" for="cash"><i class="bi bi-cash"></i> Cash on Collection</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="Card" id="card">
                            <label class="form-check-label" for="card"><i class="bi bi-credit-card"></i> Card Payment</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check-circle"></i> Place Order - <?php echo formatPrice($total); ?></button>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="order-summary">
                <h5 class="mb-3">Order Summary</h5>
                <?php foreach ($cart_items as $item): ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?php if ($item['image1']): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $item['image1']; ?>" width="50" height="50" style="object-fit:cover;border-radius:6px;" alt="">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <small class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></small>
                        <small class="d-block text-muted">Qty: <?php echo $item['quantity']; ?></small>
                    </div>
                    <strong><?php echo formatPrice($item['subtotal']); ?></strong>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Total</strong>
                    <strong class="text-primary fs-5"><?php echo formatPrice($total); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
