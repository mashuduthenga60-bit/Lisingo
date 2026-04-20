<?php
$page_title = 'Sell an Item';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login to sell items.', 'warning');
}

$conn = getDBConnection();
$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition = sanitize($_POST['condition'] ?? 'used');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $location = sanitize($_POST['location'] ?? '');

    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if ($price <= 0) $errors[] = 'Valid price is required.';
    if ($category_id <= 0) $errors[] = 'Category is required.';

    // Handle images
    $image_fields = ['image1', 'image2', 'image3', 'image4'];
    $image_names = [];
    foreach ($image_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $result = uploadImage($_FILES[$field], 'products');
            if ($result['success']) {
                $image_names[$field] = $result['filename'];
            } else {
                $errors[] = $result['message'];
            }
        }
    }

    if (empty($errors)) {
        $img1 = $image_names['image1'] ?? null;
        $img2 = $image_names['image2'] ?? null;
        $img3 = $image_names['image3'] ?? null;
        $img4 = $image_names['image4'] ?? null;

        $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, title, description, price, `condition`, quantity, image1, image2, image3, image4, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("iissdsisssss", $_SESSION['user_id'], $category_id, $title, $description, $price, $condition, $quantity, $img1, $img2, $img3, $img4, $location);

        if ($stmt->execute()) {
            $product_id = $conn->insert_id;
            redirect(SITE_URL . '/pages/product_detail.php?id=' . $product_id, 'Product listed successfully!', 'success');
        } else {
            $errors[] = 'Failed to list product. Please try again.';
        }
        $stmt->close();
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="sell-form">
                <h3 class="mb-4"><i class="bi bi-plus-circle text-primary"></i> List an Item for Sale</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Product Title *</label>
                        <input type="text" class="form-control" name="title" value="<?php echo $title ?? ''; ?>" placeholder="e.g., iPhone 14 Pro Max 256GB" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Category *</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Condition *</label>
                            <select class="form-select" name="condition" required>
                                <option value="new" <?php echo ($condition ?? '') === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="used" <?php echo ($condition ?? 'used') === 'used' ? 'selected' : ''; ?>>Used</option>
                                <option value="refurbished" <?php echo ($condition ?? '') === 'refurbished' ? 'selected' : ''; ?>>Refurbished</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Price (ZAR) *</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" class="form-control" name="price" step="0.01" min="1" value="<?php echo $price ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="1" value="<?php echo $quantity ?? 1; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Location</label>
                            <input type="text" class="form-control" name="location" value="<?php echo $location ?? ''; ?>" placeholder="e.g., Pretoria, Gauteng">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Description *</label>
                        <textarea class="form-control" name="description" rows="5" placeholder="Describe your item in detail..." required><?php echo $description ?? ''; ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Product Images (up to 4)</label>
                        <div class="row g-3">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="col-6 col-md-3">
                                <div class="image-preview" onclick="document.getElementById('image<?php echo $i; ?>').click()">
                                    <div id="preview<?php echo $i; ?>" class="text-center">
                                        <i class="bi bi-camera text-muted" style="font-size:2rem;"></i>
                                        <small class="d-block text-muted">Image <?php echo $i; ?></small>
                                    </div>
                                </div>
                                <input type="file" id="image<?php echo $i; ?>" name="image<?php echo $i; ?>" class="d-none image-upload" data-preview="preview<?php echo $i; ?>" accept="image/*">
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check-lg"></i> Publish Listing</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
