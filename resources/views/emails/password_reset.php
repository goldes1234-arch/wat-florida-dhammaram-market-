<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#4F46E5;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('email.password_reset_title') ?></h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <p><?= __('email.greeting', ['name' => $user['name']]) ?></p>
    <p><?= __('email.password_reset_body') ?></p>
    <p style="text-align:center;margin:22px 0;">
      <a href="<?= e($link) ?>" style="background:#4F46E5;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;"><?= __('email.password_reset_cta') ?></a>
    </p>
    <p style="font-size:12px;color:#94A3B8;"><?= __('email.password_reset_expiry') ?></p>
    <p style="font-size:12px;color:#94A3B8;margin-top:22px;"><?= __('email.password_reset_ignore') ?></p>
  </div>
</div>
