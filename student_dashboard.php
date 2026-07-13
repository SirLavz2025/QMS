 <?php
require 'db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') { header('Location: login.php'); exit; }

$quizzes = $pdo->query("SELECT q.*, c.name AS category_name FROM quizzes q LEFT JOIN categories c ON q.category_id = c.id")->fetchAll();
$attempts = $pdo->prepare("SELECT a.*, q.title FROM quiz_attempts a JOIN quizzes q ON a.quiz_id = q.id WHERE a.user_id = ? ORDER BY a.attempt_date DESC");
$attempts->execute([$_SESSION['user_id']]);
$history = $attempts->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Student Portal Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold"><i class="bi bi-mortarboard-fill me-2"></i>STUDENT HUB</span>
            <div>
                <span class="text-white me-3">Hello, <?= htmlspecialchars($_SESSION['name']); ?></span>
                <a href="student_chat.php" class="nav-link text-white"><i class="bi bi-chat-square-text me-2"></i> Contact Faculty</a>
                <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container my-5">
        <div class="row g-4">
            <div class="col-md-8">
                <h4>Assigned Examinations Available</h4>
                <div class="row g-3">
                    <?php foreach($quizzes as $quiz): ?>
                        <div class="col-md-6"><div class="card p-4 border-0 shadow-sm">
                            <span class="badge bg-secondary mb-2 w-fit" style="width:fit-content;"><?= htmlspecialchars($quiz['category_name']); ?></span>
                            <h5><?= htmlspecialchars($quiz['title']); ?></h5>
                            <p class="text-muted small">Allocated Running Time: <?= $quiz['duration']; ?> Mins</p>
                            <a href="take_quiz.php?id=<?= $quiz['id']; ?>" class="btn btn-primary w-100">Launch Quiz Engine</a>
                        </div></div>
                    <?php endforeach; if(empty($quizzes)) echo "<div class='alert alert-info'>No active assignments found.</div>"; ?>
                </div>
            </div>
            <div class="col-md-4">
                <h4>Your Evaluation History</h4>
                <div class="card p-3 border-0 shadow-sm">
                    <?php foreach($history as $h): ?>
                        <div class="p-2 mb-2 bg-white border-start border-4 border-success d-flex justify-content-between">
                            <div><strong class="small d-block text-truncate" style="max-width:180px;"><?= htmlspecialchars($h['title']); ?></strong><small class="text-muted"><?= $h['attempt_date']; ?></small></div>
                            <span class="badge bg-success my-auto fs-6"><?= $h['score']; ?>%</span>
                        </div>
                    <?php endforeach; if(empty($history)) echo "<p class='text-muted text-center'>No past evaluations.</p>"; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
