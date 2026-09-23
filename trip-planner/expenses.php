<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

$tripId = (int)($_GET['trip_id'] ?? $_POST['trip_id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) { flash('error', 'Trip not found.'); redirect('/trips.php'); }

$pdo = getDB();
$categories = ['flight','transport','accommodation','activity','food','other'];
$errors = [];

// Traveler management (add/remove) — used for expense splitting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_traveler'])) {
    csrf_require();
    $name = trim($_POST['traveler_name'] ?? '');
    if ($name !== '' && mb_strlen($name) <= 100) {
        $stmt = $pdo->prepare('INSERT INTO trip_travelers (trip_id, name) VALUES (?, ?)');
        $stmt->execute([$tripId, $name]);
    }
    redirect('/expenses.php?trip_id=' . $tripId);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_traveler'])) {
    csrf_require();
    $tid = (int)($_POST['traveler_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM trip_travelers WHERE id = ? AND trip_id = ?');
    $stmt->execute([$tid, $tripId]);
    redirect('/expenses.php?trip_id=' . $tripId);
}

$editId = (int)($_GET['edit'] ?? 0);
$editingExpense = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM expenses WHERE id = ? AND trip_id = ?');
    $stmt->execute([$editId, $tripId]);
    $editingExpense = $stmt->fetch() ?: null;
}

$category = $editingExpense['category'] ?? 'other';
$description = $editingExpense['description'] ?? '';
$amount = $editingExpense['amount'] ?? '';
$currency = $editingExpense['currency'] ?? 'USD';
$expense_date = $editingExpense['expense_date'] ?? date('Y-m-d');
$notes = $editingExpense['notes'] ?? '';
$paidBy = $editingExpense['paid_by'] ?? '';
$splitWith = $editingExpense['split_with'] ?? '';
$splitWithArr = $splitWith ? array_map('intval', explode(',', $splitWith)) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_expense'])) {
    csrf_require();
    $expenseId = (int)($_POST['expense_id'] ?? 0);
    $category = $_POST['category'] ?? 'other';
    $description = trim($_POST['description'] ?? '');
    $amount = $_POST['amount'] ?? '';
    $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
    $expense_date = $_POST['expense_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $paidBy = ($_POST['paid_by'] ?? '') !== '' ? (int)$_POST['paid_by'] : null;
    $splitWithArr = array_map('intval', $_POST['split_with'] ?? []);
    $splitWith = $splitWithArr ? implode(',', $splitWithArr) : null;

    if (!in_array($category, $categories, true)) $errors['category'] = 'Invalid category.';
    if ($description === '' || mb_strlen($description) > 255) $errors['description'] = 'Description is required (max 255 characters).';
    if (!is_numeric($amount) || (float)$amount < 0) $errors['amount'] = 'Amount must be a non-negative number.';
    if (!isValidCurrency($currency)) $errors['currency'] = 'Currency must be a 3-letter code (e.g. USD).';
    if ($expense_date && !strtotime($expense_date)) $errors['expense_date'] = 'Invalid date.';

    if (empty($errors)) {
        if ($expenseId) {
            // verify ownership
            $stmt = $pdo->prepare('SELECT id FROM expenses WHERE id = ? AND trip_id = ?');
            $stmt->execute([$expenseId, $tripId]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare('UPDATE expenses SET category=?, description=?, amount=?, currency=?, expense_date=?, notes=?, paid_by=?, split_with=? WHERE id=? AND trip_id=?');
                $stmt->execute([$category, $description, (float)$amount, $currency, $expense_date ?: null, $notes, $paidBy, $splitWith, $expenseId, $tripId]);
                flash('success', 'Expense updated.');
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO expenses (trip_id, category, description, amount, currency, expense_date, notes, paid_by, split_with) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$tripId, $category, $description, (float)$amount, $currency, $expense_date ?: null, $notes, $paidBy, $splitWith]);
            flash('success', 'Expense added.');
        }
        redirect('/expenses.php?trip_id=' . $tripId);
    }
}

$stmt = $pdo->prepare('SELECT * FROM expenses WHERE trip_id = ? ORDER BY expense_date DESC, id DESC');
$stmt->execute([$tripId]);
$expenses = $stmt->fetchAll();
$totals = getTripTotals($tripId);
$travelers = getTripTravelers($tripId);
$travelerNames = [];
foreach ($travelers as $t) $travelerNames[$t['id']] = $t['name'];
$balanceData = getExpenseBalances($tripId);

$showForm = $editingExpense !== null || isset($_GET['action']) || !empty($errors);

$pageTitle = 'Expenses';
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex-between">
  <h1 class="mt-0">Expenses &ndash; <?= e($trip['name']) ?></h1>
  <a class="btn btn-secondary" href="<?= BASE_URL ?>/trip-details.php?id=<?= $tripId ?>">&larr; Back to Trip</a>
</div>

<div class="cost-summary">
  <div class="cost-item"><strong><?= e(formatMoney($totals['flight'])) ?></strong><br>Flights</div>
  <div class="cost-item"><strong><?= e(formatMoney($totals['transport'])) ?></strong><br>Transport</div>
  <div class="cost-item"><strong><?= e(formatMoney($totals['accommodation'])) ?></strong><br>Accommodation</div>
  <div class="cost-item"><strong><?= e(formatMoney($totals['activity'])) ?></strong><br>Activities</div>
  <div class="cost-item"><strong><?= e(formatMoney($totals['food'])) ?></strong><br>Food</div>
  <div class="cost-item"><strong><?= e(formatMoney($totals['other'])) ?></strong><br>Other</div>
  <div class="cost-item total"><strong><?= e(formatMoney($totals['grand_total'])) ?></strong><br>Total</div>
