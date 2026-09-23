<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM trips WHERE user_id = ? ORDER BY start_date DESC');
$stmt->execute([$user['id']]);
$trips = $stmt->fetchAll();

$pageTitle = 'My Trips';
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex-between">
  <h1 class="mt-0">My Trips</h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/create-trip.php">+ Create Trip</a>
</div>

<?php if (empty($trips)): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= icon('suitcase') ?></div>
    <p>No trips yet.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/create-trip.php">Create your first trip</a>
  </div>
<?php else: ?>
  <div class="card-grid">
    <?php foreach ($trips as $t): ?>
      <div class="card trip-card">
        <?php if ($t['cover_image']): ?>
          <img class="trip-cover" src="<?= BASE_URL ?>/uploads/<?= e($t['cover_image']) ?>" alt="Cover image for <?= e($t['name']) ?>">
        <?php else: ?>
          <img class="trip-cover" src="https://source.unsplash.com/600x400/?travel,<?= urlencode(explode(' ', $t['name'])[0] ?? 'destination') ?>" alt="Cover image for <?= e($t['name']) ?>" loading="lazy">
        <?php endif; ?>
        <div class="trip-card-body">
          <span class="badge badge-<?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span>
          <h3><?= e($t['name']) ?></h3>
          <p class="text-muted"><?= e(formatDate($t['start_date'])) ?> &ndash; <?= e(formatDate($t['end_date'])) ?></p>
          <div class="btn-row">
            <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/trip-details.php?id=<?= (int)$t['id'] ?>">View</a>
            <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/edit-trip.php?id=<?= (int)$t['id'] ?>">Edit</a>
            <form method="post" action="<?= BASE_URL ?>/delete-trip.php" data-confirm="Delete this trip? This cannot be undone.">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
