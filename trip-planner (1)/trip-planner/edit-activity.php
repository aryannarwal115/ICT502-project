<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$actId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = getDB();
$stmt = $pdo->prepare('
  SELECT a.*, t.user_id, t.start_date as trip_start, t.end_date as trip_end, t.name as trip_name
  FROM activities a JOIN trips t ON t.id = a.trip_id
  WHERE a.id = ?
');
$stmt->execute([$actId]);
$act = $stmt->fetch();

if (!$act || (int)$act['user_id'] !== (int)$user['id']) {
    flash('error', 'Activity not found.');
    redirect('/trips.php');
}
$tripId = (int)$act['trip_id'];

$stmt = $pdo->prepare('SELECT id, name FROM destinations WHERE trip_id = ? ORDER BY sort_order');
$stmt->execute([$tripId]);
$destinations = $stmt->fetchAll();

$errors = [];
$name = $act['name'];
$description = $act['description'];
$location = $act['location'];
$destination_id = $act['destination_id'] ?? '';
$activity_date = $act['activity_date'];
$start_time = $act['start_time'];
$end_time = $act['end_time'];
$estimated_cost = $act['estimated_cost'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $destination_id = $_POST['destination_id'] ?? '';
    $activity_date = $_POST['activity_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $estimated_cost = $_POST['estimated_cost'] ?? '0';

    if ($name === '' || mb_strlen($name) > 150) $errors['name'] = 'Activity name is required.';
    if (!$activity_date || !strtotime($activity_date)) $errors['activity_date'] = 'A valid date is required.';
    elseif (!dateWithinRange($activity_date, $activity_date, $act['trip_start'], $act['trip_end'])) {
        $errors['activity_date'] = 'Activity date must fall within the trip dates.';
    }
    if ($start_time && $end_time && strtotime($end_time) < strtotime($start_time)) {
        $errors['end_time'] = 'End time cannot be before start time.';
    }
    if (!is_numeric($estimated_cost) || (float)$estimated_cost < 0) {
        $errors['estimated_cost'] = 'Cost must be a non-negative number.';
    }
    $destIdValue = null;
    if ($destination_id !== '') {
        $found = array_filter($destinations, fn($d) => (int)$d['id'] === (int)$destination_id);
        if (empty($found)) {
            $errors['destination_id'] = 'Invalid destination selected.';
        } else {
            $destIdValue = (int)$destination_id;
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE activities SET destination_id=?, name=?, description=?, location=?, activity_date=?, start_time=?, end_time=?, estimated_cost=? WHERE id=?');
        $stmt->execute([$destIdValue, $name, $description, $location, $activity_date, $start_time ?: null, $end_time ?: null, (float)$estimated_cost, $actId]);
        flash('success', 'Activity updated.');
        redirect('/trip-details.php?id=' . $tripId);
    }
}

$pageTitle = 'Edit Activity';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1 class="mt-0">Edit Activity</h1>
  <p class="text-muted">Trip: <?= e($act['trip_name']) ?></p>
  <form method="post" action="<?= BASE_URL ?>/edit-activity.php?id=<?= $actId ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $actId ?>">
    <div class="form-group">
      <label for="name">Activity Name *</label>
      <input type="text" id="name" name="name" required maxlength="150" value="<?= e($name) ?>">
      <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
      <label for="destination_id">Destination</label>
      <select id="destination_id" name="destination_id">
        <option value="">— None —</option>
        <?php foreach ($destinations as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= (string)$destination_id === (string)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($errors['destination_id'])): ?><div class="field-error"><?= e($errors['destination_id']) ?></div><?php endif; ?>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="activity_date">Date *</label>
        <input type="date" id="activity_date" name="activity_date" required min="<?= e($act['trip_start']) ?>" max="<?= e($act['trip_end']) ?>" value="<?= e($activity_date) ?>">
        <?php if (!empty($errors['activity_date'])): ?><div class="field-error"><?= e($errors['activity_date']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" maxlength="255" value="<?= e($location) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="start_time">Start Time</label>
        <input type="time" id="start_time" name="start_time" value="<?= e(substr((string)$start_time,0,5)) ?>">
      </div>
      <div class="form-group">
        <label for="end_time">End Time</label>
        <input type="time" id="end_time" name="end_time" value="<?= e(substr((string)$end_time,0,5)) ?>">
        <?php if (!empty($errors['end_time'])): ?><div class="field-error"><?= e($errors['end_time']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="estimated_cost">Estimated Cost</label>
      <input type="number" id="estimated_cost" name="estimated_cost" min="0" step="0.01" value="<?= e((string)$estimated_cost) ?>">
      <?php if (!empty($errors['estimated_cost'])): ?><div class="field-error"><?= e($errors['estimated_cost']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"><?= e($description) ?></textarea>
    </div>
    <div class="btn-row">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <a class="btn btn-secondary" href="<?= BASE_URL ?>/trip-details.php?id=<?= $tripId ?>">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
