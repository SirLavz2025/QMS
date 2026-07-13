 <?php
require 'db.php';
session_start();

// Protection Gate: Restrict access strictly to Faculty Admin roles
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['quiz_id'])) {
    die("Error: Quiz execution parameter scope missing.");
}
$quiz_id = intval($_GET['quiz_id']);

// Fetch Quiz profile metadata
$quiz_stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$quiz_stmt->execute([$quiz_id]);
$quiz = $quiz_stmt->fetch();

if (!$quiz) {
    die("Error: Linked quiz configuration context not found.");
}

$alert_message = '';
$alert_type = '';

// ==========================================
// ACTION A: Process Manual Question Form Entry
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual'])) {
    $question_text = trim($_POST['question_text']);
    $choices_input = $_POST['choices'] ?? [];
    $correct_index = isset($_POST['correct_choice']) ? intval($_POST['correct_choice']) : null;

    if (!empty($question_text) && count($choices_input) === 4 && $correct_index !== null) {
        try {
            $pdo->beginTransaction();
            $q_stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)");
            $q_stmt->execute([$quiz_id, $question_text]);
            $question_id = $pdo->lastInsertId();

            foreach ($choices_input as $index => $text) {
                $is_correct = ($index === $correct_index) ? 1 : 0;
                $c_stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                $c_stmt->execute([$question_id, trim($text), $is_correct]);
            }
            $pdo->commit();
            $alert_message = "Successfully added 1 new question item.";
            $alert_type = "success";
        } catch (\Exception $e) {
            $pdo->rollBack();
            $alert_message = "Error saving manual question: " . $e->getMessage();
            $alert_type = "danger";
        }
    }
}

// ==========================================
// ACTION B: Process Bulk Text Import Format Parser
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_bulk'])) {
    $bulk_text = trim($_POST['bulk_data']);
    
    if (!empty($bulk_text)) {
        // Split text block input by looking for double carriage return line breaks separating items
        $raw_blocks = explode("\n\n", str_replace("\r", "", $bulk_text));
        $imported_count = 0;
        
        try {
            $pdo->beginTransaction();
            
            foreach ($raw_blocks as $block) {
                $lines = array_values(array_filter(array_map('trim', explode("\n", $block))));
                
                // Expecting a block layout template comprising exactly 1 Question + 4 Options + 1 Answer indicator index key string
                if (count($lines) >= 6) {
                    $question_prompt = preg_replace('/^[Q0-9.\s]+/i', '', $lines[0]); // Strip prefixes like "Q1."
                    
                    $q_stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)");
                    $q_stmt->execute([$quiz_id, $question_prompt]);
                    $new_q_id = $pdo->lastInsertId();
                    
                    // Harvest answers lines index values
                    $optA = preg_replace('/^[A-D.\s]+/i', '', $lines[1]);
                    $optB = preg_replace('/^[A-D.\s]+/i', '', $lines[2]);
                    $optC = preg_replace('/^[A-D.\s]+/i', '', $lines[3]);
                    $optD = preg_replace('/^[A-D.\s]+/i', '', $lines[4]);
                    $parsed_options = [$optA, $optB, $optC, $optD];
                    
                    // Identify correct target letter key index definition parameter boundary
                    $ans_line = strtoupper($lines[5]);
                    $correct_letter = str_replace('ANSWER:', '', $ans_line);
                    $correct_letter = trim(str_replace('.', '', $correct_letter));
                    
                    $letter_map = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
                    $target_correct_index = $letter_map[$correct_letter] ?? 0;
                    
                    foreach ($parsed_options as $idx => $opt_text) {
                        $is_ans = ($idx === $target_correct_index) ? 1 : 0;
                        $c_stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)");
                        $c_stmt->execute([$new_q_id, $opt_text, $is_ans]);
                    }
                    $imported_count++;
                }
            }
            $pdo->commit();
            $alert_message = "Bulk parsing complete! Successfully injected **" . $imported_count . "** questions into this quiz.";
            $alert_type = "success";
        } catch (\Exception $e) {
            $pdo->rollBack();
            $alert_message = "Parser processing error: " . $e->getMessage();
            $alert_type = "danger";
        }
    }
}

// ==========================================
// ACTION C: Remove Selected Question Element
// ==========================================
if (isset($_GET['delete_question_id'])) {
    $delete_id = intval($_GET['delete_question_id']);
    $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?")->execute([$delete_id, $quiz_id]);
    header("Location: manage_questions.php?quiz_id=" . $quiz_id);
    exit;
}

