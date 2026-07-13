<?php
require 'db.php';
session_start();

// 1. Protection Gate: Ensure the user is actually authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];

// 2. Handle Message Submission (AJAX POST fallback to Self)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['receiver_id'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message']);

    if (!empty($message_text)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
        $stmt->execute([$current_user_id, $receiver_id, $message_text]);
    }
    // Redirect to clear form variables and prevent duplicate submissions on page refresh
    header("Location: chat.php?user_id=" . $receiver_id);
    exit;
}

// 3. Fetch Contacts: Faculty sees all students; Students see all faculty admins
if ($current_role === 'admin') {
    $contact_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE role = 'student' ORDER BY name ASC");
} else {
    $contact_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE role = 'admin' ORDER BY name ASC");
}
$contact_stmt->execute();
$contacts = $contact_stmt->fetchAll();

// 4. Fetch Conversation Logs if a contact is actively targeted/selected
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$messages = [];
$selected_user = null;

if ($selected_user_id) {
    // Confirm the selected target user profile exists
    $user_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $user_stmt->execute([$selected_user_id]);
    $selected_user = $user_stmt->fetch();

    if ($selected_user) {
        // Automatically mark unread messages coming from this user to you as read (1)
        $update_stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $update_stmt->execute([$selected_user_id, $current_user_id]);

        // Fix: Using simple positional placeholders to avoid driver issues with duplicated named params
        $msg_stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY sent_at ASC
        ");
        // Pass parameters in the exact sequential order they appear in the query
        $msg_stmt->execute([$current_user_id, $selected_user_id, $selected_user_id, $current_user_id]);
        $messages = $msg_stmt->fetchAll();
    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz System - Communication Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .chat-container { height: calc(100vh - 120px); min-height: 500px; }
        .message-stream { height: calc(100vh - 250px); min-height: 350px; overflow-y: auto; }
        .contact-list { height: calc(100vh - 180px); overflow-y: auto; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold text-uppercase tracking-wide">
                <i class="bi bi-chat-right-text-fill me-2 text-primary"></i>Portal Chatroom
            </span>
            <a href="<?= $current_role === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container my-4">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="row g-0 chat-container">
                
                <div class="col-md-4 border-end bg-white d-flex flex-column">
                    <div class="p-3 bg-light border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">Active Contacts Directory</h6>
                        <small class="text-muted text-capitalize">Logged in as: <?= $current_role; ?></small>
                    </div>
                    <div class="list-group list-group-flush contact-list flex-grow-1">
                        <?php if (count($contacts) > 0): ?>
                            <?php foreach ($contacts as $ct): ?>
                                <a href="chat.php?user_id=<?= $ct['id']; ?>" 
                                   class="list-group-item list-group-item-action p-3 border-bottom d-flex justify-content-between align-items-center <?= $selected_user_id === $ct['id'] ? 'bg-primary text-white active' : ''; ?>">
                                    <div>
                                        <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($ct['name']); ?></h6>
                                        <small class="<?= $selected_user_id === $ct['id'] ? 'text-white-50' : 'text-muted'; ?> text-uppercase fs-8">
                                            <?= htmlspecialchars($ct['role']); ?>
                                        </small>
                                    </div>
                                    <i class="bi bi-chevron-right opacity-50"></i>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">No communication contacts found in the directory database table records.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-8 d-flex flex-column bg-light justify-content-between">
                    <?php if ($selected_user): ?>
                        
                        <div class="p-3 bg-white border-bottom shadow-sm d-flex align-items-center">
                            <i class="bi bi-person-circle fs-4 text-secondary me-2"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($selected_user['name']); ?></h6>
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase px-2 fs-8"><?= $selected_user['role']; ?></span>
                            </div>
                        </div>

                        <div class="p-4 message-stream d-flex flex-column gap-3 bg-light-subtle" id="chatStream">
                            <?php if (count($messages) > 0): ?>
                                <?php foreach ($messages as $m): 
                                    $is_mine = ($m['sender_id'] == $current_user_id);
                                ?>
                                    <div class="d-flex <?= $is_mine ? 'justify-content-end' : 'justify-content-start'; ?>">
                                        <div class="card border-0 px-3 py-2 shadow-sm rounded-3" 
                                             style="max-width: 75%; <?= $is_mine ? 'background-color: #0d6efd; color: white;' : 'background-color: #ffffff; color: #212529;'; ?>">
                                            <p class="mb-1 text-break fs-6"><?= htmlspecialchars($m['message_text']); ?></p>
                                            <small class="d-block text-end opacity-70" style="font-size: 0.7rem;">
                                                <?= date('h:i A', strtotime($m['sent_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="m-auto text-center text-muted py-5">
                                    <i class="bi bi-chat-left-dots fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <p class="small">No messages recorded yet. Type a message below to start the conversation.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-3 bg-white border-top shadow-sm">
                            <form method="POST" action="chat.php?user_id=<?= $selected_user_id; ?>">
                                <input type="hidden" name="receiver_id" value="<?= $selected_user_id; ?>">
                                <div class="input-group">
                                    <input type="text" name="message" class="form-control border-light-subtle py-2 bg-light shadow-none" 
                                           placeholder="Write a message response here..." required autocomplete="off" autofocus>
                                    <button class="btn btn-primary px-4 shadow-sm" type="submit">
                                        <i class="bi bi-send-fill"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                    <?php else: ?>
                        <div class="m-auto text-center py-5 px-3">
                            <i class="bi bi-chat-square-dots text-secondary opacity-25 display-2 mb-3 d-block"></i>
                            <h5 class="text-dark fw-bold mb-1">No Active Chat Session Selected</h5>
                            <p class="text-muted small max-width-300 mx-auto">Click on one of the registered users in the active contacts directory array panel to initialize a text conversation link stream.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
        var stream = document.getElementById('chatStream');
        if(stream) {
            stream.scrollTop = stream.scrollHeight;
        }
    </script>
</body>
</html>