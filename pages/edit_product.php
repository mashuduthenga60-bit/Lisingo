<?php
$page_title = 'Edit Product';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login.', 'warning');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product && !isAdmin()) {
    redirect(SITE_URL . '/pages/my_products.php', 'Product not found.', 'danger');
}

// Admin override
if (!$product && isAdmin()) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition = sanitize($_POST['condition'] ?? 'used');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $location = sanitize($_POST['location'] ?? '');
    $status = sanitize($_POST['status'] ?? 'active');

    $errors = [];
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if ($price <= 0) $errors[] = 'Valid price is required.';

    // Handle new images
    $image_fields = ['image1', 'image2', 'image3', 'image4'];
    $updates = [];
    foreach ($image_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $result = uploadImage($_FILES[$field], 'products');
            if ($result['success']) {
                $updates[$field] = $result['filename'];
            }
        } else {
            $updates[$field] = $product[$field];
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE products SET title=?, description=?, price=?, category_id=?, `condition`=?, quantity=?, location=?, status=?, image1=?, image2=?, image3=?, image4=? WHERE id=?");
        $stmt->bind_param("ssdissssssssi", $title, $description, $price, $category_id, $condition, $quantity, $location, $status, $updates['image1'], $updates['image2'], $updates['image3'], $updates['image4'], $id);

        if ($stmt->execute()) {
            redirect(SITE_URL . '/pages/product_detail.php?id=' . $id, 'Product updated successfully!', 'success');
        } else {
            $errors[] = 'Update failed.';
        }
        $stmt->close();
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="sell-form">
                <h3 class="mb-4"><i class="bi bi-pencil text-primary"></i> Edit Product</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Product Title *</label>
                        <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Category</label>
                            <select class="form-select" name="category_id">
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Condition</label>
                            <select class="form-select" name="condition">
                                <option value="new" <?php echo $product['condition'] === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="used" <?php echo $product['condition'] === 'used' ? 'selected' : ''; ?>>Used</option>
                                <option value="refurbished" <?php echo $product['condition'] === 'refurbished' ? 'selected' : ''; ?>>Refurbished</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Price (R)</label>
                            <input type="number" class="form-control" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="0" value="<?php echo $product['quantity']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="sold" <?php echo $product['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                <option value="pending" <?php echo $product['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location</label>
                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description *</label>
                        <textarea class="form-control" name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Update Images</label>
                        <div class="row g-3">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="col-6 col-md-3">
                                <div class="image-preview" onclick="document.getElementById('image<?php echo $i; ?>').click()">
                                    <div id="preview<?php echo $i; ?>">
                                        <?php if ($product["image$i"]): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $product["image$i"]; ?>" alt="">
                                        <?php else: ?>
                                            <i class="bi bi-camera text-muted" style="font-size:2rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <input type="file" id="image<?php echo $i; ?>" name="image<?php echo $i; ?>" class="d-none image-upload" data-preview="preview<?php echo $i; ?>" accept="image/*">
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1"><i class="bi bi-check-lg"></i> Update Product</button>
                        <a href="<?php echo SITE_URL; ?>/pages/product_detail.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
