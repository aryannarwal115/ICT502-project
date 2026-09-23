<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
$admin = requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    csrf_require();
    $uid = (int)$_POST['user_id'];
    $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $target = $stmt->fetch();
    if ($target && $target['role'] !== 'admin') {
        $newStatus = $target['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $uid]);
        flash('success', 'User status updated.');
    } else {
        flash('error', 'Cannot modify this account.');
    }
    redirect('/admin/users.php');
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'user' AND (name LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
}
$users = $stmt->fetchAll();

$viewId = (int)($_GET['view'] ?? 0);
$viewUser = null;
$viewTrips = [];
if ($viewId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
    $stmt->execute([$viewId]);
    $viewUser = $stmt->fetch() ?: null;
    if ($viewUser) {
        $stmt = $pdo->prepare('SELECT id, name, status, start_date, end_date FROM trips WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$viewId]);
        $viewTrips = $stmt->fetchAll();
    }
}

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<h1 class="mt-0">Users</h1>

<div class="card">
  <form method="get" action="<?= BASE_URL ?>/admin/users.php" class="flex-between">
    <input type="text" name="q" placeholder="Search by name or email" value="<?= e($search) ?>" style="max-width:300px;">
    <button type="submit" class="btn btn-secondary">Search</button>
  </form>
</div>

<?php if ($viewUser): ?>
  <div class="card">
    <h2 class="mt-0">User Details: <?= e($viewUser['name']) ?></h2>
    <p>Email: <?= e($viewUser['email']) ?></p>
    <p>Status: <span class="badge <?= $viewUser['status'] === 'active' ? 'badge-completed' : 'badge-cancelled' ?>"><?= e(ucfirst($viewUser['status'])) ?></span></p>
    <p>Joined: <?= e(formatDate($viewUser['created_at'])) ?></p>
    <h3>Trips (<?= count($viewTrips) ?>)</h3>
    <?php if (empty($viewTrips)): ?>
      <p class="text-muted">No trips yet.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($viewTrips as $t): ?>
          <li><?= e($t['name']) ?> (<?= e(formatDate($t['start_date'])) ?> &ndash; <?= e(formatDate($t['end_date'])) ?>) &ndash; <a href="<?= BASE_URL ?>/admin/trips.php?view=<?= (int)$t['id'] ?>">View</a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/admin/users.php">&larr; Back to list</a>
  </div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge <?= $u['status'] === 'active' ? 'badge-completed' : 'badge-cancelled' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
          <td><?= e(formatDate($u['created_at'])) ?></td>
          <td>
            <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/admin/users.php?view=<?= (int)$u['id'] ?>">View</a>
            <form style="display:inline" method="post" action="<?= BASE_URL ?>/admin/users.php" data-confirm="<?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this user?">
              <?= csrf_field() ?>
              <input type="hidden" name="toggle_status" value="1">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-secondary btn-sm"><?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($users)): ?>
        <tr><td colspan="5" class="text-muted">No users found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
