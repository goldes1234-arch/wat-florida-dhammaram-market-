<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#0F172A;">
  <div style="background:#16A34A;color:#fff;padding:20px 24px;border-radius:12px 12px 0 0;">
    <div style="font-size:13px;opacity:.85;"><?= e($settings['org_name'] ?? '') ?></div>
    <h2 style="margin:6px 0 0;color:#fff;"><?= __('email.admin_ad_submission_alert_title') ?></h2>
  </div>
  <div style="border:1px solid #E2E8F0;border-top:none;padding:24px;border-radius:0 0 12px 12px;">
    <table style="width:100%;font-size:14px;border-collapse:collapse;">
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('settings.ads_business_name') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:700;"><?= e($ad['business_name']) ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('ads.public_form_contact_name') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($ad['contact_name'] ?? '') ?></td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:#64748B;"><?= __('ads.public_form_contact_phone') ?></td>
        <td style="padding:6px 0;text-align:right;font-weight:600;"><?= e($ad['contact_phone'] ?? '') ?></td>
      </tr>
    </table>
    <?php if (!empty($ad['description'])): ?>
      <p style="font-size:13px;color:#334155;margin-top:16px;white-space:pre-line;"><?= e($ad['description']) ?></p>
    <?php endif; ?>
    <p style="font-size:12px;color:#94A3B8;margin-top:22px;"><?= __('email.admin_ad_submission_alert_footer') ?></p>
  </div>
</div>
