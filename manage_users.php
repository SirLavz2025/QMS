 <?php
require 'db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $role = $_POST['role'];

    if (!empty($name) && !empty($email)) {
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")->execute([$name, $email, $password, $role]);
    }
    header("Location: manage_users.php"); exit;
}
$users = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>User Directory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="d-flex justify-content-between mb-4"><h3>User Accounts Engine</h3><a href="admin_dashboard.php" class="btn btn-secondary">Back</a></div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm">
                    <form method="POST">
                        <div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label>Email Address</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="mb-3"><label>Role Profile</label><select name="role" class="form-select"><option value="student">Student</option><option value="admin">Faculty</option></select></div>
                        <button type="submit" name="add_user" class="btn btn-primary w-100">Create Account</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card p-4 border-0 shadow-sm">
                    <table class="table">
                        <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                        <tbody>
                            <?php foreach($users as $u) echo "<tr><td>" . htmlspecialchars($u['name']) . "</td><td>" . htmlspecialchars($u['email']) . "</td><td><span class='badge bg-dark'>{$u['role']}</span></td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>