 <?php
require 'db.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: student_dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email address or password combination.';
        }
    } else {
        $error = 'Please fill out all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Quiz System - Security Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow border-0 rounded-3">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill text-primary display-5"></i>
                            <h4 class="fw-bold text-dark mt-2">QUIZ PORTAL</h4>
                            <small class="text-muted">Enter credentials to authenticate access</small>
                        </div>
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger py-2 fs-7"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email Identity</label>
                                <input type="email" name="email" class="form-control" required placeholder="admin@faculty.com">
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="••••••••">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Verify and Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>