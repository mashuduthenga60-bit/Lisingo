<?php
$page_title = 'Manage Users';
require_once __DIR__ . '/includes/admin_header.php';

if (!hasPermission('can_manage_users')) {
    redirect(SITE_URL . '/admin/', 'Access denied.', 'danger');
}

$conn = getDBConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($action === 'update_role' && $user_id > 0) {
        $new_role = (int)$_POST['role_id'];
        // Only super_admin can assign super_admin role
        if ($new_role == 1 && !isSuperAdmin()) {
            redirect(SITE_URL . '/admin/users.php', 'Only super admins can assign super admin role.', 'danger');
        }
        $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_role, $user_id);
        $stmt->execute();
        $stmt->close();
        redirect(SITE_URL . '/admin/users.php', 'User role updated.', 'success');
    }

    if ($action === 'update_status' && $user_id > 0) {
        $new_status = sanitize($_POST['status']);
        if (in_array($new_status, ['active', 'suspended', 'banned'])) {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $user_id);
            $stmt->execute();
            $stmt->close();
            redirect(SITE_URL . '/admin/users.php', 'User status updated.', 'success');
        }
    }

    if ($action === 'delete' && $user_id > 0) {
        if ($user_id == $_SESSION['user_id']) {
            redirect(SITE_URL . '/admin/users.php', 'Cannot delete your own account.', 'danger');
        }
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        redirect(SITE_URL . '/admin/users.php', 'User deleted.', 'success');
    }

    if ($action === 'create') {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = password_hash($_POST['password'] ?? 'User@123', PASSWORD_DEFAULT);
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $role_id = (int)($_POST['role_id'] ?? 2);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $username, $email, $password, $first_name, $last_name, $role_id);
        if ($stmt->execute()) {
            redirect(SITE_URL . '/admin/users.php', 'User created successfully.', 'success');
        } else {
            $error = 'Failed to create user. Username or email may already exist.';
        }
        $stmt->close();
    }
}

// Get all users
$search = sanitize($_GET['search'] ?? '');
$role_filter = (int)($_GET['role'] ?? 0);

$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ?)";
    $s = "%$search%";
    $params = [$s, $s, $s];
    $types = "sss";
}
if ($role_filter > 0) {
    $where .= " AND u.role_id = ?";
    $params[] = $role_filter;
    $types .= "i";
}

$sql = "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id $where ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();

$roles = $conn->query("SELECT * FROM roles ORDER BY id");
$roles_arr = [];
while ($r = $roles->fetch_assoc()) $roles_arr[] = $r;
?>

<!-- Filters & Create -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                    <select class="form-select" name="role" style="width:auto;">
                        <option value="0">All Roles</option>
                        <?php foreach ($roles_arr as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $role_filter == $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="col-md-8 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="bi bi-person-plus"></i> Create User</button>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<!-- Users Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['first_name'] . '+' . $u['last_name']); ?>&background=2563eb&color=fff&size=32" class="rounded-circle" width="32" height="32" alt="">
                                <div>
                                    <strong><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></strong><br>
                                    <small class="text-muted">@<?php echo htmlspecialchars($u['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <select name="role_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    <?php foreach ($roles_arr as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" <?php echo $u['role_id'] == $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['role_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                                    <option value="active" <?php echo $u['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo $u['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="banned" <?php echo $u['status'] === 'banned' ? 'selected' : ''; ?>>Banned</option>
                                </select>
                            </form>
                        </td>
                        <td><small><?php echo date('d M Y', strtotime($u['created_at'])); ?></small></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/admin/user_edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger btn-delete-confirm" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" value="User@123" required>
                        <small class="text-muted">Default: User@123</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role_id" required>
                            <?php foreach ($roles_arr as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == 2 ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['role_name']); ?> - <?php echo htmlspecialchars($r['description']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $stmt->close(); $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
