<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$tripId = (int)($_GET['id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) {
    flash('error', 'Trip not found.');
    redirect('/trips.php');
}

$pdo = getDB();

$stmt = $pdo->prepare('SELECT * FROM destinations WHERE trip_id = ? ORDER BY sort_order ASC, arrival_date ASC');
$stmt->execute([$tripId]);
$destinations = $stmt->fetchAll();

$stmt = $pdo->prepare('
  SELECT a.*, d.name as destination_name,
    (SELECT COUNT(*) FROM activity_comments c WHERE c.activity_id = a.id) as comment_count
  FROM activities a LEFT JOIN destinations d ON d.id = a.destination_id
  WHERE a.trip_id = ? ORDER BY a.activity_date ASC, a.start_time ASC
');
$stmt->execute([$tripId]);
$activities = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM expenses WHERE trip_id = ? ORDER BY expense_date DESC, id DESC');
$stmt->execute([$tripId]);
$expenses = $stmt->fetchAll();

$totals = getTripTotals($tripId);

$stmt = $pdo->prepare('SELECT token, is_active FROM trip_shares WHERE trip_id = ?');
$stmt->execute([$tripId]);
$share = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM trip_documents WHERE trip_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$tripId]);
$documents = $stmt->fetchAll();

$packingItems = getChecklist($tripId, 'packing');
$prepItems = getChecklist($tripId, 'prep');
$packingDone = count(array_filter($packingItems, fn($i) => (int)$i['is_done'] === 1));
$prepDone = count(array_filter($prepItems, fn($i) => (int)$i['is_done'] === 1));

$pageTitle = $trip['name'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex-between">
  <div>
    <h1 class="mt-0"><?= e($trip['name']) ?> <span class="badge badge-<?= e($trip['status']) ?>"><?= e(ucfirst($trip['status'])) ?></span></h1>
    <p class="text-muted"><?= e(formatDate($trip['start_date'])) ?> &ndash; <?= e(formatDate($trip['end_date'])) ?></p>
  </div>
  <div class="btn-row">
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/edit-trip.php?id=<?= $tripId ?>">Edit Trip</a>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/itinerary.php?id=<?= $tripId ?>">View Itinerary</a>
    <form method="post" action="<?= BASE_URL ?>/duplicate-trip.php" style="display:inline">
      <?= csrf_field() ?>
      <input type="hidden" name="trip_id" value="<?= $tripId ?>">
      <button type="submit" class="btn btn-secondary">Duplicate Trip</button>
    </form>
  </div>
</div>

<?php if ($trip['description']): ?><div class="card"><?= nl2br(e($trip['description'])) ?></div><?php endif; ?>

<!-- Checklists -->
<div class="card">
  <h2 class="mt-0"><?= icon('check', 'icon-inline') ?> Checklists</h2>
  <div class="checklist-summary-grid">
    <a class="checklist-summary-card" href="<?= BASE_URL ?>/checklist.php?trip_id=<?= $tripId ?>&type=packing">
      <?= icon('suitcase') ?>
      <div>
        <strong>Packing List</strong>
        <span class="text-muted"><?= $packingItems ? "$packingDone/" . count($packingItems) . ' packed' : 'Nothing added yet' ?></span>
      </div>
    </a>
    <a class="checklist-summary-card" href="<?= BASE_URL ?>/checklist.php?trip_id=<?= $tripId ?>&type=prep">
      <?= icon('check') ?>
      <div>
        <strong>Trip Prep</strong>
        <span class="text-muted"><?= $prepItems ? "$prepDone/" . count($prepItems) . ' done' : 'Nothing added yet' ?></span>
      </div>
    </a>
  </div>
</div>

<!-- Destinations -->
<div class="card">
  <div class="flex-between">
    <h2 class="mt-0">Destinations</h2>
    <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/add-destination.php?trip_id=<?= $tripId ?>">+ Add Destination</a>
  </div>
  <?php if (empty($destinations)): ?>
    <div class="empty-state"><p>No destinations added yet.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Country</th><th>Arrival</th><th>Departure</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($destinations as $d): ?>
          <tr>
            <td><?= e($d['name']) ?></td>
            <td><?= e($d['country']) ?></td>
            <td><?= e(formatDate($d['arrival_date'])) ?></td>
            <td><?= e(formatDate($d['departure_date'])) ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/edit-destination.php?id=<?= (int)$d['id'] ?>">Edit</a>
              <form style="display:inline" method="post" action="<?= BASE_URL ?>/delete-destination.php" data-confirm="Delete this destination?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <input type="hidden" name="trip_id" value="<?= $tripId ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Activities -->
<div class="card">
  <div class="flex-between">
    <h2 class="mt-0">Activities</h2>
    <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/add-activity.php?trip_id=<?= $tripId ?>">+ Add Activity</a>
  </div>
  <?php if (empty($activities)): ?>
    <div class="empty-state"><p>No activities added yet.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Name</th><th>Destination</th><th>Time</th><th>Cost</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($activities as $a): ?>
          <tr>
            <td><?= e(formatDate($a['activity_date'])) ?></td>
            <td><?= e($a['name']) ?></td>
            <td><?= e($a['destination_name'] ?? '—') ?></td>
            <td><?= e(formatTime($a['start_time'])) ?><?= $a['end_time'] ? ' - ' . e(formatTime($a['end_time'])) : '' ?></td>
            <td><?= e(formatMoney((float)$a['estimated_cost'])) ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/edit-activity.php?id=<?= (int)$a['id'] ?>">Edit</a>
              <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/activity-comments.php?activity_id=<?= (int)$a['id'] ?>">Notes<?= $a['comment_count'] ? ' (' . (int)$a['comment_count'] . ')' : '' ?></a>
              <form style="display:inline" method="post" action="<?= BASE_URL ?>/delete-activity.php" data-confirm="Delete this activity?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <input type="hidden" name="trip_id" value="<?= $tripId ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Expenses -->
<div class="card">
  <div class="flex-between">
    <h2 class="mt-0">Expenses</h2>
    <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>&action=add">+ Add Expense</a>
  </div>
  <div class="cost-summary">
    <div class="cost-item"><strong><?= e(formatMoney($totals['flight'])) ?></strong><br>Flights</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['transport'])) ?></strong><br>Transport</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['accommodation'])) ?></strong><br>Accommodation</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['activity'])) ?></strong><br>Activities</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['food'])) ?></strong><br>Food</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['other'])) ?></strong><br>Other</div>
    <div class="cost-item total"><strong><?= e(formatMoney($totals['grand_total'])) ?></strong><br>Total Estimated Cost</div>
  </div>
  <p><a href="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>">Manage all expenses &amp; splitting &rarr;</a></p>
