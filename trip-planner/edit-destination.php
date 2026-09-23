<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$destId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = getDB();
$stmt = $pdo->prepare('
  SELECT d.*, t.user_id, t.start_date as trip_start, t.end_date as trip_end, t.name as trip_name
  FROM destinations d JOIN trips t ON t.id = d.trip_id
  WHERE d.id = ?
');
$stmt->execute([$destId]);
$dest = $stmt->fetch();

if (!$dest || (int)$dest['user_id'] !== (int)$user['id']) {
    flash('error', 'Destination not found.');
    redirect('/trips.php');
}
$tripId = (int)$dest['trip_id'];

$errors = [];
$name = $dest['name'];
$country = $dest['country'];
$location = $dest['location'];
$notes = $dest['notes'];
$arrival_date = $dest['arrival_date'];
$departure_date = $dest['departure_date'];

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
        } elseif (!dateWithinRange($arrival_date, $departure_date, $dest['trip_start'], $dest['trip_end'])) {
            $errors['departure_date'] = 'Destination dates must fall within the trip dates.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE destinations SET name=?, country=?, location=?, notes=?, arrival_date=?, departure_date=? WHERE id=?');
        $stmt->execute([$name, $country, $location, $notes, $arrival_date, $departure_date, $destId]);
        flash('success', 'Destination updated.');
        redirect('/trip-details.php?id=' . $tripId);
    }
}

$pageTitle = 'Edit Destination';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1 class="mt-0">Edit Destination</h1>
  <p class="text-muted">Trip: <?= e($dest['trip_name']) ?> (<?= e(formatDate($dest['trip_start'])) ?> &ndash; <?= e(formatDate($dest['trip_end'])) ?>)</p>
  <form method="post" action="<?= BASE_URL ?>/edit-destination.php?id=<?= $destId ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $destId ?>">
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
        <input type="date" id="arrival_date" name="arrival_date" required min="<?= e($dest['trip_start']) ?>" max="<?= e($dest['trip_end']) ?>" value="<?= e($arrival_date) ?>">
        <?php if (!empty($errors['arrival_date'])): ?><div class="field-error"><?= e($errors['arrival_date']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="departure_date">Departure Date *</label>
        <input type="date" id="departure_date" name="departure_date" required min="<?= e($dest['trip_start']) ?>" max="<?= e($dest['trip_end']) ?>" value="<?= e($departure_date) ?>">
        <?php if (!empty($errors['departure_date'])): ?><div class="field-error"><?= e($errors['departure_date']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="notes">Notes</label>
      <textarea id="notes" name="notes"><?= e($notes) ?></textarea>
    </div>
    <div class="btn-row">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <a class="btn btn-secondary" href="<?= BASE_URL ?>/trip-details.php?id=<?= $tripId ?>">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