</div>

<!-- Travelers & Split Expenses -->
<div class="card">
  <h2 class="mt-0"><?= icon('users', 'icon-inline') ?> Travelers &amp; Splitting</h2>
  <p class="text-muted mt-0">Add the people on this trip, then assign expenses to whoever paid and split the cost between travelers.</p>
  <form method="post" action="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>" class="checklist-add-row">
    <?= csrf_field() ?>
    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
    <input type="hidden" name="add_traveler" value="1">
    <input type="text" name="traveler_name" maxlength="100" placeholder="Add a traveler (e.g. Priya)" required>
    <button type="submit" class="btn btn-primary btn-sm">Add</button>
  </form>
  <?php if (!empty($travelers)): ?>
    <div class="chip-row" style="margin-top:12px;">
      <?php foreach ($travelers as $t): ?>
        <form method="post" action="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="trip_id" value="<?= $tripId ?>">
          <input type="hidden" name="delete_traveler" value="1">
          <input type="hidden" name="traveler_id" value="<?= (int)$t['id'] ?>">
          <button type="submit" class="chip chip-removable"><?= e($t['name']) ?> &times;</button>
        </form>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($balanceData['settlements'])): ?>
      <h3 style="margin-top:18px;">Who Owes Whom</h3>
      <ul class="settlement-list">
        <?php foreach ($balanceData['settlements'] as $s): ?>
          <li><strong><?= e($s['from']) ?></strong> owes <strong><?= e($s['to']) ?></strong> <span class="text-muted"><?= e(formatMoney($s['amount'])) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php elseif (count($travelers) > 1): ?>
      <p class="help-text" style="margin-top:14px;">No split expenses yet — assign "Paid by" and "Split with" when adding an expense below.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="help-text">No travelers added yet — add at least two to enable expense splitting.</p>
  <?php endif; ?>
</div>

<div class="card">
  <div class="flex-between">
    <h2 class="mt-0"><?= $editingExpense ? 'Edit Expense' : 'Add Expense' ?></h2>
    <?php if (!$showForm): ?>
      <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>&action=add">+ Add Expense</a>
    <?php endif; ?>
  </div>
  <?php if ($showForm || $editingExpense): ?>
  <form method="post" id="expenseForm" action="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="save_expense" value="1">
    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
    <input type="hidden" name="expense_id" value="<?= (int)($editingExpense['id'] ?? 0) ?>">
    <div class="form-row">
      <div class="form-group">
        <label for="category">Category *</label>
        <select id="category" name="category" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c ?>" <?= $category === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['category'])): ?><div class="field-error"><?= e($errors['category']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="expense_date">Date</label>
        <input type="date" id="expense_date" name="expense_date" value="<?= e($expense_date) ?>">
        <?php if (!empty($errors['expense_date'])): ?><div class="field-error"><?= e($errors['expense_date']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="description">Description *</label>
      <input type="text" id="description" name="description" required maxlength="255" value="<?= e($description) ?>">
      <?php if (!empty($errors['description'])): ?><div class="field-error"><?= e($errors['description']) ?></div><?php endif; ?>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="amount">Amount *</label>
        <input type="number" id="amount" name="amount" required min="0" step="0.01" value="<?= e((string)$amount) ?>">
        <div id="expensePreview" class="help-text"></div>
        <?php if (!empty($errors['amount'])): ?><div class="field-error"><?= e($errors['amount']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="currency">Currency</label>
        <input type="text" id="currency" name="currency" maxlength="3" value="<?= e($currency) ?>">
        <?php if (!empty($errors['currency'])): ?><div class="field-error"><?= e($errors['currency']) ?></div><?php endif; ?>
      </div>
    </div>
    <?php if (!empty($travelers)): ?>
    <div class="form-row">
      <div class="form-group">
        <label for="paid_by">Paid by</label>
        <select id="paid_by" name="paid_by">
          <option value="">— not tracked —</option>
          <?php foreach ($travelers as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= (int)$paidBy === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Split with</label>
        <div class="checkbox-group">
          <?php foreach ($travelers as $t): ?>
            <label class="checkbox-inline">
              <input type="checkbox" name="split_with[]" value="<?= (int)$t['id'] ?>" <?= in_array((int)$t['id'], $splitWithArr, true) ? 'checked' : '' ?>>
              <?= e($t['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div class="form-group">
      <label for="notes">Notes</label>
      <textarea id="notes" name="notes"><?= e($notes) ?></textarea>
    </div>
    <div class="btn-row">
      <button type="submit" class="btn btn-primary"><?= $editingExpense ? 'Save Changes' : 'Add Expense' ?></button>
      <a class="btn btn-secondary" href="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>">Cancel</a>
    </div>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <h2 class="mt-0">All Expenses</h2>
  <?php if (empty($expenses)): ?>
    <div class="empty-state"><p>No expenses recorded yet.</p></div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Paid by</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($expenses as $ex): ?>
          <tr>
            <td><?= e(formatDate($ex['expense_date'])) ?></td>
            <td><?= e(ucfirst($ex['category'])) ?></td>
            <td><?= e($ex['description']) ?></td>
            <td><?= e(formatMoney((float)$ex['amount'], $ex['currency'])) ?></td>
            <td><?= $ex['paid_by'] && isset($travelerNames[$ex['paid_by']]) ? e($travelerNames[$ex['paid_by']]) : '—' ?></td>
            <td>
              <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/expenses.php?trip_id=<?= $tripId ?>&edit=<?= (int)$ex['id'] ?>">Edit</a>
              <form style="display:inline" method="post" action="<?= BASE_URL ?>/delete-expense.php" data-confirm="Delete this expense?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
