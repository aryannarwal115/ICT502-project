<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (isAdminLoggedIn()) redirect('/admin/dashboard.php');

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $a = $stmt->fetch();

    if (!$a || !password_verify($password, $a['password_hash'])) {
        $errors['general'] = 'Invalid admin credentials.';
    } elseif ($a['status'] !== 'active') {
        $errors['general'] = 'This admin account is inactive.';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id'];
        flash('success', 'Welcome back, ' . $a['name'] . '.');
        redirect('/admin/dashboard.php');
    }
}

$pageTitle = 'Admin Login';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="auth-wrap">
  <div class="card">
    <h1 class="mt-0">Admin Login</h1>
    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>/admin/login.php" novalidate data-validate>
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= e($email) ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrap">
          <input type="password" id="password" name="password" required>
          <button type="button" class="password-toggle" data-target="password">Show</button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Log In</button>
    </form>
    <p class="text-muted"><a href="<?= BASE_URL ?>/index.php">&larr; Back to site</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
