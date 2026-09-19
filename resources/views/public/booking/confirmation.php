<div class="container-narrow" style="padding:0;text-align:center;">
  <div style="font-size:44px;">🎉</div>
  <h1><?= __('public.confirmation_title') ?></h1>

  <div class="confirmation-code-box">
    <div class="code-label"><?= __('public.confirmation_code_label') ?></div>
    <div class="code-value"><?= e($booking['booking_code']) ?></div>
  </div>
  <p class="text-muted"><?= __('public.confirmation_save_code') ?></p>

  <?= partial('booking_qr', ['booking' => $booking]) ?>

  <?php if (!empty($booking['booker_email'])): ?>
    <p class="text-sm text-muted"><?= __('public.confirmation_email_sent', ['email' => $booking['booker_email']]) ?></p>
  <?php endif; ?>

  <p>
    <a href="<?= base_url('booking/' . $booking['booking_code'] . '/receipt') ?>" target="_blank" rel="noopener" class="btn btn-secondary">
      🖨️ <?= __('public.print_receipt_button') ?>
    </a>
  </p>
</div>

<div class="card text-center" style="max-width:520px;margin:24px auto 0;">
  <div class="info-row"><span class="info-label"><?= __('event.singular') ?></span><span class="info-value"><?= e($booking['event_name_th']) ?></span></div>
  <div class="info-row"><span class="info-label"><?= __('lot.singular') ?></span><span class="info-value"><?= e($booking['lot_code']) ?></span></div>
  <div class="info-row"><span class="info-label"><?= __('lot.price') ?></span><span class="info-value"><?= money((float) $booking['price_at_booking'], $booking['currency_code']) ?></span></div>
  <div class="info-row"><span class="info-label"><?= __('booking.payment_method') ?></span><span class="info-value"><?= payment_method_label($booking['payment_method']) ?></span></div>
</div>

<?php if ($booking['payment_method'] === 'onsite_cash'): ?>
  <div class="card" style="max-width:520px;margin:16px auto 0;">
    <p class="mb-0"><?= __('public.confirmation_instructions_onsite') ?></p>
  </div>
<?php elseif ($booking['payment_method'] === 'bank_transfer'): ?>
  <div class="card" style="max-width:520px;margin:16px auto 0;">
    <p><?= __('public.confirmation_instructions_bank') ?></p>
    <div class="bank-details">
      <div class="info-row"><span class="info-label"><?= __('settings.bank_name') ?></span><span class="info-value"><?= e($settings['bank_name'] ?? '') ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('settings.bank_account_name') ?></span><span class="info-value"><?= e($settings['bank_account_name'] ?? '') ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('settings.bank_account_number') ?></span><span class="info-value"><?= e($settings['bank_account_number'] ?? '') ?></span></div>
      <?php if (!empty($settings['promptpay_id'])): ?>
        <div class="info-row"><span class="info-label"><?= __('settings.promptpay_id') ?></span><span class="info-value"><?= e($settings['promptpay_id']) ?></span></div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<p class="text-center mt-6"><a href="<?= base_url('my-booking/' . $booking['booking_code']) ?>"><?= __('nav.my_booking') ?> &rarr;</a></p>
