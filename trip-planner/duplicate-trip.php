<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$tripId = (int)($_POST['trip_id'] ?? 0);
$newId = duplicateTrip($tripId, $user['id']);

if ($newId) {
    flash('success', 'Trip duplicated. You can now edit the copy.');
    redirect('/trip-details.php?id=' . $newId);
}
flash('error', 'Could not duplicate this trip.');
redirect('/trips.php');
