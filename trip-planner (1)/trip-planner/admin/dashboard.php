<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$admin = requireAdmin();

$pdo = getDB();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'")->fetchColumn();
$totalTrips = (int)$pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
$sharedTrips = (int)$pdo->query("SELECT COUNT(*) FROM trip_shares WHERE is_active = 1")->fetchColumn();

$stmt = $pdo->query("
  SELECT t.id, t.name, t.created_at, u.name as owner_name
  FROM trips t JOIN users u ON u.id = t.user_id
  ORDER BY t.created_at DESC LIMIT 8
");
$recentTrips = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<h1 class="mt-0">Admin Dashboard</h1>
<div class="card-grid">
  <div class="card stat-card"><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div>
  <div class="card stat-card"><div class="stat-value"><?= $activeUsers ?></div><div class="stat-label">Active Users</div></div>
  <div class="card stat-card"><div class="stat-value"><?= $totalTrips ?></div><div class="stat-label">Total Trips</div></div>
  <div class="card stat-card"><div class="stat-value"><?= $sharedTrips ?></div><div class="stat-label">Shared Trips</div></div>
</div>

<div class="card">
  <h2 class="mt-0">Recent Activity</h2>
  <?php if (empty($recentTrips)): ?>
    <p class="text-muted">No trips created yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Trip</th><th>Owner</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recentTrips as $t): ?>
          <tr>
            <td><?= e($t['name']) ?></td>
            <td><?= e($t['owner_name']) ?></td>
            <td><?= e(formatDate($t['created_at'])) ?></td>
            <td><a href="<?= BASE_URL ?>/admin/trips.php?view=<?= (int)$t['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
