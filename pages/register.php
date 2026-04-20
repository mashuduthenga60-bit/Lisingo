<?php
$page_title = 'Sign Up';
require_once __DIR__ . '/../includes/header.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $province = sanitize($_POST['province'] ?? '');

    $errors = [];

    if (empty($username) || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($first_name)) $errors[] = 'First name is required.';
    if (empty($last_name)) $errors[] = 'Last name is required.';

    if (empty($errors)) {
        $conn = getDBConnection();

        // Check if username or email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, city, province, role_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 2)");
            $stmt->bind_param("ssssssss", $username, $email, $hashed, $first_name, $last_name, $phone, $city, $province);

            if ($stmt->execute()) {
                redirect(SITE_URL . '/pages/login.php', 'Account created successfully! Please login.', 'success');
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<div class="container py-4">
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <h2><i class="bi bi-person-plus text-primary"></i></h2>
                <h2>Create Account</h2>
                <p class="text-muted">Join Lisingo to start buying and selling</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo $first_name ?? ''; ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo $last_name ?? ''; ?>" required>
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-control" name="username" value="<?php echo $username ?? ''; ?>" minlength="3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address *</label>
                    <input type="email" class="form-control" name="email" value="<?php echo $email ?? ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" name="phone" value="<?php echo $phone ?? ''; ?>" placeholder="+27...">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" value="<?php echo $city ?? ''; ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Province</label>
                        <select class="form-select" name="province">
                            <option value="">Select...</option>
                            <option value="Eastern Cape" <?php echo ($province ?? '') === 'Eastern Cape' ? 'selected' : ''; ?>>Eastern Cape</option>
                            <option value="Free State" <?php echo ($province ?? '') === 'Free State' ? 'selected' : ''; ?>>Free State</option>
                            <option value="Gauteng" <?php echo ($province ?? '') === 'Gauteng' ? 'selected' : ''; ?>>Gauteng</option>
                            <option value="KwaZulu-Natal" <?php echo ($province ?? '') === 'KwaZulu-Natal' ? 'selected' : ''; ?>>KwaZulu-Natal</option>
                            <option value="Limpopo" <?php echo ($province ?? '') === 'Limpopo' ? 'selected' : ''; ?>>Limpopo</option>
                            <option value="Mpumalanga" <?php echo ($province ?? '') === 'Mpumalanga' ? 'selected' : ''; ?>>Mpumalanga</option>
                            <option value="Northern Cape" <?php echo ($province ?? '') === 'Northern Cape' ? 'selected' : ''; ?>>Northern Cape</option>
                            <option value="North West" <?php echo ($province ?? '') === 'North West' ? 'selected' : ''; ?>>North West</option>
                            <option value="Western Cape" <?php echo ($province ?? '') === 'Western Cape' ? 'selected' : ''; ?>>Western Cape</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <label class="form-label">Password *</label>
                    <input type="password" class="form-control" name="password" id="reg-password" minlength="8" required>
                    <div class="password-strength mt-1"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
            </form>

            <div class="text-center mt-3">
                <p class="text-muted">Already have an account? <a href="<?php echo SITE_URL; ?>/pages/login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
