<?php
$page_title = 'Search';
require_once __DIR__ . '/../includes/header.php';

$q = sanitize($_GET['q'] ?? '');
$conn = getDBConnection();
$products = null;

if (!empty($q)) {
    $search = "%$q%";
    $stmt = $conn->prepare("SELECT p.*, u.username, u.city as seller_city, c.name as category_name 
        FROM products p 
        LEFT JOIN users u ON p.seller_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' AND (p.title LIKE ? OR p.description LIKE ? OR c.name LIKE ?) 
        ORDER BY p.created_at DESC LIMIT 50");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $products = $stmt->get_result();
}
?>

<div class="container py-4">
    <h3 class="section-title"><i class="bi bi-search"></i> Search Results</h3>
    <?php if (!empty($q)): ?>
        <p class="text-muted mb-4">Results for "<strong><?php echo htmlspecialchars($q); ?></strong>" <?php echo $products ? '(' . $products->num_rows . ' found)' : ''; ?></p>
    <?php endif; ?>

    <div class="row g-3">
        <?php if ($products && $products->num_rows > 0): ?>
            <?php while ($product = $products->fetch_assoc()): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $product['id']; ?>">
                        <?php if ($product['image1']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" class="card-img-top" alt="">
                        <?php else: ?>
                            <div class="card-img-top placeholder-img" style="height:200px;"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </a>
                    <div class="card-body">
                        <p class="product-title mb-1"><?php echo htmlspecialchars($product['title']); ?></p>
                        <p class="product-price mb-1"><?php echo formatPrice($product['price']); ?></p>
                        <span class="badge product-condition badge-condition-<?php echo $product['condition']; ?>"><?php echo ucfirst($product['condition']); ?></span>
                        <p class="product-location mt-2 mb-0"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($product['seller_city'] ?? 'SA'); ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="bi bi-search d-block"></i>
                    <h4><?php echo empty($q) ? 'Enter a search term' : 'No results found'; ?></h4>
                    <p><?php echo empty($q) ? 'Type something to search for products.' : 'Try different keywords or browse our categories.'; ?></p>
                    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn btn-primary">Browse All Products</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
