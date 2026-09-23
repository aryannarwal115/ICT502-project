<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$errors = [];
$name = $user['name'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $formType = $_POST['form_type'] ?? '';
    $pdo = getDB();

    if ($formType === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) $errors['name'] = 'Please enter a valid name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'This email is already in use.';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                $stmt->execute([$name, $email, $user['id']]);
                flash('success', 'Profile updated.');
                redirect('/profile.php');
            }
        }
    } elseif ($formType === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $errors['current_password'] = 'Current password is incorrect.';
        } elseif (mb_strlen($new) < 8) {
            $errors['new_password'] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$newHash, $user['id']]);
            flash('success', 'Password changed successfully.');
            redirect('/profile.php');
        }
    }
}

$pageTitle = 'Profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1 class="mt-0">Profile</h1>
  <form method="post" action="<?= BASE_URL ?>/profile.php" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="form_type" value="profile">
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
    <button type="submit" class="btn btn-primary">Save Profile</button>
  </form>
</div>

<div class="card">
  <h2 class="mt-0">Change Password</h2>
  <form method="post" action="<?= BASE_URL ?>/profile.php" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="form_type" value="password">
    <div class="form-group">
      <label for="current_password">Current Password</label>
      <input type="password" id="current_password" name="current_password" required>
      <?php if (!empty($errors['current_password'])): ?><div class="field-error"><?= e($errors['current_password']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
      <label for="new_password">New Password</label>
      <input type="password" id="new_password" name="new_password" required minlength="8">
      <?php if (!empty($errors['new_password'])): ?><div class="field-error"><?= e($errors['new_password']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
      <label for="confirm_password">Confirm New Password</label>
      <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
      <?php if (!empty($errors['confirm_password'])): ?><div class="field-error"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary">Change Password</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
