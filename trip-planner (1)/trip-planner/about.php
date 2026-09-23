<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About';
require_once __DIR__ . '/includes/header.php';

$features = [
    ['map', 'Multi-trip organization', 'Create and manage multiple trips side by side.'],
    ['pin', 'Destinations & activities', 'Add destinations and plan daily activities for each stop.'],
    ['wallet', 'Expense tracking', 'Track flights, accommodation, food and other costs.'],
    ['calendar', 'Automatic totals', 'Costs are calculated automatically as you plan.'],
    ['link', 'Shareable itineraries', 'Generate secure links so anyone can follow your trip.'],
];
?>
<section class="page-hero">
  <div class="page-hero-icon"><?= icon('compass') ?></div>
  <h1 class="mt-0">About Trip Planner</h1>
  <p>A lightweight web application for organizing complete travel itineraries — destinations, day-by-day activities, and budgets — all in one place.</p>
</section>

<div class="section-heading"><h2>Features</h2></div>
<div class="card-grid">
  <?php foreach ($features as [$ic, $title, $desc]): ?>
    <div class="card feature-row">
      <div class="feature-icon"><?= icon($ic) ?></div>
      <div>
        <h3><?= e($title) ?></h3>
        <p class="text-muted mt-0"><?= e($desc) ?></p>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="section-heading"><h2>Built with</h2></div>
<section class="card tech-card">
  <div class="tech-icon"><?= icon('shield') ?></div>
  <p class="mt-0">PHP 8, MySQL, vanilla JavaScript, and plain CSS — runs on any standard XAMPP stack.</p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
