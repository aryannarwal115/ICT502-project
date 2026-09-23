<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();
$pdo = getDB();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM trips WHERE user_id = ?');
$stmt->execute([$user['id']]);
$totalTrips = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE user_id = ? AND status IN ('planned','ongoing') AND end_date >= CURDATE()");
$stmt->execute([$user['id']]);
$upcoming = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE user_id = ? AND (status = 'completed' OR end_date < CURDATE())");
$stmt->execute([$user['id']]);
$completed = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT COALESCE(SUM(e.amount),0) as total
  FROM expenses e JOIN trips t ON t.id = e.trip_id
  WHERE t.user_id = ?
");
$stmt->execute([$user['id']]);
$totalSpend = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$recentTrips = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex-between">
  <h1 class="mt-0">Welcome, <?= e($user['name']) ?></h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/create-trip.php">+ Create Trip</a>
</div>

<div class="card-grid">
  <div class="card stat-card">
    <div class="stat-value"><?= $totalTrips ?></div>
    <div class="stat-label">Total Trips</div>
  </div>
  <div class="card stat-card">
    <div class="stat-value"><?= $upcoming ?></div>
    <div class="stat-label">Upcoming Trips</div>
  </div>
  <div class="card stat-card">
    <div class="stat-value"><?= $completed ?></div>
    <div class="stat-label">Completed Trips</div>
  </div>
  <div class="card stat-card">
    <div class="stat-value"><?= e(formatMoney($totalSpend)) ?></div>
    <div class="stat-label">Estimated Total Spending</div>
  </div>
</div>

<h2>Recent Trips</h2>
<?php if (empty($recentTrips)): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= icon('suitcase') ?></div>
    <p>You haven't created any trips yet.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/create-trip.php">Create your first trip</a>
  </div>
<?php else: ?>
  <div class="card-grid">
    <?php foreach ($recentTrips as $t): ?>
      <div class="card trip-card">
        <?php if ($t['cover_image']): ?>
          <img class="trip-cover" src="<?= BASE_URL ?>/uploads/<?= e($t['cover_image']) ?>" alt="Cover image for <?= e($t['name']) ?>">
        <?php else: ?>
          <div class="trip-cover" role="img" aria-label="No cover image"></div>
        <?php endif; ?>
        <span class="badge badge-<?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span>
        <h3><?= e($t['name']) ?></h3>
        <p class="text-muted"><?= e(formatDate($t['start_date'])) ?> &ndash; <?= e(formatDate($t['end_date'])) ?></p>
        <div class="btn-row">
          <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/trip-details.php?id=<?= (int)$t['id'] ?>">View</a>
          <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/edit-trip.php?id=<?= (int)$t['id'] ?>">Edit</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <p><a href="<?= BASE_URL ?>/trips.php">View all trips &rarr;</a></p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
