<?php
require 'db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quiz_id'])) {
    $quiz_id = intval($_POST['quiz_id']);
    $user_answers = $_POST['answers'] ?? [];

    $questions = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ?"); $questions->execute([$quiz_id]); $qList = $questions->fetchAll();
    $total = count($qList); $correct = 0;

    foreach ($qList as $q) {
        $qid = $q['id'];
        if (isset($user_answers[$qid])) {
            $check = $pdo->prepare("SELECT is_correct FROM choices WHERE id = ? AND question_id = ?");
            $check->execute([intval($user_answers[$qid]), $qid]);
            if (($check->fetch()['is_correct'] ?? 0) == 1) $correct++;
        }
    }
    $final_score = ($total > 0) ? round(($correct / $total) * 100) : 0;
    $pdo->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)")->execute([$_SESSION['user_id'], $quiz_id, $final_score, $total]);
} else { header('Location: student_dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Calculation Complete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
    <div class="container text-center col-md-4 card p-5 border-0 shadow">
        <h3 class="fw-bold">Assessment Evaluated</h3>
        <div class="my-4"><span class="text-muted d-block">Result Metrics Score</span><h1 class="display-3 text-success font-black"><?= $final_score; ?>%</h1></div>
        <a href="student_dashboard.php" class="btn btn-primary w-100 py-2">Return to Student Workspace</a>
    </div>
</body>
</html>