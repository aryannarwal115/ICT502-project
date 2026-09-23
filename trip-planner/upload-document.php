<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/trips.php');
csrf_require();

$tripId = (int)($_POST['trip_id'] ?? 0);
$trip = getOwnedTrip($tripId, $user['id']);
if (!$trip) { flash('error', 'Trip not found.'); redirect('/trips.php'); }

if (!empty($_FILES['document']['name'])) {
    $file = $_FILES['document'];
    $allowed = [
        'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png',
        'image/webp' => 'webp', 'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!isset($allowed[$mime])) {
            flash('error', 'Unsupported file type. Allowed: PDF, JPG, PNG, WEBP, DOC, DOCX.');
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            flash('error', 'File must be under 10MB.');
        } else {
            $stored = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            $destDir = __DIR__ . '/uploads/documents/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $destDir . $stored)) {
                $stmt = getDB()->prepare('INSERT INTO trip_documents (trip_id, original_name, stored_name, file_size) VALUES (?,?,?,?)');
                $stmt->execute([$tripId, mb_substr(basename($file['name']), 0, 255), $stored, (int)$file['size']]);
                flash('success', 'Document uploaded.');
            } else {
                flash('error', 'Failed to upload document.');
            }
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        flash('error', 'There was a problem uploading the file.');
    }
}

redirect('/trip-details.php?id=' . $tripId);
