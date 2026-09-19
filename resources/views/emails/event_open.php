<?php $eventName = $event['name_th'] ?: ($event['name_en'] ?? ''); ?>
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#D97706;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('event.status_open') ?>!</h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <p><?= e($eventName) ?> — <?= __('event.status_open') ?></p>
    <p style="font-size:13px;color:#64748B;">
      <?= e($event['venue_name'] ?? '') ?> · <?= e(date('d/m/Y', strtotime($event['start_date']))) ?>
      – <?= e(date('d/m/Y', strtotime($event['end_date']))) ?>
    </p>
    <p style="margin-top:18px;">
      <a href="<?= full_url('events/' . $event['slug']) ?>"
         style="display:inline-block;background:#4F46E5;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;">
        <?= __('public.book_this_lot') ?>
      </a>
    </p>
    <p style="font-size:12px;color:#94A3B8;margin-top:22px;"><?= __('email.thanks') ?> — <?= e($settings['org_name'] ?? '') ?></p>
  </div>
</div>
