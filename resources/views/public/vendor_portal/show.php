<?php
/** @var array|null $vendor */
/** @var array $history */
?>
<div class="container-narrow" style="padding:0;">
  <div class="page-header">
    <h1><?= __('vendor_portal.title') ?></h1>
  </div>

  <div class="card">
    <?php if (!$vendor): ?>
      <div class="empty-state">
        <div class="empty-icon">⏳</div>
        <?= __('vendor_portal.expired') ?>
      </div>
    <?php else: ?>
      <p class="text-muted mb-4"><?= __('vendor_portal.greeting', ['name' => $vendor['name']]) ?></p>

      <?php if (!$history): ?>
        <div class="empty-state">
          <div class="empty-icon">🧑‍🌾</div>
          <?= __('vendor_portal.no_history') ?>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th><?= __('event.singular') ?></th>
                <th><?= __('lot.singular') ?></th>
                <th><?= __('booking.code') ?></th>
                <th><?= __('common.status') ?></th>
                <th><?= __('public.event_dates') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($history as $booking): ?>
                <tr>
                  <td><?= e($booking['event_name_th']) ?></td>
                  <td><strong><?= e($booking['lot_code']) ?></strong></td>
                  <td><?= e($booking['booking_code']) ?></td>
                  <td><span class="<?= booking_status_badge_class($booking['status']) ?>"><?= booking_status_label($booking['status']) ?></span></td>
                  <td><?= e(date('d/m/Y', strtotime((string) $booking['event_start_date']))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
