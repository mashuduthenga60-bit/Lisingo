<?php
$page_title = 'Roles & Permissions';
require_once __DIR__ . '/includes/admin_header.php';

if (!hasPermission('can_manage_roles')) {
    redirect(SITE_URL . '/admin/', 'Access denied. Only Super Admins can manage roles.', 'danger');
}

$conn = getDBConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $role_name = sanitize($_POST['role_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $can_manage_users = isset($_POST['can_manage_users']) ? 1 : 0;
        $can_manage_products = isset($_POST['can_manage_products']) ? 1 : 0;
        $can_manage_orders = isset($_POST['can_manage_orders']) ? 1 : 0;
        $can_manage_categories = isset($_POST['can_manage_categories']) ? 1 : 0;
        $can_manage_roles = isset($_POST['can_manage_roles']) ? 1 : 0;
        $can_view_reports = isset($_POST['can_view_reports']) ? 1 : 0;

        if (!empty($role_name)) {
            $stmt = $conn->prepare("INSERT INTO roles (role_name, description, can_manage_users, can_manage_products, can_manage_orders, can_manage_categories, can_manage_roles, can_view_reports) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiiii", $role_name, $description, $can_manage_users, $can_manage_products, $can_manage_orders, $can_manage_categories, $can_manage_roles, $can_view_reports);
            if ($stmt->execute()) {
                redirect(SITE_URL . '/admin/roles.php', 'Role created successfully.', 'success');
            } else {
                $error = 'Role name already exists.';
            }
            $stmt->close();
        }
    }

    if ($action === 'update') {
        $role_id = (int)($_POST['role_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $can_manage_users = isset($_POST['can_manage_users']) ? 1 : 0;
        $can_manage_products = isset($_POST['can_manage_products']) ? 1 : 0;
        $can_manage_orders = isset($_POST['can_manage_orders']) ? 1 : 0;
        $can_manage_categories = isset($_POST['can_manage_categories']) ? 1 : 0;
        $can_manage_roles = isset($_POST['can_manage_roles']) ? 1 : 0;
        $can_view_reports = isset($_POST['can_view_reports']) ? 1 : 0;

        if ($role_id > 0) {
            $stmt = $conn->prepare("UPDATE roles SET description=?, can_manage_users=?, can_manage_products=?, can_manage_orders=?, can_manage_categories=?, can_manage_roles=?, can_view_reports=? WHERE id=?");
            $stmt->bind_param("siiiiiii", $description, $can_manage_users, $can_manage_products, $can_manage_orders, $can_manage_categories, $can_manage_roles, $can_view_reports, $role_id);
            $stmt->execute();
            $stmt->close();
            redirect(SITE_URL . '/admin/roles.php', 'Role updated.', 'success');
        }
    }

    if ($action === 'delete') {
        $role_id = (int)($_POST['role_id'] ?? 0);
        if ($role_id > 1 && $role_id > 0) { // Cannot delete super_admin
            // Move users to default role
            $conn->query("UPDATE users SET role_id = 2 WHERE role_id = $role_id");
            $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->bind_param("i", $role_id);
            $stmt->execute();
            $stmt->close();
            redirect(SITE_URL . '/admin/roles.php', 'Role deleted. Affected users moved to default role.', 'success');
        }
    }
}

$roles = $conn->query("SELECT r.*, (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count FROM roles r ORDER BY r.id");
$permissions = ['can_manage_users', 'can_manage_products', 'can_manage_orders', 'can_manage_categories', 'can_manage_roles', 'can_view_reports'];
$perm_labels = ['Users', 'Products', 'Orders', 'Categories', 'Roles', 'Reports'];
?>

<?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div class="row g-4">
    <!-- Create Role -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong><i class="bi bi-shield-plus"></i> Create New Role</strong></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Role Name *</label>
                        <input type="text" class="form-control" name="role_name" required placeholder="e.g., editor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description" placeholder="Brief description">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Permissions</label>
                        <?php foreach ($permissions as $i => $perm): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="<?php echo $perm; ?>" id="new_<?php echo $perm; ?>">
                            <label class="form-check-label" for="new_<?php echo $perm; ?>">Manage <?php echo $perm_labels[$i]; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create Role</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Roles List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>All Roles</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Role</th>
                                <th>Description</th>
                                <th>Users</th>
                                <?php foreach ($perm_labels as $label): ?>
                                    <th class="text-center"><small><?php echo $label; ?></small></th>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($role = $roles->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($role['role_name']); ?></strong>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($role['description'] ?? ''); ?></small></td>
                                <td><span class="badge bg-secondary"><?php echo $role['user_count']; ?></span></td>
                                <?php foreach ($permissions as $perm): ?>
                                <td class="text-center">
                                    <?php if ($role[$perm]): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle text-danger"></i>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRole<?php echo $role['id']; ?>"><i class="bi bi-pencil"></i></button>
                                    <?php if ($role['id'] > 1): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger btn-delete-confirm"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Edit Role Modal -->
                            <div class="modal fade" id="editRole<?php echo $role['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Role: <?php echo htmlspecialchars($role['role_name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <input type="text" class="form-control" name="description" value="<?php echo htmlspecialchars($role['description'] ?? ''); ?>">
                                                </div>
                                                <label class="form-label fw-bold">Permissions</label>
                                                <?php foreach ($permissions as $j => $perm): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="<?php echo $perm; ?>" id="edit_<?php echo $role['id'] . '_' . $perm; ?>" <?php echo $role[$perm] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="edit_<?php echo $role['id'] . '_' . $perm; ?>">Manage <?php echo $perm_labels[$j]; ?></label>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
