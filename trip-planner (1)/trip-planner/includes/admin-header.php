<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
$admin = function_exists('currentAdmin') ? currentAdmin() : null;
$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> - Admin - Trip Planner</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header" style="background:#101828;">
  <div class="container header-inner">
    <a class="logo" href="<?= BASE_URL ?>/admin/dashboard.php">🛠 Trip Planner Admin</a>
    <?php if ($admin): ?>
    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/admin/users.php">Users</a></li>
        <li><a href="<?= BASE_URL ?>/admin/trips.php">Trips</a></li>
        <li><a href="<?= BASE_URL ?>/admin/settings.php">Settings</a></li>
        <li><a href="<?= BASE_URL ?>/admin/logout.php">Logout (<?= e($admin['name']) ?>)</a></li>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</header>
<main class="container main-content">
<?php
$err = flash('error');
$ok  = flash('success');
if ($err): ?>
  <div class="alert alert-error" role="alert"><?= e($err) ?></div>
<?php endif;
if ($ok): ?>
  <div class="alert alert-success" role="alert"><?= e($ok) ?></div>
<?php endif; ?>
