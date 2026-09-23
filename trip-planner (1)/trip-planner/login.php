<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isLoggedIn()) redirect('/dashboard.php');

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors['general'] = 'Please enter both email and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($password, $u['password_hash'])) {
            $errors['general'] = 'Invalid email or password.';
        } elseif ($u['status'] !== 'active') {
            $errors['general'] = 'This account has been deactivated. Contact support.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id'];
            flash('success', 'Welcome back, ' . $u['name'] . '!');
            redirect('/dashboard.php');
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-brand">
    <img src="<?= BASE_URL ?>/assets/images/logo.svg" width="44" height="44" alt="Trip Planner">
  </div>
  <div class="card">
    <h1 class="mt-0">Log In</h1>
    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= BASE_URL ?>/login.php" novalidate data-validate>
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
    <p class="text-muted">No account? <a href="<?= BASE_URL ?>/register.php">Register here</a></p>
    <p class="text-muted">Admin? <a href="<?= BASE_URL ?>/admin/login.php">Admin login</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
