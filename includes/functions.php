<?php
require_once __DIR__ . '/../config/database.php';

// Sanitize input (trim only - output escaping handled at display layer, SQL injection prevented by prepared statements)
function sanitize($data) {
    return trim($data);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1, 4]);
}

// Check if user is super admin
function isSuperAdmin() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

// Check if user is moderator
function isModerator() {
    return isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1, 3, 4]);
}

// Get user by ID
function getUserById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user;
}

// Get role permissions
function getRolePermissions($role_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $role;
}

// Check specific permission
function hasPermission($permission) {
    if (!isset($_SESSION['role_id'])) return false;
    $role = getRolePermissions($_SESSION['role_id']);
    return $role && isset($role[$permission]) && $role[$permission] == 1;
}

// Redirect with message
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    }
    return '';
}

// Format price in ZAR
function formatPrice($price) {
    return 'R ' . number_format($price, 2);
}

// Time ago function
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// Upload image
function uploadImage($file, $directory = 'products') {
    $target_dir = __DIR__ . '/../uploads/' . $directory . '/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($file_ext, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp'];
    }

    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'File too large. Max 5MB.'];
    }

    $new_name = uniqid() . '_' . time() . '.' . $file_ext;
    $target_path = $target_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $new_name];
    }

    return ['success' => false, 'message' => 'Upload failed.'];
}

// Get cart count
function getCartCount() {
    if (!isLoggedIn()) return 0;
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $result['count'] ?? 0;
}

// Get unread messages count
function getUnreadCount() {
    if (!isLoggedIn()) return 0;
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $result['count'] ?? 0;
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Paginate results
function paginate($total, $per_page = 12, $current_page = 1) {
    $total_pages = ceil($total / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;

    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset
    ];
}
?>
