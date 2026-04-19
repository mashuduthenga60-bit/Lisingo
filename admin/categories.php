<?php
$page_title = 'Manage Categories';
require_once __DIR__ . '/includes/admin_header.php';

if (!hasPermission('can_manage_categories')) {
    redirect(SITE_URL . '/admin/', 'Access denied.', 'danger');
}

$conn = getDBConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $parent_id = (int)($_POST['parent_id'] ?? 0);
        $parent_val = $parent_id > 0 ? $parent_id : null;

        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $description, $parent_val);
            $stmt->execute();
            $stmt->close();
            redirect(SITE_URL . '/admin/categories.php', 'Category created.', 'success');
        }
    }

    if ($action === 'update') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if ($cat_id > 0 && !empty($name)) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $cat_id);
            $stmt->execute();
            $stmt->close();
            redirect(SITE_URL . '/admin/categories.php', 'Category updated.', 'success');
        }
    }

    if ($action === 'delete') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        if ($cat_id > 0) {
            // Set products to uncategorized
            $conn->query("UPDATE products SET category_id = NULL WHERE category_id = $cat_id");
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $cat_id);
            $stmt->execute();
            $stmt->close();
            redirect(SITE_URL . '/admin/categories.php', 'Category deleted.', 'success');
        }
    }
}

$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c WHERE c.parent_id IS NULL ORDER BY c.name");
?>

<div class="row g-4">
    <!-- Create Category -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong><i class="bi bi-plus-circle"></i> Add Category</strong></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Category</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>All Categories</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-1" id="editCat<?php echo $cat['id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <input type="text" class="form-control form-control-sm" name="name" value="<?php echo htmlspecialchars($cat['name']); ?>" style="min-width:120px;">
                                    </form>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="description" value="<?php echo htmlspecialchars($cat['description'] ?? ''); ?>" form="editCat<?php echo $cat['id']; ?>" style="min-width:150px;">
                                </td>
                                <td><span class="badge bg-secondary"><?php echo $cat['product_count']; ?></span></td>
                                <td>
                                    <button type="submit" form="editCat<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-check"></i></button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
