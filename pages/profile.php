<?php
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? $_SESSION['user_id'] : 0);
if ($id <= 0) {
    redirect(SITE_URL . '/pages/login.php', 'Please login.', 'warning');
}

$conn = getDBConnection();
$user = getUserById($id);
if (!$user) {
    redirect(SITE_URL . '/', 'User not found.', 'danger');
}

$page_title = $user['username'] . "'s Profile";

// Get user's products
$stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 8");
$stmt->bind_param("i", $id);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// Get stats
$product_count = $conn->query("SELECT COUNT(*) as c FROM products WHERE seller_id = $id AND status = 'active'")->fetch_assoc()['c'];
$sold_count = $conn->query("SELECT COUNT(*) as c FROM orders WHERE seller_id = $id AND status = 'delivered'")->fetch_assoc()['c'];

// Get reviews
$stmt = $conn->prepare("SELECT r.*, u.username, u.profile_image FROM reviews r JOIN users u ON r.reviewer_id = u.id WHERE r.reviewed_user_id = ? ORDER BY r.created_at DESC LIMIT 5");
$stmt->bind_param("i", $id);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();

$avg_rating = $conn->query("SELECT AVG(rating) as avg_r FROM reviews WHERE reviewed_user_id = $id")->fetch_assoc()['avg_r'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="profile-header mb-4">
    <div class="container text-center">
        <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo $user['profile_image']; ?>" class="profile-avatar mb-3" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'] . '+' . $user['last_name']); ?>&size=120&background=2563eb&color=fff'">
        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
        <p class="opacity-75">@<?php echo htmlspecialchars($user['username']); ?> &middot; Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
        <?php if ($user['city'] || $user['province']): ?>
            <p class="opacity-75"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars(trim($user['city'] . ', ' . $user['province'], ', ')); ?></p>
        <?php endif; ?>
        <div class="d-flex justify-content-center gap-4 mt-3">
            <div><strong><?php echo $product_count; ?></strong><br><small class="opacity-75">Listings</small></div>
            <div><strong><?php echo $sold_count; ?></strong><br><small class="opacity-75">Sold</small></div>
            <div><strong><?php echo $avg_rating ? number_format($avg_rating, 1) : 'N/A'; ?></strong><br><small class="opacity-75">Rating</small></div>
        </div>
        <?php if (isLoggedIn() && $_SESSION['user_id'] == $id): ?>
            <a href="<?php echo SITE_URL; ?>/pages/edit_profile.php" class="btn btn-light mt-3"><i class="bi bi-pencil"></i> Edit Profile</a>
        <?php elseif (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>/pages/messages.php?to=<?php echo $id; ?>" class="btn btn-light mt-3"><i class="bi bi-chat-dots"></i> Send Message</a>
        <?php endif; ?>
    </div>
</div>

<div class="container py-4">
    <h4 class="section-title"><?php echo $id == ($_SESSION['user_id'] ?? 0) ? 'My' : htmlspecialchars($user['first_name']) . "'s"; ?> Listings</h4>
    <div class="row g-3">
        <?php if ($products->num_rows > 0): ?>
            <?php while ($p = $products->fetch_assoc()): ?>
            <div class="col-6 col-md-3">
                <div class="product-card">
                    <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $p['id']; ?>">
                        <?php if ($p['image1']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $p['image1']; ?>" class="card-img-top" alt="">
                        <?php else: ?>
                            <div class="card-img-top placeholder-img" style="height:160px;"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </a>
                    <div class="card-body">
                        <p class="product-title mb-1"><?php echo htmlspecialchars($p['title']); ?></p>
                        <p class="product-price mb-0"><?php echo formatPrice($p['price']); ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><p class="text-muted">No active listings.</p></div>
        <?php endif; ?>
    </div>

    <!-- Reviews -->
    <?php if ($reviews->num_rows > 0): ?>
    <h4 class="section-title mt-5">Reviews</h4>
    <?php while ($review = $reviews->fetch_assoc()): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex gap-3">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($review['username']); ?>&background=2563eb&color=fff" class="rounded-circle" width="40" height="40" alt="">
            <div>
                <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                <span class="text-warning ms-2"><?php echo str_repeat('<i class="bi bi-star-fill"></i>', $review['rating']); ?></span>
                <small class="text-muted d-block"><?php echo timeAgo($review['created_at']); ?></small>
                <p class="mb-0 mt-1"><?php echo htmlspecialchars($review['comment']); ?></p>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    <?php endif; ?>
</div>

<?php $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
