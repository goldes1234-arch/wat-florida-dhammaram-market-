<?php $eventName = $event['name_th'] ?: ($event['name_en'] ?? ''); ?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#16A34A;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('email.admin_booking_alert_title') ?></h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <table style="width:100%;font-size:14px;border-collapse:collapse;">
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('booking.code') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:700;"><?= e($booking['booking_code']) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('event.singular') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($eventName) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('lot.singular') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($lot['code']) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('booking.booker_name') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($booking['booker_name']) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('booking.booker_phone') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($booking['booker_phone']) ?></td>
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
    <p style="font-size:12px;color:#94A3B8;margin-top:22px;"><?= __('email.admin_booking_alert_footer') ?></p>
  </div>
</div>
