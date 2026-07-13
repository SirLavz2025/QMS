 <?php
require 'db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
    }
    header("Location: manage_categories.php"); exit;
}
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Categories Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="d-flex justify-content-between mb-4"><h3>Manage Categories</h3><a href="admin_dashboard.php" class="btn btn-secondary">Back</a></div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm">
                    <form method="POST">
                        <div class="mb-3"><label>Category Name</label><input type="text" name="category_name" class="form-control" required></div>
                        <button type="submit" name="add_category" class="btn btn-success w-100">Save</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card p-4 border-0 shadow-sm">
                    <table class="table">
                        <thead><tr><th>ID</th><th>Category Name</th></tr></thead>
                        <tbody>
                            <?php foreach($categories as $cat) echo "<tr><td>{$cat['id']}</td><td>" . htmlspecialchars($cat['name']) . "</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>