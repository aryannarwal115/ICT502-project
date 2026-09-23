<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
$admin = requireAdmin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$admin['id']]);
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
        $stmt->execute([$newHash, $admin['id']]);
        flash('success', 'Password changed successfully.');
        redirect('/admin/settings.php');
    }
}

$pageTitle = 'Admin Settings';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<h1 class="mt-0">Admin Settings</h1>
<div class="card">
  <h2 class="mt-0">Change Admin Password</h2>
  <form method="post" action="<?= BASE_URL ?>/admin/settings.php" novalidate data-validate>
    <?= csrf_field() ?>
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
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
