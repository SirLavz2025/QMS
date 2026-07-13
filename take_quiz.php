 <?php
require 'db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') { header('Location: login.php'); exit; }

$quiz_id = intval($_GET['id'] ?? 0);
$quiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?"); $quiz->execute([$quiz_id]); $qInfo = $quiz->fetch();
if(!$qInfo) die("Target assignment registry not found.");

$questions = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?"); $questions->execute([$quiz_id]); $qList = $questions->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Exam Terminal Engine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container col-md-7">
        <div class="card bg-primary text-white border-0 shadow-sm p-4 mb-4"><h3><?= htmlspecialchars($qInfo['title']); ?></h3><p class="mb-0 text-white-50">Answer all required choice parameters before committing submission variables.</p></div>
        <form action="submit_quiz.php" method="POST">
            <input type="hidden" name="quiz_id" value="<?= $quiz_id; ?>">
            <?php $i=1; foreach($qList as $qs): 
                $choices = $pdo->prepare("SELECT * FROM choices WHERE question_id = ?"); $choices->execute([$qs['id']]); $cList = $choices->fetchAll();
            ?>
                <div class="card p-4 border-0 shadow-sm mb-3">
                    <h6><strong>Q<?= $i++; ?>:</strong> <?= htmlspecialchars($qs['question_text']); ?></h6>
                    <div class="mt-3">
                        <?php foreach($cList as $ch): ?>
                            <div class="form-check border p-2 mb-2 rounded"><input class="form-check-input ms-1 me-2" type="radio" name="answers[<?= $qs['id']; ?>]" value="<?= $ch['id']; ?>" required id="c_<?= $ch['id']; ?>"><label class="form-check-label w-100" for="c_<?= $ch['id']; ?>"><?= htmlspecialchars($ch['choice_text']); ?></label></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success w-100 py-3 font-semibold shadow-sm">Commit Processing Calculations</button>
        </form>
    </div>
</body>
</html>