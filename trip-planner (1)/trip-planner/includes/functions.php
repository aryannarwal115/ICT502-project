<?php
/**
 * Shared helper / business-logic functions.
 */
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    if ($path !== '' && $path[0] === '/') {
        $path = BASE_URL . $path;
    }
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function formatMoney(float $amount, string $currency = 'USD'): string
{
    return $currency . ' ' . number_format($amount, 2);
}

function formatDate(?string $date): string
{
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date('d M Y', $ts) : '';
}

function formatTime(?string $time): string
{
    if (!$time) return '';
    $ts = strtotime($time);
    return $ts ? date('h:i A', $ts) : '';
}

function validateDateRange(string $start, string $end): bool
{
    $s = strtotime($start);
    $e = strtotime($end);
    return $s !== false && $e !== false && $e >= $s;
}

/** Return true if [innerStart, innerEnd] falls within [outerStart, outerEnd] */
function dateWithinRange(string $innerStart, string $innerEnd, string $outerStart, string $outerEnd): bool
{
    $is = strtotime($innerStart);
    $ie = strtotime($innerEnd);
    $os = strtotime($outerStart);
    $oe = strtotime($outerEnd);
    if ($is === false || $ie === false || $os === false || $oe === false) return false;
    return $is >= $os && $ie <= $oe && $ie >= $is;
}

/** Fetch a trip only if it belongs to the given user (ownership check). */
function getOwnedTrip(int $tripId, int $userId): ?array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = ? AND user_id = ?');
    $stmt->execute([$tripId, $userId]);
    $trip = $stmt->fetch();
    return $trip ?: null;
}

function getTripTotals(int $tripId): array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT category, COALESCE(SUM(amount),0) as total FROM expenses WHERE trip_id = ? GROUP BY category');
    $stmt->execute([$tripId]);
    $rows = $stmt->fetchAll();

    $totals = [
        'flight' => 0, 'transport' => 0, 'accommodation' => 0,
        'activity' => 0, 'food' => 0, 'other' => 0,
    ];
    foreach ($rows as $row) {
        $totals[$row['category']] = (float)$row['total'];
    }
    $totals['grand_total'] = array_sum($totals);
    return $totals;
}

function generateShareToken(): string
{
    return bin2hex(random_bytes(32));
}

function isValidCurrency(string $c): bool
{
    return (bool)preg_match('/^[A-Z]{3}$/', $c);
}

/** All checklist items for a trip, split into packing / prep. */
function getChecklist(int $tripId, string $type): array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM trip_checklist_items WHERE trip_id = ? AND type = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$tripId, $type]);
    return $stmt->fetchAll();
}

/** Travelers registered on a trip (for expense splitting). */
function getTripTravelers(int $tripId): array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM trip_travelers WHERE trip_id = ? ORDER BY name ASC');
    $stmt->execute([$tripId]);
    return $stmt->fetchAll();
}

/**
 * Compute a "who owes whom" balance summary for a trip.
 * Each expense with paid_by + split_with is divided evenly among the travelers in split_with.
 * Returns ['balances' => [traveler_id => net], 'travelers' => [...], 'settlements' => [...]]
 */
