<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#4F46E5;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('contact.email_heading') ?></h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <table style="width:100%;font-size:14px;border-collapse:collapse;">
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('contact.form_name') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($name) ?></td>
      </tr>
      <?php if (!empty($email)): ?>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('contact.form_email') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($email) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($phone)): ?>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('contact.form_phone') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($phone) ?></td>
      </tr>
      <?php endif; ?>
    </table>
    <div style="background:#F1F5F9;border-radius:10px;padding:14px 16px;margin-top:16px;font-size:14px;white-space:pre-line;"><?= e($message) ?></div>
  </div>
</div>
