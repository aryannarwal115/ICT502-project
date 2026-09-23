<?php
require_once __DIR__ . '/includes/auth.php';
$user = requireLogin();

$tripId = (int)($_GET['id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) { flash('error', 'Trip not found.'); redirect('/trips.php'); }

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

$pageTitle = 'Itinerary';
require_once __DIR__ . '/includes/header.php';
?>
<div class="btn-row no-print" style="margin-bottom:16px;">
  <a class="btn btn-secondary" href="<?= BASE_URL ?>/trip-details.php?id=<?= $tripId ?>">&larr; Back to Trip</a>
  <button type="button" class="btn btn-primary" onclick="window.print()"><?= icon('link', 'icon-inline') ?> Print / Save as PDF</button>
</div>
<?php require __DIR__ . '/includes/itinerary-render.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
