<?php
/** @var array $booking */
/** @var array $settings */
$locale = \App\Core\Lang::locale();
$eventName = $booking['event_name_th'];
if ($locale === 'en') {
    $eventName = $booking['event_name_en'] ?: $booking['event_name_th'];
}
?><!doctype html>
<html lang="<?= e($locale) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(__('public.receipt_title')) ?> <?= e($booking['booking_code']) ?> · <?= e($settings['org_name'] ?: __('common.app_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
<link rel="stylesheet" href="<?= asset('css/public.css') ?>">
</head>
<body>

<div class="receipt-toolbar">
  <a href="<?= base_url('my-booking/' . $booking['booking_code']) ?>" class="back-link"><?= __('public.receipt_back') ?></a>
  <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ <?= __('public.print_receipt_button') ?></button>
</div>

<div class="receipt-sheet">
  <div class="receipt-paper">
    <div class="receipt-org">
      <?php if (!empty($settings['logo_path'])): ?>
        <img src="<?= upload_url($settings['logo_path']) ?>" alt="<?= e($settings['org_name']) ?>">
      <?php else: ?>
        <span class="receipt-org-mark">🛕</span>
      <?php endif; ?>
      <div>
        <div class="receipt-org-name"><?= e($settings['org_name'] ?: __('common.app_name')) ?></div>
        <?php if (!empty($settings['org_address']) || !empty($settings['org_phone'])): ?>
          <div class="receipt-org-meta">
            <?= e($settings['org_address'] ?? '') ?><?php if (!empty($settings['org_address']) && !empty($settings['org_phone'])): ?> · <?php endif; ?><?= e($settings['org_phone'] ?? '') ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <h1 class="receipt-title"><?= __('public.receipt_title') ?></h1>

    <div class="confirmation-code-box">
      <div class="code-label"><?= __('public.confirmation_code_label') ?></div>
      <div class="code-value"><?= e($booking['booking_code']) ?></div>
    </div>

    <?php if (in_array($booking['status'], ['pending_payment', 'booked'], true)): ?>
      <?= partial('booking_qr', ['booking' => $booking]) ?>
    <?php endif; ?>

    <div class="receipt-section-label"><?= __('public.receipt_details') ?></div>
    <div class="info-row"><span class="info-label"><?= __('event.singular') ?></span><span class="info-value"><?= e($eventName) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('lot.singular') ?></span><span class="info-value"><?= e($booking['lot_code']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('lot.price') ?></span><span class="info-value"><?= money((float) $booking['price_at_booking'], $booking['currency_code']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('booking.payment_method') ?></span><span class="info-value"><?= payment_method_label($booking['payment_method']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('public.receipt_status') ?></span><span class="info-value"><span class="<?= booking_status_badge_class($booking['status']) ?>"><?= booking_status_label($booking['status']) ?></span></span></div>
    <div class="info-row"><span class="info-label"><?= __('public.receipt_created_at') ?></span><span class="info-value"><?= e(date('d/m/Y H:i', strtotime($booking['created_at']))) ?></span></div>

    <div class="receipt-section-label"><?= __('public.receipt_booker') ?></div>
    <div class="info-row"><span class="info-label"><?= __('booking.booker_name') ?></span><span class="info-value"><?= e($booking['booker_name']) ?></span></div>
    <div class="info-row"><span class="info-label"><?= __('booking.booker_phone') ?></span><span class="info-value"><?= e($booking['booker_phone']) ?></span></div>
    <?php if (!empty($booking['booker_email'])): ?>
      <div class="info-row"><span class="info-label"><?= __('booking.booker_email') ?></span><span class="info-value"><?= e($booking['booker_email']) ?></span></div>
    <?php endif; ?>

    <?php if ($booking['payment_method'] === 'bank_transfer' && $booking['status'] === 'pending_payment'): ?>
      <div class="receipt-section-label"><?= __('public.confirmation_instructions_bank') ?></div>
      <div class="bank-details">
        <div class="info-row"><span class="info-label"><?= __('settings.bank_name') ?></span><span class="info-value"><?= e($settings['bank_name'] ?? '') ?></span></div>
        <div class="info-row"><span class="info-label"><?= __('settings.bank_account_name') ?></span><span class="info-value"><?= e($settings['bank_account_name'] ?? '') ?></span></div>
        <div class="info-row"><span class="info-label"><?= __('settings.bank_account_number') ?></span><span class="info-value"><?= e($settings['bank_account_number'] ?? '') ?></span></div>
        <?php if (!empty($settings['promptpay_id'])): ?>
          <div class="info-row"><span class="info-label"><?= __('settings.promptpay_id') ?></span><span class="info-value"><?= e($settings['promptpay_id']) ?></span></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <p class="receipt-footer-note"><?= __('public.receipt_generated_note', ['datetime' => date('d/m/Y H:i')]) ?></p>
  </div>
</div>

</body>
</html>
