<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$activityId = (int)($_GET['activity_id'] ?? $_POST['activity_id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare('
  SELECT a.*, t.id as trip_owner_check, t.user_id, t.name as trip_name
  FROM activities a JOIN trips t ON t.id = a.trip_id
  WHERE a.id = ?
');
$stmt->execute([$activityId]);
$activity = $stmt->fetch();
if (!$activity || (int)$activity['user_id'] !== (int)$user['id']) {
    flash('error', 'Activity not found.');
    redirect('/trips.php');
}
$tripId = (int)$activity['trip_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $do = $_POST['do'] ?? '';
    if ($do === 'add') {
        $author = trim($_POST['author'] ?? '') ?: 'Me';
        $body = trim($_POST['body'] ?? '');
        if ($body !== '' && mb_strlen($body) <= 2000) {
            $stmt = $pdo->prepare('INSERT INTO activity_comments (activity_id, trip_id, author, body) VALUES (?,?,?,?)');
            $stmt->execute([$activityId, $tripId, mb_substr($author, 0, 100), $body]);
        }
    } elseif ($do === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM activity_comments WHERE id = ? AND activity_id = ?');
        $stmt->execute([$id, $activityId]);
    }
    redirect('/activity-comments.php?activity_id=' . $activityId);
}

$stmt = $pdo->prepare('SELECT * FROM activity_comments WHERE activity_id = ? ORDER BY created_at ASC');
$stmt->execute([$activityId]);
$comments = $stmt->fetchAll();

$pageTitle = 'Notes – ' . $activity['name'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex-between">
  <div>
    <h1 class="mt-0">Notes &amp; Comments</h1>
    <p class="text-muted mt-0"><?= e($activity['name']) ?> &middot; <?= e($activity['trip_name']) ?></p>
  </div>
  <a class="btn btn-secondary" href="<?= BASE_URL ?>/trip-details.php?id=<?= $tripId ?>">&larr; Back to Trip</a>
</div>

<div class="card">
  <?php if (empty($comments)): ?>
    <div class="empty-state"><p>No notes yet — add the first one below.</p></div>
  <?php else: ?>
    <ul class="comment-list">
      <?php foreach ($comments as $c): ?>
        <li class="comment-item">
          <div class="comment-head">
            <strong><?= e($c['author']) ?></strong>
            <span class="text-muted"><?= e(date('d M Y, h:i A', strtotime($c['created_at']))) ?></span>
          </div>
          <p><?= nl2br(e($c['body'])) ?></p>
          <form method="post" action="<?= BASE_URL ?>/activity-comments.php">
            <?= csrf_field() ?>
            <input type="hidden" name="activity_id" value="<?= $activityId ?>">
            <input type="hidden" name="do" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="<?= BASE_URL ?>/activity-comments.php" class="comment-form">
    <?= csrf_field() ?>
    <input type="hidden" name="activity_id" value="<?= $activityId ?>">
    <input type="hidden" name="do" value="add">
    <div class="form-row">
      <div class="form-group">
        <label for="author">Your name</label>
        <input type="text" id="author" name="author" maxlength="100" value="Me">
      </div>
    </div>
    <div class="form-group">
      <label for="body">Note</label>
      <textarea id="body" name="body" required maxlength="2000" placeholder="Add a note about this activity…"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Add Note</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
