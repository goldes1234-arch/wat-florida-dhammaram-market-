<?php $eventName = $event['name_th'] ?: ($event['name_en'] ?? ''); ?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#4F46E5;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('email.event_reminder_title') ?></h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <p><?= __('email.greeting', ['name' => $booking['booker_name']]) ?></p>
    <p><?= __('email.event_reminder_body', ['event' => e($eventName), 'date' => e(date('d/m/Y', strtotime((string) $event['start_date'])))]) ?></p>

    <table style="width:100%;font-size:14px;border-collapse:collapse;margin-top:14px;">
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('event.start_date') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e(date('d/m/Y', strtotime((string) $event['start_date']))) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('lot.singular') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($booking['lot_code']) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('booking.code') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($booking['booking_code']) ?></td>
      </tr>
    </table>

    <p style="font-size:12px;color:#94A3B8;margin-top:22px;"><?= __('email.thanks') ?> — <?= e($settings['org_name'] ?? '') ?></p>
  </div>
</div>
