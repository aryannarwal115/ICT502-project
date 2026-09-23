<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$errors = [];
$name = $description = $notes = $start_date = $end_date = '';
$status = 'planned';

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

    $coverImageName = null;
    if (!empty($_FILES['cover_image']['name'])) {
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
                $coverImageName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                $destDir = __DIR__ . '/uploads/';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                if (!move_uploaded_file($file['tmp_name'], $destDir . $coverImageName)) {
                    $errors['cover_image'] = 'Failed to upload cover image.';
                    $coverImageName = null;
                }
            }
        } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['cover_image'] = 'There was a problem uploading the file.';
        }
    }

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare('INSERT INTO trips (user_id, name, description, cover_image, notes, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $name, $description, $coverImageName, $notes, $start_date, $end_date, $status]);
        $tripId = (int)$pdo->lastInsertId();
        flash('success', 'Trip created successfully.');
        redirect('/trip-details.php?id=' . $tripId);
    }
}

$pageTitle = 'Create Trip';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1 class="mt-0">Create a New Trip</h1>

  <div class="form-group">
    <label>Start from a template (optional)</label>
    <div class="chip-row" id="templateRow">
      <button type="button" class="chip" data-name="Beach Vacation" data-desc="A relaxing beach getaway — sun, sand, and slow mornings.">🏖 Beach Vacation</button>
      <button type="button" class="chip" data-name="City Break" data-desc="A short, fast-paced trip exploring a city's sights, food, and culture.">🏙 City Break</button>
      <button type="button" class="chip" data-name="Backpacking Trip" data-desc="Budget travel across multiple destinations with light packing.">🎒 Backpacking Trip</button>
      <button type="button" class="chip" data-name="Family Holiday" data-desc="A family-friendly trip with kid-safe activities and relaxed pacing.">👨‍👩‍👧 Family Holiday</button>
      <button type="button" class="chip" data-name="Business Trip" data-desc="A work trip — meetings, conference, and minimal downtime.">💼 Business Trip</button>
    </div>
  </div>

  <form method="post" action="<?= BASE_URL ?>/create-trip.php" enctype="multipart/form-data" novalidate data-validate data-date-range>
    <?= csrf_field() ?>
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
    <div class="form-group">
      <label for="cover_image">Cover Image (optional)</label>
      <input type="file" id="cover_image" name="cover_image" accept="image/png,image/jpeg,image/webp">
      <?php if (!empty($errors['cover_image'])): ?><div class="field-error"><?= e($errors['cover_image']) ?></div><?php endif; ?>
    </div>
    <div class="form-group">
      <label for="notes">Notes</label>
      <textarea id="notes" name="notes"><?= e($notes) ?></textarea>
    </div>
    <div class="btn-row">
      <button type="submit" class="btn btn-primary">Create Trip</button>
      <a class="btn btn-secondary" href="<?= BASE_URL ?>/dashboard.php">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
