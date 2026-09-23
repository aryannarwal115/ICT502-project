<?php
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$trip = null;

if ($token && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $pdo = getDB();
    $stmt = $pdo->prepare('
      SELECT t.* FROM trips t
      JOIN trip_shares ts ON ts.trip_id = t.id
      WHERE ts.token = ? AND ts.is_active = 1
    ');
    $stmt->execute([$token]);
    $trip = $stmt->fetch() ?: null;
}

$pageTitle = 'Shared Itinerary';
require_once __DIR__ . '/includes/header.php';

if (!$trip): ?>
  <div class="card">
    <h1 class="mt-0">Itinerary Not Found</h1>
    <p>This share link is invalid, has expired, or sharing has been disabled by the trip owner.</p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/index.php">Back to Home</a>
  </div>
<?php else:
  $tripId = (int)$trip['id'];
  $pdo = getDB();

  $stmt = $pdo->prepare('SELECT * FROM destinations WHERE trip_id = ? ORDER BY sort_order ASC');
  $stmt->execute([$tripId]);
  $destinations = $stmt->fetchAll();

  $stmt = $pdo->prepare('
    SELECT a.*, d.name as destination_name
    FROM activities a LEFT JOIN destinations d ON d.id = a.destination_id
    WHERE a.trip_id = ? ORDER BY a.activity_date ASC, a.start_time ASC
  ');
  $stmt->execute([$tripId]);
  $activities = $stmt->fetchAll();

  $activitiesByDate = [];
  foreach ($activities as $a) {
      $activitiesByDate[$a['activity_date']][] = $a;
  }

  $totals = getTripTotals($tripId);
  echo '<div class="btn-row no-print" style="margin-bottom:16px;"><button type="button" class="btn btn-primary" onclick="window.print()">' . icon('link', 'icon-inline') . ' Print / Save as PDF</button></div>';
  require __DIR__ . '/includes/itinerary-render.php';
endif;
require_once __DIR__ . '/includes/footer.php';
