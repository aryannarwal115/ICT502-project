<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$tripId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) {
    flash('error', 'Trip not found.');
    redirect('/trips.php');
}

$errors = [];
$name = $trip['name'];
$description = $trip['description'];
$notes = $trip['notes'];
$start_date = $trip['start_date'];
$end_date = $trip['end_date'];
$status = $trip['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'planned';
    $validStatuses = ['planned','ongoing','completed','cancelled'];

    if ($name === '' || mb_strlen($name) > 150) $errors['name'] = 'Trip name is required (max 150 characters).';
    if (!$start_date || !strtotime($start_date)) $errors['start_date'] = 'A valid start date is required.';
    if (!$end_date || !strtotime($end_date)) $errors['end_date'] = 'A valid end date is required.';
    if (empty($errors['start_date']) && empty($errors['end_date']) && !validateDateRange($start_date, $end_date)) {
        $errors['end_date'] = 'End date cannot be before start date.';
    }
    if (!in_array($status, $validStatuses, true)) $status = 'planned';

    $coverImageName = $trip['cover_image'];
    if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['cover_image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!isset($allowed[$mime])) {
                $errors['cover_image'] = 'Cover image must be a JPG, PNG, or WEBP file.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors['cover_image'] = 'Cover image must be under 5MB.';
            } else {
                $newName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                $destDir = __DIR__ . '/uploads/';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $destDir . $newName)) {
                    if ($coverImageName && file_exists($destDir . $coverImageName)) {
                        @unlink($destDir . $coverImageName);
                    }
                    $coverImageName = $newName;
                } else {
                    $errors['cover_image'] = 'Failed to upload cover image.';
                }
            }
        } else {
            $errors['cover_image'] = 'There was a problem uploading the file.';
        }
    }

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE trips SET name=?, description=?, cover_image=?, notes=?, start_date=?, end_date=?, status=? WHERE id=? AND user_id=?');
        $stmt->execute([$name, $description, $coverImageName, $notes, $start_date, $end_date, $status, $tripId, $user['id']]);
        flash('success', 'Trip updated successfully.');
        redirect('/trip-details.php?id=' . $tripId);
    }
}

$pageTitle = 'Edit Trip';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1 class="mt-0">Edit Trip</h1>
  <form method="post" action="<?= BASE_URL ?>/edit-trip.php?id=<?= $tripId ?>" enctype="multipart/form-data" novalidate data-validate data-date-range>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $tripId ?>">
    <div class="form-group">
      <label for="name">Trip Name *</label>
      <input type="text" id="name" name="name" required maxlength="150" value="<?= e($name) ?>">
      <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"><?= e($description) ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="start_date">Start Date *</label>
        <input type="date" id="start_date" name="start_date" required value="<?= e($start_date) ?>">
        <?php if (!empty($errors['start_date'])): ?><div class="field-error"><?= e($errors['start_date']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="end_date">End Date *</label>
        <input type="date" id="end_date" name="end_date" required value="<?= e($end_date) ?>">
        <?php if (!empty($errors['end_date'])): ?><div class="field-error"><?= e($errors['end_date']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (['planned','ongoing','completed','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($trip['cover_image']): ?>
      <div class="form-group">
        <label>Current Cover Image</label>
        <img class="trip-cover" style="max-width:220px" src="<?= BASE_URL ?>/uploads/<?= e($trip['cover_image']) ?>" alt="Current cover image">
      </div>
    <?php endif; ?>
    <div class="form-group">
      <label for="cover_image">Replace Cover Image (optional)</label>
      <input type="file" id="cover_image" name="cover_image" accept="image/png,image/jpeg,image/webp">
      <?php if (!empty($errors['cover_image'])): ?><div class="field-error"><?= e($errors['cover_image']) ?></div><?php endif; ?>
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
