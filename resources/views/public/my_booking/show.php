<?php $cutoffDays = (int) ($settings['cancellation_cutoff_days'] ?? 3); ?>
<div class="container-narrow" style="padding:0;">
  <div class="page-header">
    <h1><?= __('public.booking_status') ?></h1>
    <span class="<?= booking_status_badge_class($booking['status']) ?>"><?= booking_status_label($booking['status']) ?></span>
  </div>

  <div class="card mb-6">
    <div class="info-row"><span class="info-label"><?= __('booking.code') ?></span><span class="info-value"><?= e($booking['booking_code']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('event.singular') ?></span><span class="info-value"><?= e($booking['event_name_th']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('lot.singular') ?></span><span class="info-value"><?= e($booking['lot_code']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('booking.booker_name') ?></span><span class="info-value"><?= e($booking['booker_name']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('lot.price') ?></span><span class="info-value"><?= money((float) $booking['price_at_booking'], $booking['currency_code']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('booking.payment_method') ?></span><span class="info-value"><?= payment_method_label($booking['payment_method']) ?></span></div>
  </div>

  <?php if (in_array($booking['status'], ['pending_payment', 'booked'], true)): ?>
    <?= partial('booking_qr', ['booking' => $booking]) ?>
  <?php endif; ?>

  <p class="text-center">
    <a href="<?= base_url('booking/' . $booking['booking_code'] . '/receipt') ?>" target="_blank" rel="noopener" class="btn btn-secondary">
      🖨️ <?= __('public.print_receipt_button') ?>
    </a>
  </p>

  <?php if ($booking['payment_method'] === 'bank_transfer' && $booking['status'] === 'pending_payment'): ?>
    <div class="card mb-6">
      <div class="card-header"><h3><?= __('public.confirmation_instructions_bank') ?></h3></div>
      <div class="bank-details">
        <div class="info-row"><span class="info-label"><?= __('settings.bank_name') ?></span><span class="info-value"><?= e($settings['bank_name'] ?? '') ?></span></div>
        <div class="info-row"><span class="info-label"><?= __('settings.bank_account_name') ?></span><span class="info-value"><?= e($settings['bank_account_name'] ?? '') ?></span></div>
        <div class="info-row"><span class="info-label"><?= __('settings.bank_account_number') ?></span><span class="info-value"><?= e($settings['bank_account_number'] ?? '') ?></span></div>
      </div>
    </div>
  <?php elseif ($booking['payment_method'] === 'stripe' && $booking['status'] === 'pending_payment'): ?>
    <div class="alert alert-error"><?= __('public.stripe_pending_body') ?></div>
  <?php endif; ?>

  <?php if ($canCancel): ?>
    <form method="post" action="<?= base_url('my-booking/' . $booking['booking_code'] . '/cancel') ?>" data-confirm="<?= e(__('public.cancel_confirm')) ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger btn-block"><?= __('public.cancel_button') ?></button>
      <p class="form-hint text-center mt-2"><?= __('public.cutoff_notice', ['days' => $cutoffDays]) ?></p>
    </form>
  <?php elseif (in_array($booking['status'], ['pending_payment', 'booked'], true)): ?>
    <p class="text-muted text-center"><?= __('public.cannot_cancel') ?></p>
  <?php endif; ?>
</div>