</div>

<!-- Documents -->
<div class="card">
  <h2 class="mt-0"><?= icon('link', 'icon-inline') ?> Documents</h2>
  <p class="text-muted mt-0">Flight tickets, hotel bookings, passport scans, insurance — keep them attached to the trip.</p>
  <form method="post" action="<?= BASE_URL ?>/upload-document.php" enctype="multipart/form-data" class="checklist-add-row">
    <?= csrf_field() ?>
    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
    <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
  </form>
  <?php if (empty($documents)): ?>
    <div class="empty-state"><p>No documents uploaded yet.</p></div>
  <?php else: ?>
    <ul class="document-list">
      <?php foreach ($documents as $doc): ?>
        <li>
          <a href="<?= BASE_URL ?>/uploads/documents/<?= e($doc['stored_name']) ?>" target="_blank" rel="noopener"><?= e($doc['original_name']) ?></a>
          <span class="text-muted"><?= e(round($doc['file_size'] / 1024)) ?> KB</span>
          <form method="post" action="<?= BASE_URL ?>/delete-document.php" data-confirm="Remove this document?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
            <input type="hidden" name="trip_id" value="<?= $tripId ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<!-- Sharing -->
<div class="card">
  <h2 class="mt-0">Share this Trip</h2>
  <?php if ($share && $share['is_active']): ?>
    <p>Your itinerary is publicly viewable at:</p>
    <p><code><?= e('/shared-trip.php?token=' . $share['token']) ?></code></p>
    <form method="post" action="<?= BASE_URL ?>/toggle-share.php">
      <?= csrf_field() ?>
      <input type="hidden" name="trip_id" value="<?= $tripId ?>">
      <input type="hidden" name="action" value="disable">
      <button type="submit" class="btn btn-secondary">Disable Sharing</button>
    </form>
  <?php else: ?>
    <p>This trip is not currently shared.</p>
    <form method="post" action="<?= BASE_URL ?>/toggle-share.php">
      <?= csrf_field() ?>
      <input type="hidden" name="trip_id" value="<?= $tripId ?>">
      <input type="hidden" name="action" value="enable">
      <button type="submit" class="btn btn-primary">Generate Share Link</button>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