// Pull complete structured question logs mapped to current quiz
$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$q_stmt->execute([$quiz_id]);
$questions_list = $q_stmt->fetchAll();
$total_items = count($questions_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?= htmlspecialchars($quiz['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container">
            <span class="navbar-brand fw-bold text-uppercase"><i class="bi bi-patch-question me-2 text-primary"></i>Question Repository Pipeline</span>
            <a href="manage_quizzes.php" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left me-1"></i> Back to Quizzes</a>
        </div>
    </nav>

    <div class="container my-5">
        
        <div class="card border-0 shadow-sm p-4 mb-4 bg-primary text-white d-flex flex-row justify-content-between align-items-center">
            <div>
                <small class="text-uppercase tracking-wider opacity-75">Configuring Structural Blueprint For:</small>
                <h2 class="fw-bold mb-0"><?= htmlspecialchars($quiz['title']); ?></h2>
            </div>
            <div class="text-end bg-white bg-opacity-10 px-4 py-2 rounded-3">
                <span class="small d-block text-uppercase opacity-75">Current Size</span>
                <h3 class="fw-black mb-0"><?= $total_items; ?> Items</h3>
            </div>
        </div>

        <?php if (!empty($alert_message)): ?>
            <div class="alert alert-<?= $alert_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                <?= $alert_message; ?>
                <button type="submit" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
                    
                    <div class="bg-light border-bottom p-2">
                        <ul class="nav nav-pills nav-justified" id="inputMethodTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-semibold small" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manualPanel" type="button" role="tab"><i class="bi bi-pencil-square me-1"></i> Manual Add</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-semibold small" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulkPanel" type="button" role="tab"><i class="bi bi-cloud-arrow-up-fill me-1"></i> Bulk Import</button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4 tab-content">
                        
                        <div class="tab-pane fade show active" id="manualPanel" role="tabpanel">
                            <h6 class="fw-bold text-dark mb-3">Add Question Manually</h6>
                            <form action="manage_questions.php?quiz_id=<?= $quiz_id; ?>" method="POST">
                                <input type="hidden" name="add_manual" value="1">
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Question prompt definition string</label>
                                    <textarea name="question_text" class="form-control" rows="2" placeholder="Write question details..." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Alternatives Setup (Select one target checkbox as correct value)</label>
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text bg-light border-light-subtle">
                                                <input class="form-check-input mt-0" type="radio" name="correct_choice" value="<?= $i; ?>" required>
                                            </div>
                                            <input type="text" name="choices[<?= $i; ?>]" class="form-control form-control-sm" placeholder="Choice option alternative <?= chr(65 + $i); ?>" required>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-2 small fw-semibold">Save Individual Item</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="bulkPanel" role="tabpanel">
                            <h6 class="fw-bold text-dark mb-1">Bulk Plain Text Formatting Panel</h6>
                            <p class="text-muted small mb-3">Paste questions exactly matching the validation schematic pattern rule layout below.</p>
                            
                            <div class="bg-dark text-light p-3 rounded-2 small font-monospace mb-3" style="font-size:0.75rem; line-height: 1.35;">
                                What color is the sky?<br>
                                A. Green<br>
                                B. Blue<br>
                                C. Yellow<br>
                                D. Red<br>
                                ANSWER: B<br><br>
                                Which tag defines a link in HTML?<br>
                                A. &lt;img&gt;<br>
                                B. &lt;p&gt;<br>
                                C. &lt;a&gt;<br>
                                D. &lt;div&gt;<br>
                                ANSWER: C
                            </div>

                            <form action="manage_questions.php?quiz_id=<?= $quiz_id; ?>" method="POST">
                                <input type="hidden" name="import_bulk" value="1">
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Raw Text Batch Input Stream Area</label>
                                    <textarea name="bulk_data" class="form-control font-monospace small" rows="8" placeholder="Paste structural question objects here... Separate multi-item strings with an empty blank line break string space block configuration." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100 py-2 small fw-semibold"><i class="bi bi-file-earmark-medical me-1"></i> Trigger Batch Injection Stream</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card p-4 border-0 shadow-sm rounded-3 bg-white">
                    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-list-ol text-primary me-2"></i>Current Question Catalog Blueprint</h5>
                    
                    <?php if ($total_items > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php $counter = 1; foreach ($questions_list as $q): 
                                $c_stmt = $pdo->prepare("SELECT * FROM choices WHERE question_id = ?");
                                $c_stmt->execute([$q['id']]);
                                $choices = $c_stmt->fetchAll();
                            ?>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold text-dark mb-0 flex-grow-1">
                                            <span class="text-primary me-1">Item <?= $counter++; ?>.</span> 
                                            <?= htmlspecialchars($q['question_text']); ?>
                                        </h6>
                                        <a href="manage_questions.php?quiz_id=<?= $quiz_id; ?>&delete_question_id=<?= $q['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger ms-2 py-1 px-2" 
                                           onclick="return confirm('Drop this question from this quiz configuration index parameters completely?')">
                                            <i class="bi bi-trash small"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="row g-2 mt-2">
                                        <?php foreach ($choices as $ch): ?>
                                            <div class="col-md-6">
                                                <div class="p-2 rounded bg-white small border <?= $ch['is_correct'] ? 'border-success bg-success bg-opacity-10 text-success fw-medium' : 'text-secondary'; ?>">
                                                    <i class="bi <?= $ch['is_correct'] ? 'bi-check-circle-fill' : 'bi-circle'; ?> me-2"></i>
                                                    <?= htmlspecialchars($ch['choice_text']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4 d-block mb-3 opacity-40"></i>
                            <p class="m-0">No active item blocks mapped yet. Use either the Manual Add console interface input setup or the Batch Bulk Import mechanism to generate structural assets fields layout tracking entries logs records.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>