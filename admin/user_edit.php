<?php
$page_title = 'Edit User';
require_once __DIR__ . '/includes/admin_header.php';

if (!hasPermission('can_manage_users')) {
    redirect(SITE_URL . '/admin/', 'Access denied.', 'danger');
}

$id = (int)($_GET['id'] ?? 0);
$conn = getDBConnection();
$user = getUserById($id);

if (!$user) {
    redirect(SITE_URL . '/admin/users.php', 'User not found.', 'danger');
}

$roles = $conn->query("SELECT * FROM roles ORDER BY id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 2);
    $status = sanitize($_POST['status'] ?? 'active');

    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, city=?, province=?, role_id=?, status=? WHERE id=?");
    $stmt->bind_param("ssssssiis", $first_name, $last_name, $email, $phone, $city, $province, $role_id, $status, $id);

    if ($stmt->execute()) {
        // Update password if provided
        if (!empty($_POST['password'])) {
            $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwd_stmt->bind_param("si", $hashed, $id);
            $pwd_stmt->execute();
            $pwd_stmt->close();
        }
        redirect(SITE_URL . '/admin/users.php', 'User updated successfully.', 'success');
    } else {
        $error = 'Update failed.';
    }
    $stmt->close();
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h4 class="mb-4">Edit User: <?php echo htmlspecialchars($user['username']); ?></h4>
                <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control" name="province" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role_id">
                                <?php while ($r = $roles->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id']; ?>" <?php echo $user['role_id'] == $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['role_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="banned" <?php echo $user['status'] === 'banned' ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/includes/admin_footer.php'; ?>
