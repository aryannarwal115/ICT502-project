<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';
$user = function_exists('currentUser') ? currentUser() : null;
$pageTitle = $pageTitle ?? 'Trip Planner';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> - Trip Planner</title>
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script>
(function(){try{var t=localStorage.getItem('theme');if(t==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();
</script>
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="<?= BASE_URL ?>/index.php"><img src="<?= BASE_URL ?>/assets/images/logo.svg" width="30" height="30" alt=""> Trip Planner</a>
    <nav class="main-nav" aria-label="Main navigation">
      <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks">☰</button>
      <ul id="navLinks" class="nav-links">
        <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
        <li><a href="<?= BASE_URL ?>/about.php">About</a></li>
        <?php if ($user): ?>
          <li><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
          <li><a href="<?= BASE_URL ?>/create-trip.php">New Trip</a></li>
          <li><a href="<?= BASE_URL ?>/profile.php">Profile</a></li>
          <li><a href="<?= BASE_URL ?>/logout.php">Logout (<?= e($user['name']) ?>)</a></li>
        <?php else: ?>
          <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
          <li><a href="<?= BASE_URL ?>/register.php">Register</a></li>
        <?php endif; ?>
        <li><button type="button" id="themeToggle" class="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode"><?= icon('moon', 'theme-icon-dark') ?><?= icon('sun', 'theme-icon-light') ?></button></li>
      </ul>
    </nav>
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
