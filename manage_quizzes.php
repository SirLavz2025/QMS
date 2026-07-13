 <?php
require 'db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quiz'])) {
    $title = trim($_POST['title']);
    $category_id = intval($_POST['category_id']);
    $duration = intval($_POST['duration']);
    
    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO quizzes (category_id, title, duration, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$category_id, $title, $duration, $_SESSION['user_id']]);
    }
    header("Location: manage_quizzes.php"); exit;
}
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$quizzes = $pdo->query("SELECT q.*, c.name as category_name FROM quizzes q LEFT JOIN categories c ON q.category_id = c.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Manage Quizzes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="d-flex justify-content-between mb-4"><h3>Manage Quizzes</h3><a href="admin_dashboard.php" class="btn btn-secondary">Back</a></div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm">
                    <h5>New Quiz Details</h5>
                    <form method="POST">
                        <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                        <div class="mb-3"><label>Category</label>
                            <select name="category_id" class="form-select">
                                <?php foreach($categories as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                            </select>
                        </div>
                        <div class="mb-3"><label>Duration (Mins)</label><input type="number" name="duration" class="form-control" value="30" required></div>
                        <button type="submit" name="add_quiz" class="btn btn-primary w-100">Publish Quiz</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card p-4 border-0 shadow-sm">
                    <table class="table">
                        <thead><tr><th>Title</th><th>Category</th><th>Duration</th></tr></thead>
                        <tbody>
                            <?php foreach($quizzes as $q): ?>
    <tr>
        <td class="fw-semibold text-dark"><?= htmlspecialchars($q['title']); ?></td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($q['category_name'] ?? 'Unassigned'); ?></span></td>
        <td><i class="bi bi-clock me-1"></i> <?= $q['duration']; ?> Mins</td>
        <td class="text-end">
            <a href="manage_questions.php?quiz_id=<?= $q['id']; ?>" class="btn btn-sm btn-primary fw-medium">
                <i class="bi bi-gear-fill me-1"></i> Manage Questions
            </a>
        </td>
    </tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>