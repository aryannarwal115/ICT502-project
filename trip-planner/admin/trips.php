<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/csrf.php';
$admin = requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trip'])) {
    csrf_require();
    $tripId = (int)$_POST['trip_id'];
    $stmt = $pdo->prepare('SELECT cover_image FROM trips WHERE id = ?');
    $stmt->execute([$tripId]);
    $cover = $stmt->fetchColumn();
    $stmt = $pdo->prepare('DELETE FROM trips WHERE id = ?');
    $stmt->execute([$tripId]);
    if ($cover) {
        $path = __DIR__ . '/../uploads/' . $cover;
        if (file_exists($path)) @unlink($path);
    }
    flash('success', 'Trip deleted.');
    redirect('/admin/trips.php');
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("
      SELECT t.*, u.name as owner_name FROM trips t JOIN users u ON u.id = t.user_id
      WHERE t.name LIKE ? OR u.name LIKE ?
      ORDER BY t.created_at DESC
    ");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT t.*, u.name as owner_name FROM trips t JOIN users u ON u.id = t.user_id ORDER BY t.created_at DESC");
}
$trips = $stmt->fetchAll();

$viewId = (int)($_GET['view'] ?? 0);
$viewTrip = null;
if ($viewId) {
    $stmt = $pdo->prepare("SELECT t.*, u.name as owner_name, u.email as owner_email FROM trips t JOIN users u ON u.id = t.user_id WHERE t.id = ?");
    $stmt->execute([$viewId]);
    $viewTrip = $stmt->fetch() ?: null;
    if ($viewTrip) {
        $totals = getTripTotals($viewId);
    }
}

$pageTitle = 'Manage Trips';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<h1 class="mt-0">Trips</h1>

<div class="card">
  <form method="get" action="<?= BASE_URL ?>/admin/trips.php" class="flex-between">
    <input type="text" name="q" placeholder="Search by trip or owner name" value="<?= e($search) ?>" style="max-width:300px;">
    <button type="submit" class="btn btn-secondary">Search</button>
  </form>
</div>

<?php if ($viewTrip): ?>
  <div class="card">
    <h2 class="mt-0"><?= e($viewTrip['name']) ?></h2>
    <p>Owner: <?= e($viewTrip['owner_name']) ?> (<?= e($viewTrip['owner_email']) ?>)</p>
    <p>Dates: <?= e(formatDate($viewTrip['start_date'])) ?> &ndash; <?= e(formatDate($viewTrip['end_date'])) ?></p>
    <p>Status: <span class="badge badge-<?= e($viewTrip['status']) ?>"><?= e(ucfirst($viewTrip['status'])) ?></span></p>
    <p>Total estimated cost: <?= e(formatMoney($totals['grand_total'])) ?></p>
    <?php if ($viewTrip['description']): ?><p><?= nl2br(e($viewTrip['description'])) ?></p><?php endif; ?>
    <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/admin/trips.php">&larr; Back to list</a>
  </div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Trip</th><th>Owner</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($trips as $t): ?>
        <tr>
          <td><?= e($t['name']) ?></td>
          <td><?= e($t['owner_name']) ?></td>
          <td><?= e(formatDate($t['start_date'])) ?> &ndash; <?= e(formatDate($t['end_date'])) ?></td>
          <td><span class="badge badge-<?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td>
            <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/admin/trips.php?view=<?= (int)$t['id'] ?>">View</a>
            <form style="display:inline" method="post" action="<?= BASE_URL ?>/admin/trips.php" data-confirm="Delete this trip permanently?">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_trip" value="1">
              <input type="hidden" name="trip_id" value="<?= (int)$t['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($trips)): ?>
        <tr><td colspan="5" class="text-muted">No trips found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
