<?php $eventName = $lot['event_name_th'] ?: ($lot['event_name_en'] ?? ''); ?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#C2650C;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('email.vendor_reservation_invite_title') ?></h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <p><?= __('email.greeting', ['name' => $lot['reserved_vendor_name']]) ?></p>
    <p><?= __('email.vendor_reservation_invite_body', ['event' => $eventName, 'lot' => $lot['code']]) ?></p>
    <p style="text-align:center;margin:22px 0;">
      <a href="<?= full_url('reserve/' . $lot['reserved_token']) ?>" style="background:#C2650C;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;"><?= __('email.vendor_reservation_invite_cta') ?></a>
    </p>
    <p style="font-size:12px;color:#64748B;margin-top:16px;"><?= e(__('public.cancellation_policy', \App\Services\BookingService::policyDays())) ?></p>

    <p style="font-size:12px;color:#94A3B8;margin-top:22px;"><?= __('email.thanks') ?> — <?= e($settings['org_name'] ?? '') ?></p>
  </div>
</div>
