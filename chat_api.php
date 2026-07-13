  <?php
// chat_api.php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// --- ACTION A: SEND MESSAGE ---
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message']);

    if (!empty($message_text)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
        $stmt->execute([$current_user_id, $receiver_id, $message_text]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Message text is empty']);
    }
    exit;
}

// --- ACTION B: FETCH CHAT STREAM ---
if ($action === 'fetch_messages') {
    $selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    
    if ($selected_user_id) {
        // Automatically mark incoming messages as read
        $update_stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $update_stmt->execute([$selected_user_id, $current_user_id]);

        // Fetch conversation records
        $msg_stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY sent_at ASC
        ");
        $msg_stmt->execute([$current_user_id, $selected_user_id, $selected_user_id, $current_user_id]);
        $messages = $msg_stmt->fetchAll();

        foreach ($messages as &$m) {
            $m['formatted_time'] = date('h:i A', strtotime($m['sent_at']));
            $m['is_mine'] = ($m['sender_id'] == $current_user_id);
        }
        
        header('Content-Type: application/json');
        echo json_encode($messages);
        exit;
    }
}

// --- ACTION C: FETCH UNREAD NOTIFICATIONS ---
if ($action === 'fetch_notifications') {
    $noti_stmt = $pdo->prepare("
        SELECT sender_id, COUNT(*) as unread_count 
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0 
        GROUP BY sender_id
    ");
    $noti_stmt->execute([$current_user_id]);
    $counts = $noti_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Output format: [sender_id => count]

    header('Content-Type: application/json');
    echo json_encode($counts ? $counts : new stdClass());
    exit;
}
