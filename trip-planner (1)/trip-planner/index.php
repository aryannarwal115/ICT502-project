<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$stmt = $pdo->query("
  SELECT t.id, t.name, t.description, t.start_date, t.end_date, t.cover_image, ts.token
  FROM trips t
  JOIN trip_shares ts ON ts.trip_id = t.id AND ts.is_active = 1
  ORDER BY t.created_at DESC
  LIMIT 6
");
$publicTrips = $stmt->fetchAll();
?>
<section class="hero">
  <div class="hero-inner">
    <span class="hero-eyebrow"><?= icon('compass') ?> Your trips, beautifully organized</span>
    <h1>Plan your next adventure</h1>
    <p>Trip Planner helps you organize destinations, activities, and budgets for every trip :- then share your itinerary with friends and family.</p>
    <?php if (!$user): ?>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= BASE_URL ?>/register.php">Get Started</a>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/about.php">Learn More</a>
      </div>
    <?php else: ?>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= BASE_URL ?>/dashboard.php">Go to Dashboard</a>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/create-trip.php">Plan a New Trip</a>
      </div>
    <?php endif; ?>
    <div class="hero-stats">
      <div class="stat"><b><?= icon('map') ?></b><span>Unlimited destinations</span></div>
      <div class="stat"><b><?= icon('wallet') ?></b><span>Automatic expense totals</span></div>
      <div class="stat"><b><?= icon('link') ?></b><span>One-click sharing</span></div>
    </div>
  </div>
</section>

<div class="feature-strip">
  <div class="feature">
    <div class="feature-icon"><?= icon('map') ?></div>
    <h3>Organize Destinations</h3>
    <p>Keep every stop, date and detail in one place.</p>
  </div>
  <div class="feature">
    <div class="feature-icon"><?= icon('calendar') ?></div>
    <h3>Day-by-Day Itineraries</h3>
    <p>Build a clear schedule of activities for each day.</p>
  </div>
  <div class="feature">
    <div class="feature-icon"><?= icon('wallet') ?></div>
    <h3>Track Expenses</h3>
    <p>See where your travel budget is going at a glance.</p>
  </div>
  <div class="feature">
    <div class="feature-icon"><?= icon('link') ?></div>
    <h3>Share With Anyone</h3>
    <p>Send a link so friends and family can follow along.</p>
  </div>
</div>

<div class="section-heading">
  <h2>Publicly Shared Itineraries</h2>
</div>
<?php if (empty($publicTrips)): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= icon('globe') ?></div>
    <p>No shared trips yet. Be the first to share yours!</p>
  </div>
<?php else: ?>
  <div class="card-grid">
    <?php foreach ($publicTrips as $t):
      $cover = $t['cover_image']
        ? BASE_URL . '/uploads/' . rawurlencode($t['cover_image'])
        : 'https://source.unsplash.com/600x400/?travel,' . urlencode(explode(' ', $t['name'])[0] ?? 'destination');
    ?>
      <div class="card trip-card">
        <img class="trip-cover" src="<?= e($cover) ?>" alt="Cover image for <?= e($t['name']) ?>" loading="lazy">
        <div class="trip-card-body">
          <h3><?= e($t['name']) ?></h3>
          <p class="text-muted"><?= e(formatDate($t['start_date'])) ?> &ndash; <?= e(formatDate($t['end_date'])) ?></p>
          <p><?= e(mb_strimwidth($t['description'] ?? '', 0, 100, '...')) ?></p>
          <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/shared-trip.php?token=<?= urlencode($t['token']) ?>">View Itinerary</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
