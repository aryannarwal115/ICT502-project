<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$tripId = (int)($_GET['trip_id'] ?? $_POST['trip_id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) { flash('error', 'Trip not found.'); redirect('/trips.php'); }

$errors = [];
$name = $country = $location = $notes = '';
$arrival_date = $trip['start_date'];
$departure_date = $trip['end_date'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $arrival_date = $_POST['arrival_date'] ?? '';
    $departure_date = $_POST['departure_date'] ?? '';

    if ($name === '' || mb_strlen($name) > 150) $errors['name'] = 'Destination name is required.';
    if ($country === '' || mb_strlen($country) > 100) $errors['country'] = 'Country is required.';
    if (!$arrival_date || !strtotime($arrival_date)) $errors['arrival_date'] = 'A valid arrival date is required.';
    if (!$departure_date || !strtotime($departure_date)) $errors['departure_date'] = 'A valid departure date is required.';
    if (empty($errors['arrival_date']) && empty($errors['departure_date'])) {
        if (!validateDateRange($arrival_date, $departure_date)) {
            $errors['departure_date'] = 'Departure date cannot be before arrival date.';
        } elseif (!dateWithinRange($arrival_date, $departure_date, $trip['start_date'], $trip['end_date'])) {
            $errors['departure_date'] = 'Destination dates must fall within the trip dates (' . formatDate($trip['start_date']) . ' - ' . formatDate($trip['end_date']) . ').';
        }
    }

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM destinations WHERE trip_id = ?');
        $stmt->execute([$tripId]);
        $nextOrder = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('INSERT INTO destinations (trip_id, name, country, location, notes, arrival_date, departure_date, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$tripId, $name, $country, $location, $notes, $arrival_date, $departure_date, $nextOrder]);
        flash('success', 'Destination added.');
        redirect('/trip-details.php?id=' . $tripId);
    }
}

$pageTitle = 'Add Destination';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1 class="mt-0">Add Destination to "<?= e($trip['name']) ?>"</h1>
  <p class="text-muted">Trip dates: <?= e(formatDate($trip['start_date'])) ?> &ndash; <?= e(formatDate($trip['end_date'])) ?></p>
  <form method="post" action="<?= BASE_URL ?>/add-destination.php?trip_id=<?= $tripId ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
    <div class="form-row">
      <div class="form-group">
        <label for="name">Destination Name *</label>
        <input type="text" id="name" name="name" required maxlength="150" value="<?= e($name) ?>">
        <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="country">Country *</label>
        <input type="text" id="country" name="country" required maxlength="100" value="<?= e($country) ?>">
        <?php if (!empty($errors['country'])): ?><div class="field-error"><?= e($errors['country']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="location">Location / Address</label>
      <input type="text" id="location" name="location" maxlength="255" value="<?= e($location) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="arrival_date">Arrival Date *</label>
        <input type="date" id="arrival_date" name="arrival_date" required min="<?= e($trip['start_date']) ?>" max="<?= e($trip['end_date']) ?>" value="<?= e($arrival_date) ?>">
        <?php if (!empty($errors['arrival_date'])): ?><div class="field-error"><?= e($errors['arrival_date']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="departure_date">Departure Date *</label>
        <input type="date" id="departure_date" name="departure_date" required min="<?= e($trip['start_date']) ?>" max="<?= e($trip['end_date']) ?>" value="<?= e($departure_date) ?>">
        <?php if (!empty($errors['departure_date'])): ?><div class="field-error"><?= e($errors['departure_date']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="notes">Notes</label>
      <textarea id="notes" name="notes"><?= e($notes) ?></textarea>
    </div>
    <div class="btn-row">
      <button type="submit" class="btn btn-primary">Add Destination</button>
      <a class="btn btn-secondary" href="<?= BASE_URL ?>/trip-details.php?id=<?= $tripId ?>">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
