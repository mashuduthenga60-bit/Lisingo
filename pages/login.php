<?php
$page_title = 'Login';
require_once __DIR__ . '/../includes/header.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];

    if (empty($email)) $errors[] = 'Email is required.';
    if (empty($password)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $errors[] = 'Your account has been ' . $user['status'] . '. Contact support.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

                redirect(SITE_URL . '/', 'Welcome back, ' . $user['first_name'] . '!', 'success');
            }
        } else {
            $errors[] = 'Invalid email or password.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<div class="container py-5">
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <h2><i class="bi bi-box-arrow-in-right text-primary"></i></h2>
                <h2>Welcome Back</h2>
                <p class="text-muted">Login to your Lisingo account</p>
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
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control form-control-lg" name="email" value="<?php echo $email ?? ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control form-control-lg" name="password" required>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 btn-lg">Login</button>
            </form>

            <div class="text-center mt-3">
                <p class="text-muted">Don't have an account? <a href="<?php echo SITE_URL; ?>/pages/register.php">Sign up free</a></p>
            </div>

            <!-- Demo Credentials -->
            <div class="mt-4 p-3 bg-light rounded">
                <small class="text-muted d-block mb-1"><strong>Demo Admin Login:</strong></small>
                <small class="text-muted">Email: admin@lisingo.co.za | Password: Admin@123</small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
