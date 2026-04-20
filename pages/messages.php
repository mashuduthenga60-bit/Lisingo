<?php
$page_title = 'Messages';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php', 'Please login.', 'warning');
}

$conn = getDBConnection();
$to_user = isset($_GET['to']) ? (int)$_GET['to'] : 0;
$product_id = isset($_GET['product']) ? (int)$_GET['product'] : 0;

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = sanitize($_POST['message'] ?? '');
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $prod_id = (int)($_POST['product_id'] ?? 0);

    if (!empty($message) && $receiver_id > 0) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, message) VALUES (?, ?, ?, ?)");
        $prod_val = $prod_id > 0 ? $prod_id : null;
        $stmt->bind_param("iiis", $_SESSION['user_id'], $receiver_id, $prod_val, $message);
        $stmt->execute();
        $stmt->close();
        redirect(SITE_URL . '/pages/messages.php?to=' . $receiver_id . ($prod_val ? '&product=' . $prod_val : ''), '', '');
    }
}

// Get conversations list
$conversations = $conn->query("SELECT DISTINCT 
    CASE WHEN sender_id = {$_SESSION['user_id']} THEN receiver_id ELSE sender_id END as other_user_id,
    MAX(m.created_at) as last_message_time
    FROM messages m 
    WHERE sender_id = {$_SESSION['user_id']} OR receiver_id = {$_SESSION['user_id']}
    GROUP BY other_user_id 
    ORDER BY last_message_time DESC");

// If viewing a specific conversation
if ($to_user > 0) {
    // Mark as read
    $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $to_user AND receiver_id = {$_SESSION['user_id']}");

    // Get messages
    $stmt = $conn->prepare("SELECT m.*, u.username, u.profile_image FROM messages m JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) 
        ORDER BY m.created_at ASC");
    $stmt->bind_param("iiii", $_SESSION['user_id'], $to_user, $to_user, $_SESSION['user_id']);
    $stmt->execute();
    $messages = $stmt->get_result();
    $stmt->close();

    $other_user = getUserById($to_user);
}
?>

<div class="container py-4">
    <h3 class="section-title"><i class="bi bi-chat-dots"></i> Messages</h3>

    <div class="row g-4">
        <!-- Conversations List -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Conversations</strong></div>
                <div class="message-list">
                    <?php if ($conversations->num_rows > 0): ?>
                        <?php while ($conv = $conversations->fetch_assoc()): 
                            $other = getUserById($conv['other_user_id']);
                            if (!$other) continue;
                            // Check unread count
                            $unread = $conn->query("SELECT COUNT(*) as c FROM messages WHERE sender_id = {$conv['other_user_id']} AND receiver_id = {$_SESSION['user_id']} AND is_read = 0")->fetch_assoc()['c'];
                        ?>
                        <a href="<?php echo SITE_URL; ?>/pages/messages.php?to=<?php echo $conv['other_user_id']; ?>" class="text-decoration-none">
                            <div class="message-item <?php echo $unread > 0 ? 'unread' : ''; ?> <?php echo $to_user == $conv['other_user_id'] ? 'bg-light' : ''; ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($other['username']); ?>&background=2563eb&color=fff" class="rounded-circle" width="40" height="40" alt="">
                                    <div>
                                        <strong class="text-dark"><?php echo htmlspecialchars($other['first_name'] . ' ' . $other['last_name']); ?></strong>
                                        <?php if ($unread > 0): ?><span class="badge bg-primary ms-1"><?php echo $unread; ?></span><?php endif; ?>
                                        <small class="d-block text-muted"><?php echo timeAgo($conv['last_message_time']); ?></small>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-3 text-center text-muted">No conversations yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="col-md-8">
            <?php if ($to_user > 0 && $other_user): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex align-items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($other_user['username']); ?>&background=2563eb&color=fff" class="rounded-circle" width="35" height="35" alt="">
                    <div>
                        <strong><?php echo htmlspecialchars($other_user['first_name'] . ' ' . $other_user['last_name']); ?></strong>
                        <small class="d-block text-muted">@<?php echo htmlspecialchars($other_user['username']); ?></small>
                    </div>
                </div>
                <div class="card-body" style="height:400px; overflow-y:auto;" id="chatArea">
                    <?php if ($messages->num_rows > 0): ?>
                        <?php while ($msg = $messages->fetch_assoc()): ?>
                        <div class="d-flex <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'justify-content-end' : 'justify-content-start'; ?>">
                            <div class="chat-bubble <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                <small class="<?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'text-white-50' : 'text-muted'; ?>" style="font-size:0.7rem;"><?php echo timeAgo($msg['created_at']); ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">Start a conversation</div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <form method="POST">
                        <input type="hidden" name="receiver_id" value="<?php echo $to_user; ?>">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="message" placeholder="Type your message..." required autofocus>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-chat-dots text-muted" style="font-size:4rem;"></i>
                        <h5 class="mt-3">Select a conversation</h5>
                        <p class="text-muted">Choose a conversation from the list or start a new one from a product page.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto scroll to bottom of chat
$(document).ready(function() {
    var chatArea = document.getElementById('chatArea');
    if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;
});
</script>

<?php $conn->close(); require_once __DIR__ . '/../includes/footer.php'; ?>
