<?php
/**
 * Shared itinerary rendering logic used by itinerary.php (owner view)
 * and shared-trip.php (public view). Expects $trip, $destinations,
 * $activitiesByDate, $totals to be set by the including page.
 */
?>
<div class="card">
  <h1 class="mt-0"><?= e($trip['name']) ?></h1>
  <p class="text-muted"><?= e(formatDate($trip['start_date'])) ?> &ndash; <?= e(formatDate($trip['end_date'])) ?></p>
  <?php if (!empty($trip['description'])): ?><p><?= nl2br(e($trip['description'])) ?></p><?php endif; ?>

  <h2>Destinations</h2>
  <?php if (empty($destinations)): ?>
    <p class="text-muted">No destinations added.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($destinations as $d): ?>
        <li><strong><?= e($d['name']) ?></strong>, <?= e($d['country']) ?> &mdash; <?= e(formatDate($d['arrival_date'])) ?> to <?= e(formatDate($d['departure_date'])) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <h2>Daily Schedule</h2>
  <?php if (empty($activitiesByDate)): ?>
    <p class="text-muted">No activities scheduled.</p>
  <?php else: ?>
    <?php foreach ($activitiesByDate as $date => $items): ?>
      <div class="day-block">
        <h3><?= e(formatDate($date)) ?></h3>
        <?php foreach ($items as $a): ?>
          <div class="activity-item">
            <strong><?= e($a['name']) ?></strong>
            <?php if ($a['start_time']): ?> &mdash; <?= e(formatTime($a['start_time'])) ?><?= $a['end_time'] ? ' to ' . e(formatTime($a['end_time'])) : '' ?><?php endif; ?>
            <?php if (!empty($a['destination_name'])): ?><br><span class="text-muted"><?= e($a['destination_name']) ?></span><?php endif; ?>
            <?php if (!empty($a['location'])): ?><br><span class="text-muted"><?= icon('pin', 'icon-inline') ?> <?= e($a['location']) ?></span><?php endif; ?>
            <?php if (!empty($a['description'])): ?><br><?= nl2br(e($a['description'])) ?><?php endif; ?>
            <?php if ((float)$a['estimated_cost'] > 0): ?><br><span class="text-muted">Estimated cost: <?= e(formatMoney((float)$a['estimated_cost'])) ?></span><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <h2>Estimated Costs</h2>
  <div class="cost-summary">
    <div class="cost-item"><strong><?= e(formatMoney($totals['flight'])) ?></strong><br>Flights</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['transport'])) ?></strong><br>Transport</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['accommodation'])) ?></strong><br>Accommodation</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['activity'])) ?></strong><br>Activities</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['food'])) ?></strong><br>Food</div>
    <div class="cost-item"><strong><?= e(formatMoney($totals['other'])) ?></strong><br>Other</div>
    <div class="cost-item total"><strong><?= e(formatMoney($totals['grand_total'])) ?></strong><br>Total Estimated Cost</div>
  </div>

  <?php if (!empty($trip['notes'])): ?>
    <h2>Notes</h2>
    <p><?= nl2br(e($trip['notes'])) ?></p>
  <?php endif; ?>
</div>
