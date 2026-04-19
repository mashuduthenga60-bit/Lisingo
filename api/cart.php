<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$action = $_POST['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
$conn = getDBConnection();

switch ($action) {
    case 'add':
        // Check product exists and is not own product
        $stmt = $conn->prepare("SELECT seller_id, quantity, status FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product || $product['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Product not available']);
            break;
        }
        if ($product['seller_id'] == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot buy your own product']);
            break;
        }

        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $stmt->bind_param("iiii", $_SESSION['user_id'], $product_id, $quantity, $quantity);
        $stmt->execute();
        $stmt->close();

        $cart_count = getCartCount();
        echo json_encode(['success' => true, 'message' => 'Added to cart', 'cart_count' => $cart_count]);
        break;

    case 'update':
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $_SESSION['user_id'], $product_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        break;

    case 'remove':
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
