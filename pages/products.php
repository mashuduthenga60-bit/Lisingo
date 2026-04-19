<?php
$page_title = 'Browse Products';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();

// Filters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$condition = sanitize($_GET['condition'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Build query
$where = "WHERE p.status = 'active'";
$params = [];
$types = "";

if ($category_id > 0) {
    $where .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}
if ($condition && in_array($condition, ['new', 'used', 'refurbished'])) {
    $where .= " AND p.`condition` = ?";
    $params[] = $condition;
    $types .= "s";
}
if ($min_price > 0) {
    $where .= " AND p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price > 0) {
    $where .= " AND p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

$order = match($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'popular' => 'p.views DESC',
    default => 'p.created_at DESC'
};

// Count total
$count_sql = "SELECT COUNT(*) as total FROM products p $where";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$pagination = paginate($total, 12, $page);

// Fetch products
$sql = "SELECT p.*, u.username, u.city as seller_city, c.name as category_name 
        FROM products p 
        LEFT JOIN users u ON p.seller_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where ORDER BY $order LIMIT ? OFFSET ?";
$params[] = $pagination['per_page'];
$params[] = $pagination['offset'];
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
?>

<div class="container py-4">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-funnel"></i> Filters</h5>
                    <form method="GET">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Condition</label>
                            <select class="form-select" name="condition">
                                <option value="">All</option>
                                <option value="new" <?php echo $condition === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="used" <?php echo $condition === 'used' ? 'selected' : ''; ?>>Used</option>
                                <option value="refurbished" <?php echo $condition === 'refurbished' ? 'selected' : ''; ?>>Refurbished</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Price Range (R)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sort By</label>
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">Showing <?php echo $total; ?> results</p>
            </div>

            <div class="row g-3">
                <?php if ($products->num_rows > 0): ?>
                    <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="col-6 col-md-4">
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
                                <p class="product-location mt-2 mb-0"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($product['seller_city'] ?? $product['location'] ?? 'SA'); ?></p>
                                <p class="product-seller mb-0"><i class="bi bi-person"></i> <?php echo htmlspecialchars($product['username']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-search d-block"></i>
                            <h4>No products found</h4>
                            <p>Try adjusting your filters or search terms.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
