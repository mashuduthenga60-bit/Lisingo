<?php
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect(SITE_URL . '/pages/products.php', 'Product not found.', 'danger');
}

$conn = getDBConnection();

// Increment views
$conn->query("UPDATE products SET views = views + 1 WHERE id = $id");

// Get product
$stmt = $conn->prepare("SELECT p.*, u.username, u.first_name, u.last_name, u.city as seller_city, u.province as seller_province, u.profile_image, u.created_at as member_since, c.name as category_name 
    FROM products p 
    LEFT JOIN users u ON p.seller_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $conn->close();
    redirect(SITE_URL . '/pages/products.php', 'Product not found.', 'danger');
}

$page_title = $product['title'];

// Get seller's other products
$stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = ? AND id != ? AND status = 'active' LIMIT 4");
$stmt->bind_param("ii", $product['seller_id'], $id);
$stmt->execute();
$related = $stmt->get_result();
$stmt->close();

// Get reviews for seller
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE reviewed_user_id = ?");
$stmt->bind_param("i", $product['seller_id']);
$stmt->execute();
$seller_rating = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/products.php">Products</a></li>
            <?php if ($product['category_name']): ?>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/pages/products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['title']); ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Product Images -->
        <div class="col-lg-6">
            <div class="product-gallery mb-3">
                <?php
                $images = array_filter([$product['image1'], $product['image2'], $product['image3'], $product['image4']]);
                $main_image = !empty($images) ? $images[0] : null;
                ?>
                <?php if ($main_image): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $main_image; ?>" class="img-fluid rounded product-main-image w-100" style="max-height:500px; object-fit:cover;" alt="Product">
                <?php else: ?>
                    <div class="placeholder-img rounded" style="height:400px;"><i class="bi bi-image"></i></div>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
            <div class="product-thumbnails d-flex gap-2">
                <?php foreach ($images as $idx => $img): ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $img; ?>" class="<?php echo $idx === 0 ? 'active' : ''; ?>" alt="Thumbnail">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <span class="badge product-condition badge-condition-<?php echo $product['condition']; ?> mb-2"><?php echo ucfirst($product['condition']); ?></span>
            <h2 class="fw-bold"><?php echo htmlspecialchars($product['title']); ?></h2>
            <h3 class="text-primary fw-bold mb-3"><?php echo formatPrice($product['price']); ?></h3>

            <div class="d-flex gap-3 text-muted mb-3">
                <span><i class="bi bi-eye"></i> <?php echo $product['views']; ?> views</span>
                <span><i class="bi bi-clock"></i> <?php echo timeAgo($product['created_at']); ?></span>
                <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($product['location'] ?? $product['seller_city'] ?? 'South Africa'); ?></span>
            </div>

            <?php if ($product['quantity'] > 0): ?>
                <p class="text-success"><i class="bi bi-check-circle"></i> In Stock (<?php echo $product['quantity']; ?> available)</p>
            <?php else: ?>
                <p class="text-danger"><i class="bi bi-x-circle"></i> Out of Stock</p>
            <?php endif; ?>

            <!-- Action Buttons -->
            <?php if (isLoggedIn() && $_SESSION['user_id'] != $product['seller_id']): ?>
                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-primary btn-lg btn-add-cart" data-product-id="<?php echo $product['id']; ?>">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                    </button>
                    <a href="<?php echo SITE_URL; ?>/pages/messages.php?to=<?php echo $product['seller_id']; ?>&product=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-chat-dots"></i> Message Seller
                    </a>
                </div>
            <?php elseif (!isLoggedIn()): ?>
                <div class="mb-4">
                    <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right"></i> Login to Buy</a>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <a href="<?php echo SITE_URL; ?>/pages/edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-lg"><i class="bi bi-pencil"></i> Edit Product</a>
                </div>
            <?php endif; ?>

            <hr>

            <!-- Description -->
            <h5>Description</h5>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <hr>

            <!-- Seller Info -->
            <div class="d-flex align-items-center gap-3">
                <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo $product['profile_image'] ?? 'default.png'; ?>" class="rounded-circle" width="60" height="60" style="object-fit:cover;" alt="Seller" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($product['username']); ?>&background=2563eb&color=fff'">
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></h6>
                    <small class="text-muted">@<?php echo htmlspecialchars($product['username']); ?></small><br>
                    <small class="text-muted">
                        <?php if ($seller_rating['review_count'] > 0): ?>
                            <?php echo str_repeat('<i class="bi bi-star-fill text-warning"></i>', round($seller_rating['avg_rating'])); ?>
                            (<?php echo $seller_rating['review_count']; ?> reviews)
                        <?php else: ?>
                            New Seller
                        <?php endif; ?>
                        &middot; Member since <?php echo date('M Y', strtotime($product['member_since'])); ?>
                    </small>
                </div>
                <a href="<?php echo SITE_URL; ?>/pages/profile.php?id=<?php echo $product['seller_id']; ?>" class="btn btn-outline-primary btn-sm ms-auto">View Profile</a>
            </div>
        </div>
    </div>

    <!-- Related Products from Same Seller -->
    <?php if ($related->num_rows > 0): ?>
    <hr class="my-5">
    <h4 class="section-title">More from this Seller</h4>
    <div class="row g-3">
        <?php while ($rp = $related->fetch_assoc()): ?>
        <div class="col-6 col-md-3">
            <div class="product-card">
                <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $rp['id']; ?>">
                    <?php if ($rp['image1']): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $rp['image1']; ?>" class="card-img-top" alt="">
                    <?php else: ?>
                        <div class="card-img-top placeholder-img" style="height:160px;"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                </a>
                <div class="card-body">
                    <p class="product-title mb-1"><?php echo htmlspecialchars($rp['title']); ?></p>
                    <p class="product-price mb-0"><?php echo formatPrice($rp['price']); ?></p>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<script>var siteUrl = '<?php echo SITE_URL; ?>';</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
