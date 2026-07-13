  <?php
// chat.php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];

// Query directory lists based on user role permissions
if ($current_role === 'admin') {
    $contact_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE role = 'student' ORDER BY name ASC");
} else {
    $contact_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE role = 'admin' ORDER BY name ASC");
}
$contact_stmt->execute();
$contacts = $contact_stmt->fetchAll();

$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$selected_user = null;

if ($selected_user_id) {
    $user_stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $user_stmt->execute([$selected_user_id]);
    $selected_user = $user_stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Chat Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .chat-container { height: calc(100vh - 120px); min-height: 500px; }
        .message-stream { height: calc(100vh - 250px); min-height: 350px; overflow-y: auto; }
        .contact-list { height: calc(100vh - 180px); overflow-y: auto; }
        .unread-badge { display: none; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold text-uppercase">
                <i class="bi bi-chat-dots-fill me-2 text-primary"></i>Live Chat Portal
            </span>
            <a href="<?= $current_role === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'; ?>" class="btn btn-sm btn-outline-light">
                <i class="bi bi-box-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </nav>

    <div class="container my-4">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="row g-0 chat-container">
                
                <div class="col-md-4 border-end bg-white d-flex flex-column">
                    <div class="p-3 bg-light border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">Active User Directory</h6>
                    </div>
                    <div class="list-group list-group-flush contact-list flex-grow-1">
                        <?php if (count($contacts) > 0): ?>
                            <?php foreach ($contacts as $ct): ?>
                                <a href="chat.php?user_id=<?= $ct['id']; ?>" 
                                   class="list-group-item list-group-item-action p-3 border-bottom d-flex justify-content-between align-items-center <?= $selected_user_id === $ct['id'] ? 'bg-primary text-white active' : ''; ?>"
                                   data-contact-id="<?= $ct['id']; ?>">
                                    <div>
                                        <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($ct['name']); ?></h6>
                                        <small class="<?= $selected_user_id === $ct['id'] ? 'text-white-50' : 'text-muted'; ?> text-uppercase fs-8">
                                            <?= htmlspecialchars($ct['role']); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge bg-danger rounded-pill unread-badge" id="badge-<?= $ct['id']; ?>">0</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">No contacts found.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-8 d-flex flex-column bg-light justify-content-between">
                    <?php if ($selected_user): ?>
                        
                        <div class="p-3 bg-white border-bottom shadow-sm">
                            <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($selected_user['name']); ?></h6>
                            <span class="badge bg-secondary-subtle text-secondary text-uppercase fs-8"><?= $selected_user['role']; ?></span>
                        </div>

                        <div class="p-4 message-stream d-flex flex-column gap-3 bg-light-subtle" id="chatStream">
                            </div>

                        <div class="p-3 bg-white border-top shadow-sm">
                            <form id="chatForm">
                                <div class="input-group">
                                    <input type="text" id="messageText" class="form-control border-light-subtle py-2 bg-light shadow-none" 
                                           placeholder="Type your message..." required autocomplete="off" autofocus>
                                    <button class="btn btn-primary px-4 shadow-sm" type="submit">
                                        <i class="bi bi-send-fill"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                    <?php else: ?>
                        <div class="m-auto text-center py-5 px-3">
                            <i class="bi bi-chat-square-dots text-secondary opacity-25 display-2 mb-3 d-block"></i>
                            <h5 class="text-dark fw-bold mb-1">No Active Conversation</h5>
                            <p class="text-muted small">Select a user profile from the directory layout side menu to begin chatting.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
        const selectedUserId = <?= json_encode($selected_user_id); ?>;
        let lastMessageCount = 0;

        // 1. LIVE MESSAGE RETRIEVAL (Runs every 2 seconds)
        function fetchMessages() {
            if (!selectedUserId) return;

            fetch(`chat_api.php?action=fetch_messages&user_id=${selectedUserId}`)
                .then(res => res.json())
                .then(messages => {
                    const stream = document.getElementById('chatStream');
                    if (!stream) return;

                    let htmlContent = '';
                    messages.forEach(msg => {
                        const isMine = msg.is_mine;
                        htmlContent += `
                            <div class="d-flex ${isMine ? 'justify-content-end' : 'justify-content-start'}">
                                <div class="card border-0 px-3 py-2 shadow-sm rounded-3" 
                                     style="max-width: 75%; ${isMine ? 'background-color: #0d6efd; color: white;' : 'background-color: #ffffff; color: #212529;'}">
                                    <p class="mb-1 text-break">${escapeHtml(msg.message_text)}</p>
                                    <small class="d-block text-end opacity-70" style="font-size: 0.7rem;">
                                        ${msg.formatted_time}
                                    </small>
                                </div>
                            </div>`;
                    });

                    stream.innerHTML = htmlContent;

                    // Automatically drop down scroll focus when new items land natively
                    if (messages.length > lastMessageCount) {
                        stream.scrollTop = stream.scrollHeight;
                        lastMessageCount = messages.length;
                    }
                })
                .catch(err => console.error("Message Sync Error:", err));
        }

        // 2. LIVE SIDE DIRECTORY NOTIFICATIONS POLL (Runs every 4 seconds)
        function fetchNotifications() {
            fetch('chat_api.php?action=fetch_notifications')
                .then(res => res.json())
                .then(counts => {
                    document.querySelectorAll('.unread-badge').forEach(badge => {
                        badge.style.display = 'none';
                        badge.textContent = '0';
                    });

                    Object.keys(counts).forEach(senderId => {
                        if (parseInt(senderId) === selectedUserId) return; // Ignore active conversation window

                        const badge = document.getElementById(`badge-${senderId}`);
                        if (badge && counts[senderId] > 0) {
                            badge.textContent = counts[senderId];
                            badge.style.display = 'inline-block';
                        }
                    });
                })
                .catch(err => console.error("Notification Sync Error:", err));
        }

        // 3. SILENT INTERCEPT SUBMIT EVENT
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const input = document.getElementById('messageText');
                const message = input.value.trim();
                if (!message) return;

                const formData = new FormData();
                formData.append('receiver_id', selectedUserId);
                formData.append('message', message);

                input.value = ''; // Instantly clear input field for responsive feel

                fetch('chat_api.php?action=send', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        fetchMessages(); // Pull updates immediately on submit execution
                    }
                });
            });
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // --- 4. ENGINE TIMER INITIALIZATION ---
        if (selectedUserId) {
            fetchMessages();
            setInterval(fetchMessages, 2000); // Check for new messages every 2 seconds
        }
        
        fetchNotifications();
        setInterval(fetchNotifications, 4000); // Check for unread alerts every 4 seconds
    </script>
</body>
</html>
