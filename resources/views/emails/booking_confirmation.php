<?php $eventName = $event['name_th'] ?: ($event['name_en'] ?? ''); ?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#4F46E5;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('public.confirmation_title') ?></h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <p><?= __('email.greeting', ['name' => $booking['booker_name']]) ?></p>
    <p><?= e($eventName) ?> — <?= __('booking.lot_label') ?> <strong><?= e($lot['code']) ?></strong></p>

    <div style="background:#EEF2FF;border-radius:10px;padding:16px 18px;text-align:center;margin:18px 0;">
      <div style="font-size:12px;color:#4F46E5;font-weight:700;"><?= __('public.confirmation_code_label') ?></div>
      <div style="font-size:26px;font-weight:800;letter-spacing:.05em;color:#4F46E5;"><?= e($booking['booking_code']) ?></div>
    </div>

    <table style="width:100%;font-size:14px;border-collapse:collapse;">
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('event.start_date') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e(date('d/m/Y', strtotime($event['start_date']))) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('lot.price') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= money((float) $booking['price_at_booking'], $booking['currency_code']) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('booking.payment_method') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= payment_method_label($booking['payment_method']) ?></td>
      </tr>
    </table>

    <?php if ($booking['payment_method'] === 'bank_transfer'): ?>
      <div style="background:#F1F5F9;border-radius:10px;padding:14px 16px;margin-top:16px;font-size:13px;">
        <div><?= __('settings.bank_name') ?>: <strong><?= e($settings['bank_name'] ?? '') ?></strong></div>
        <div><?= __('settings.bank_account_name') ?>: <strong><?= e($settings['bank_account_name'] ?? '') ?></strong></div>
        <div><?= __('settings.bank_account_number') ?>: <strong><?= e($settings['bank_account_number'] ?? '') ?></strong></div>
        <?php if (!empty($settings['promptpay_id'])): ?>
          <div><?= __('settings.promptpay_id') ?>: <strong><?= e($settings['promptpay_id']) ?></strong></div>
        <?php endif; ?>
      </div>
    <?php elseif ($booking['payment_method'] === 'onsite_cash'): ?>
      <p style="font-size:13px;color:#64748B;margin-top:16px;"><?= __('public.confirmation_instructions_onsite') ?></p>
    <?php endif; ?>

    <p style="font-size:12px;color:#94A3B8;margin-top:22px;"><?= __('email.thanks') ?> — <?= e($settings['org_name'] ?? '') ?></p>
  </div>
</div>
