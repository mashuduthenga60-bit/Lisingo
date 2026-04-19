<?php
$page_title = 'Home';
require_once 'includes/header.php';

$conn = getDBConnection();

// Get featured products
$featured = $conn->query("SELECT p.*, u.username, u.city as seller_city, c.name as category_name 
    FROM products p 
    LEFT JOIN users u ON p.seller_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.views DESC LIMIT 8");

// Get latest products
$latest = $conn->query("SELECT p.*, u.username, u.city as seller_city, c.name as category_name 
    FROM products p 
    LEFT JOIN users u ON p.seller_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC LIMIT 8");

// Get categories
$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");

// Stats
$total_products = $conn->query("SELECT COUNT(*) as c FROM products WHERE status='active'")->fetch_assoc()['c'];
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_sold = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='delivered'")->fetch_assoc()['c'];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1>Buy & Sell Directly<br>With Other Customers</h1>
                <p class="lead mb-4">South Africa's trusted Customer-to-Customer marketplace. Find great deals or sell your items to millions of buyers.</p>
                <form action="<?php echo SITE_URL; ?>/pages/search.php" method="GET" class="d-flex gap-2">
                    <input type="text" name="q" class="form-control form-control-lg" placeholder="What are you looking for?">
                    <button class="btn btn-warning btn-lg px-4" type="submit"><i class="bi bi-search"></i></button>
                </form>
                <div class="mt-3 d-flex gap-4">
                    <div><strong><?php echo $total_products; ?></strong> <small class="opacity-75">Active Listings</small></div>
                    <div><strong><?php echo $total_users; ?></strong> <small class="opacity-75">Members</small></div>
                    <div><strong><?php echo $total_sold; ?></strong> <small class="opacity-75">Items Sold</small></div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <i class="bi bi-shop" style="font-size: 12rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <h3 class="section-title">Browse Categories</h3>
        <div class="row g-3">
            <?php
            $icons = ['bi-phone', 'bi-bag', 'bi-house', 'bi-truck', 'bi-book', 'bi-trophy', 'bi-heart-pulse', 'bi-controller', 'bi-tools', 'bi-three-dots'];
            $i = 0;
            while ($cat = $categories->fetch_assoc()):
            ?>
            <div class="col-6 col-md-4 col-lg">
                <a href="<?php echo SITE_URL; ?>/pages/products.php?category=<?php echo $cat['id']; ?>" class="text-decoration-none">
                    <div class="category-card">
                        <div class="icon"><i class="bi <?php echo $icons[$i % count($icons)]; ?>"></i></div>
                        <h6><?php echo htmlspecialchars($cat['name']); ?></h6>
                    </div>
                </a>
            </div>
            <?php $i++; endwhile; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title mb-0">Featured Products</h3>
            <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-outline-primary btn-sm">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            <?php if ($featured->num_rows > 0): ?>
                <?php while ($product = $featured->fetch_assoc()): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $product['id']; ?>">
                            <?php if ($product['image1']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                                <div class="card-img-top placeholder-img" style="height:200px;"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                <p class="product-title mb-1"><?php echo htmlspecialchars($product['title']); ?></p>
                            </a>
                            <p class="product-price mb-1"><?php echo formatPrice($product['price']); ?></p>
                            <span class="badge product-condition badge-condition-<?php echo $product['condition']; ?>"><?php echo ucfirst($product['condition']); ?></span>
                            <p class="product-location mt-2 mb-0"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($product['seller_city'] ?? $product['location'] ?? 'South Africa'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-box-seam d-block"></i>
                        <h4>No products yet</h4>
                        <p>Be the first to list an item!</p>
                        <a href="<?php echo SITE_URL; ?>/pages/sell.php" class="btn btn-primary">Start Selling</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Latest Products -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title mb-0">Latest Listings</h3>
            <a href="<?php echo SITE_URL; ?>/pages/products.php?sort=newest" class="btn btn-outline-primary btn-sm">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            <?php if ($latest->num_rows > 0): ?>
                <?php while ($product = $latest->fetch_assoc()): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $product['id']; ?>">
                            <?php if ($product['image1']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                                <div class="card-img-top placeholder-img" style="height:200px;"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                <p class="product-title mb-1"><?php echo htmlspecialchars($product['title']); ?></p>
                            </a>
                            <p class="product-price mb-1"><?php echo formatPrice($product['price']); ?></p>
                            <span class="badge product-condition badge-condition-<?php echo $product['condition']; ?>"><?php echo ucfirst($product['condition']); ?></span>
                            <p class="product-seller mt-2 mb-0"><i class="bi bi-person"></i> <?php echo htmlspecialchars($product['username']); ?> &middot; <?php echo timeAgo($product['created_at']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-box-seam d-block"></i>
                        <h4>No listings yet</h4>
                        <p>Check back soon for new items!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5 bg-white how-it-works">
    <div class="container">
        <h3 class="section-title text-center">How It Works</h3>
        <div class="row g-4 mt-2">
            <div class="col-md-4">
                <div class="step">
                    <div class="step-icon"><i class="bi bi-person-plus"></i></div>
                    <h5>1. Sign Up Free</h5>
                    <p class="text-muted">Create your free account in seconds and join our community of buyers and sellers.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step">
                    <div class="step-icon"><i class="bi bi-camera"></i></div>
                    <h5>2. List Your Items</h5>
                    <p class="text-muted">Take photos, set your price, and publish your listing. It's quick and easy.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step">
                    <div class="step-icon"><i class="bi bi-cash-coin"></i></div>
                    <h5>3. Buy or Sell</h5>
                    <p class="text-muted">Connect with buyers or sellers directly. Complete transactions safely.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
