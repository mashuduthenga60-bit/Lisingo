<?php
$page_title = 'Edit Profile';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login.', 'warning');
}

$conn = getDBConnection();
$user = getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $zip_code = sanitize($_POST['zip_code'] ?? '');

    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $result = uploadImage($_FILES['profile_image'], 'profiles');
        if ($result['success']) {
            $profile_image = $result['filename'];
        }
    }

    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=?, province=?, zip_code=?, profile_image=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $first_name, $last_name, $phone, $address, $city, $province, $zip_code, $profile_image, $_SESSION['user_id']);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $first_name . ' ' . $last_name;
        redirect(SITE_URL . '/pages/profile.php', 'Profile updated successfully!', 'success');
    } else {
        $error = 'Update failed. Please try again.';
    }
    $stmt->close();
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="sell-form">
                <h3 class="mb-4"><i class="bi bi-person-gear text-primary"></i> Edit Profile</h3>
                <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <img src="<?php echo SITE_URL; ?>/uploads/profiles/<?php echo $user['profile_image']; ?>" class="rounded-circle mb-2" width="100" height="100" style="object-fit:cover;" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name']); ?>&background=2563eb&color=fff'">
                        <div><input type="file" name="profile_image" class="form-control form-control-sm d-inline-block" style="max-width:250px;" accept="image/*"></div>
                    </div>
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
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Province</label>
                            <select class="form-select" name="province">
                                <option value="">Select...</option>
                                <?php foreach (['Eastern Cape','Free State','Gauteng','KwaZulu-Natal','Limpopo','Mpumalanga','Northern Cape','North West','Western Cape'] as $prov): ?>
                                    <option value="<?php echo $prov; ?>" <?php echo ($user['province'] ?? '') === $prov ? 'selected' : ''; ?>><?php echo $prov; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Zip Code</label>
                            <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check-lg"></i> Save Changes</button>
                        <a href="<?php echo SITE_URL; ?>/pages/profile.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
