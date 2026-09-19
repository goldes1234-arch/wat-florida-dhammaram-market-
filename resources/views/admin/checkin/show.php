<div class="page-header">
  <h1><?= __('checkin.title') ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/checkin') ?>" class="btn btn-secondary">&larr; <?= __('checkin.search_another') ?></a>
  </div>
</div>

<div class="card" style="max-width:480px;">
  <div class="text-center mb-4">
    <div class="text-sm text-muted" style="font-weight:700;"><?= __('booking.code') ?></div>
    <div style="font-size:28px;font-weight:800;letter-spacing:.05em;color:var(--color-primary);"><?= e($booking['booking_code']) ?></div>
  </div>

  <div class="info-row"><span class="info-label"><?= __('event.singular') ?></span><span class="info-value"><?= e($booking['event_name_th']) ?></span></div>
  <div class="info-row"><span class="info-label"><?= __('lot.singular') ?></span><span class="info-value"><?= e($booking['lot_code']) ?></span></div>
  <div class="info-row"><span class="info-label"><?= __('booking.booker_name') ?></span><span class="info-value"><?= e($booking['booker_name']) ?></span></div>
  <div class="info-row"><span class="info-label"><?= __('booking.booker_phone') ?></span><span class="info-value"><?= e($booking['booker_phone']) ?></span></div>
  <div class="info-row"><span class="info-label"><?= __('common.status') ?></span><span class="info-value"><span class="<?= booking_status_badge_class($booking['status']) ?>"><?= booking_status_label($booking['status']) ?></span></span></div>

  <?php if ($booking['status'] !== 'booked'): ?>
    <div class="alert alert-error mt-4"><?= __('checkin.not_confirmed_yet') ?></div>
  <?php elseif ($booking['checked_in_at']): ?>
    <div class="alert alert-success mt-4">✅ <?= __('checkin.checked_in_label') ?> <?= e(date('d/m/Y H:i', strtotime($booking['checked_in_at']))) ?></div>
  <?php else: ?>
    <form method="post" action="<?= base_url('admin/checkin/' . $booking['booking_code'] . '/confirm') ?>" class="mt-4">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-success btn-lg btn-block" style="padding:20px;font-size:18px;">✅ <?= __('checkin.confirm_button') ?></button>
    </form>
  <?php endif; ?>
</div>
