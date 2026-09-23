<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$tripId = (int)($_GET['trip_id'] ?? $_POST['trip_id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) { flash('error', 'Trip not found.'); redirect('/trips.php'); }

$type = ($_GET['type'] ?? $_POST['type'] ?? 'packing') === 'prep' ? 'prep' : 'packing';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['do'] ?? '';
    if ($action === 'add') {
        $item = trim($_POST['item'] ?? '');
        if ($item !== '' && mb_strlen($item) <= 150) {
            $stmt = $pdo->prepare('INSERT INTO trip_checklist_items (trip_id, type, item) VALUES (?,?,?)');
            $stmt->execute([$tripId, $type, $item]);
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE trip_checklist_items SET is_done = 1 - is_done WHERE id = ? AND trip_id = ?');
        $stmt->execute([$id, $tripId]);
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM trip_checklist_items WHERE id = ? AND trip_id = ?');
        $stmt->execute([$id, $tripId]);
    }
    redirect('/checklist.php?trip_id=' . $tripId . '&type=' . $type);
}

$items = getChecklist($tripId, $type);
$done = count(array_filter($items, fn($i) => (int)$i['is_done'] === 1));
$total = count($items);
$pct = $total ? round($done / $total * 100) : 0;

$pageTitle = ($type === 'prep' ? 'Trip Prep' : 'Packing List') . ' – ' . $trip['name'];
require_once __DIR__ . '/includes/header.php';

$presets = $type === 'packing'
    ? ['Passport', 'Phone charger', 'Toiletries', 'Medications', 'Travel adapter']
    : ['Book accommodation', 'Travel insurance', 'Check visa requirements', 'Notify bank of travel', 'Arrange airport transfer'];
?>
<div class="flex-between">
  <div>
    <h1 class="mt-0"><?= $type === 'prep' ? 'Trip Prep Checklist' : 'Packing List' ?></h1>
    <p class="text-muted mt-0"><?= e($trip['name']) ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= BASE_URL ?>/trip-details.php?id=<?= $tripId ?>">&larr; Back to Trip</a>
</div>

<div class="tab-row">
  <a class="tab-link <?= $type === 'packing' ? 'active' : '' ?>" href="<?= BASE_URL ?>/checklist.php?trip_id=<?= $tripId ?>&type=packing"><?= icon('suitcase') ?> Packing List</a>
  <a class="tab-link <?= $type === 'prep' ? 'active' : '' ?>" href="<?= BASE_URL ?>/checklist.php?trip_id=<?= $tripId ?>&type=prep"><?= icon('check') ?> Trip Prep</a>
</div>

<div class="card">
  <?php if ($total > 0): ?>
    <div class="progress-row">
      <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
      <span class="text-muted"><?= $done ?>/<?= $total ?> done</span>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/checklist.php" class="checklist-add-row">
    <?= csrf_field() ?>
    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
    <input type="hidden" name="type" value="<?= $type ?>">
    <input type="hidden" name="do" value="add">
    <input type="text" name="item" maxlength="150" placeholder="<?= $type === 'prep' ? 'Add a prep task…' : 'Add an item to pack…' ?>" required>
    <button type="submit" class="btn btn-primary btn-sm">Add</button>
  </form>

  <?php if (empty($items)): ?>
    <p class="text-muted" style="margin:10px 0 4px;">Quick add:</p>
    <div class="chip-row">
      <?php foreach ($presets as $p): ?>
        <form method="post" action="<?= BASE_URL ?>/checklist.php" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="trip_id" value="<?= $tripId ?>">
          <input type="hidden" name="type" value="<?= $type ?>">
          <input type="hidden" name="do" value="add">
          <input type="hidden" name="item" value="<?= e($p) ?>">
          <button type="submit" class="chip">+ <?= e($p) ?></button>
        </form>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <ul class="checklist">
      <?php foreach ($items as $it): ?>
        <li class="checklist-item <?= $it['is_done'] ? 'done' : '' ?>">
          <form method="post" action="<?= BASE_URL ?>/checklist.php" class="checklist-toggle-form">
            <?= csrf_field() ?>
            <input type="hidden" name="trip_id" value="<?= $tripId ?>">
            <input type="hidden" name="type" value="<?= $type ?>">
            <input type="hidden" name="do" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button type="submit" class="checklist-check" aria-label="Toggle done"><?= $it['is_done'] ? icon('check') : '' ?></button>
          </form>
          <span class="checklist-label"><?= e($it['item']) ?></span>
          <form method="post" action="<?= BASE_URL ?>/checklist.php">
            <?= csrf_field() ?>
            <input type="hidden" name="trip_id" value="<?= $tripId ?>">
            <input type="hidden" name="type" value="<?= $type ?>">
            <input type="hidden" name="do" value="delete">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button type="submit" class="checklist-remove" aria-label="Remove">&times;</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
