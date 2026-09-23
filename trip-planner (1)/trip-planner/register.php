<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isLoggedIn()) redirect('/dashboard.php');

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) $errors['name'] = 'Please enter a valid name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, "user", "active")');
            $stmt->execute([$name, $email, $hash]);
            flash('success', 'Account created successfully. Please log in.');
            redirect('/login.php');
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-brand">
    <img src="<?= BASE_URL ?>/assets/images/logo.svg" width="44" height="44" alt="Trip Planner">
  </div>
  <div class="card">
    <h1 class="mt-0">Create an Account</h1>
    <form method="post" action="<?= BASE_URL ?>/register.php" novalidate data-validate>
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required maxlength="100" value="<?= e($name) ?>">
        <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="150" value="<?= e($email) ?>">
        <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrap">
          <input type="password" id="password" name="password" required minlength="8">
          <button type="button" class="password-toggle" data-target="password">Show</button>
        </div>
        <div class="help-text">At least 8 characters.</div>
        <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <div class="password-wrap">
          <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
          <button type="button" class="password-toggle" data-target="confirm_password">Show</button>
        </div>
        <?php if (!empty($errors['confirm_password'])): ?><div class="field-error"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Register</button>
    </form>
    <p class="text-muted">Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
