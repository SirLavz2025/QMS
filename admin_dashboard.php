 <?php
require 'db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }

$totalQuizzes = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalAttempts = $pdo->query("SELECT COUNT(*) FROM quiz_attempts")->fetchColumn();
$avgScore = $pdo->query("SELECT AVG(score) FROM quiz_attempts")->fetchColumn() ?: 0;

$attempts = $pdo->query("SELECT a.*, u.name as student_name, q.title as quiz_title FROM quiz_attempts a JOIN users u ON a.user_id = u.id JOIN quizzes q ON a.quiz_id = q.id ORDER BY a.attempt_date DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Faculty Command Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <nav class="col-md-2 bg-dark text-white p-3 d-flex flex-column justify-content-between">
                <div>
                    <h4 class="text-center mb-4 fw-bold"><i class="bi bi-mortarboard-fill me-2"></i>FACULTY</h4>
                    <ul class="nav flex-column gap-2">
                        <li class="nav-item"><a href="admin_dashboard.php" class="nav-link text-white active bg-primary rounded"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                        <li class="nav-item"><a href="manage_quizzes.php" class="nav-link text-white"><i class="bi bi-journal-text me-2"></i> Quizzes</a></li>
                        <li class="nav-item"><a href="manage_categories.php" class="nav-link text-white"><i class="bi bi-tags me-2"></i> Categories</a></li>
                        <li class="nav-item"><a href="manage_users.php" class="nav-link text-white"><i class="bi bi-people me-2"></i> Users</a></li>
                        <li class="nav-item"><a href="chat.php" class="nav-link text-white"><i class="bi bi-chat-left-text me-2"></i> Student Chat</a></li>
                    </ul>
                </div>
                <a href="logout.php" class="btn btn-danger w-100 mt-5"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
            <main class="col-md-10 bg-light p-4">
                <h2>Overview Analytics Dashboard</h2>
                <div class="row g-3 my-3">
                    <div class="col-md-3"><div class="card bg-primary text-white p-3"><h6>Total Quizzes</h6><h3><?= $totalQuizzes; ?></h3></div></div>
                    <div class="col-md-3"><div class="card bg-success text-white p-3"><h6>Total Students</h6><h3><?= $totalStudents; ?></h3></div></div>
                    <div class="col-md-3"><div class="card bg-purple text-white p-3" style="background:#6f42c1;"><h6>Total Attempts</h6><h3><?= $totalAttempts; ?></h3></div></div>
                    <div class="col-md-3"><div class="card bg-warning text-dark p-3"><h6>Average Score</h6><h3><?= round($avgScore, 1); ?>%</h3></div></div>
                </div>
                <div class="card border-0 shadow-sm p-4 mt-4">
                    <h5>Recent Metrics Assessment Output Logs</h5>
                    <table class="table table-striped mt-3">
                        <thead><tr><th>Student</th><th>Quiz Profile</th><th>Result</th><th>Timestamp</th></tr></thead>
                        <tbody>
                            <?php foreach($attempts as $at): ?>
                                <tr><td><?= htmlspecialchars($at['student_name']); ?></td><td><?= htmlspecialchars($at['quiz_title']); ?></td><td><strong><?= $at['score']; ?>%</strong></td><td><?= $at['attempt_date']; ?></td></tr>
                            <?php endforeach; if(empty($attempts)) echo "<tr><td colspan='4' class='text-center text-muted'>No entries log found.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>