function getExpenseBalances(int $tripId): array
{
    $travelers = getTripTravelers($tripId);
    $names = [];
    foreach ($travelers as $t) $names[$t['id']] = $t['name'];

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM expenses WHERE trip_id = ? AND paid_by IS NOT NULL AND split_with IS NOT NULL AND split_with <> ""');
    $stmt->execute([$tripId]);
    $splitExpenses = $stmt->fetchAll();

    $balances = [];
    foreach ($names as $id => $n) $balances[$id] = 0.0;

    foreach ($splitExpenses as $ex) {
        $participants = array_filter(array_map('intval', explode(',', $ex['split_with'])));
        $participants = array_values(array_unique($participants));
        if (empty($participants)) continue;
        $share = (float)$ex['amount'] / count($participants);
        foreach ($participants as $pid) {
            if (!isset($balances[$pid])) $balances[$pid] = 0.0;
            $balances[$pid] -= $share;
        }
        $payer = (int)$ex['paid_by'];
        if (!isset($balances[$payer])) $balances[$payer] = 0.0;
        $balances[$payer] += (float)$ex['amount'];
    }

    // Simple greedy settlement: who should pay whom to zero everyone out.
    $creditors = [];
    $debtors = [];
    foreach ($balances as $id => $amt) {
        if ($amt > 0.005) $creditors[] = ['id' => $id, 'amt' => $amt];
        elseif ($amt < -0.005) $debtors[] = ['id' => $id, 'amt' => -$amt];
    }
    $settlements = [];
    $i = 0; $j = 0;
    while ($i < count($debtors) && $j < count($creditors)) {
        $pay = min($debtors[$i]['amt'], $creditors[$j]['amt']);
        if ($pay > 0.005) {
            $settlements[] = [
                'from' => $names[$debtors[$i]['id']] ?? '—',
                'to' => $names[$creditors[$j]['id']] ?? '—',
                'amount' => $pay,
            ];
        }
        $debtors[$i]['amt'] -= $pay;
        $creditors[$j]['amt'] -= $pay;
        if ($debtors[$i]['amt'] <= 0.005) $i++;
        if ($creditors[$j]['amt'] <= 0.005) $j++;
    }

    return ['balances' => $balances, 'names' => $names, 'settlements' => $settlements];
}

/** Duplicate a trip (and its destinations/activities/checklist items) for the same user. */
function duplicateTrip(int $tripId, int $userId): ?int
{
    $pdo = getDB();
    $trip = getOwnedTrip($tripId, $userId);
    if (!$trip) return null;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO trips (user_id, name, description, cover_image, notes, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, "planned")');
        $stmt->execute([$userId, $trip['name'] . ' (Copy)', $trip['description'], $trip['cover_image'], $trip['notes'], $trip['start_date'], $trip['end_date']]);
        $newTripId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT * FROM destinations WHERE trip_id = ?');
        $stmt->execute([$tripId]);
        $destMap = [];
        foreach ($stmt->fetchAll() as $d) {
            $ins = $pdo->prepare('INSERT INTO destinations (trip_id, name, country, location, notes, arrival_date, departure_date, sort_order) VALUES (?,?,?,?,?,?,?,?)');
            $ins->execute([$newTripId, $d['name'], $d['country'], $d['location'], $d['notes'], $d['arrival_date'], $d['departure_date'], $d['sort_order']]);
            $destMap[$d['id']] = (int)$pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('SELECT * FROM activities WHERE trip_id = ?');
        $stmt->execute([$tripId]);
        foreach ($stmt->fetchAll() as $a) {
            $newDest = $a['destination_id'] && isset($destMap[$a['destination_id']]) ? $destMap[$a['destination_id']] : null;
            $ins = $pdo->prepare('INSERT INTO activities (trip_id, destination_id, name, description, location, activity_date, start_time, end_time, estimated_cost) VALUES (?,?,?,?,?,?,?,?,?)');
            $ins->execute([$newTripId, $newDest, $a['name'], $a['description'], $a['location'], $a['activity_date'], $a['start_time'], $a['end_time'], $a['estimated_cost']]);
        }

        $stmt = $pdo->prepare('SELECT * FROM trip_checklist_items WHERE trip_id = ?');
        $stmt->execute([$tripId]);
        foreach ($stmt->fetchAll() as $c) {
            $ins = $pdo->prepare('INSERT INTO trip_checklist_items (trip_id, type, item, is_done, sort_order) VALUES (?,?,?,0,?)');
            $ins->execute([$newTripId, $c['type'], $c['item'], $c['sort_order']]);
        }

        $pdo->commit();
        return $newTripId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return null;
    }
}
