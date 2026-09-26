<form method="post" action="<?= base_url('admin/settings') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.org_info') ?></h3></div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.org_name') ?></label>
        <input type="text" name="org_name" class="form-control" value="<?= e($settings['org_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label><?= __('settings.org_phone') ?></label>
        <input type="text" name="org_phone" class="form-control" value="<?= e($settings['org_phone'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.org_email') ?></label>
        <input type="email" name="org_email" class="form-control" value="<?= e($settings['org_email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label><?= __('settings.org_address') ?></label>
        <input type="text" name="org_address" class="form-control" value="<?= e($settings['org_address'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label><?= __('settings.logo') ?></label>
      <?php if (!empty($settings['logo_path'])): ?>
        <img src="<?= upload_url($settings['logo_path']) ?>" class="thumb-sm mb-2" alt="">
        <div class="checkbox-row mb-2">
          <input type="checkbox" id="remove_logo" name="remove_logo" value="1">
          <label for="remove_logo" style="margin:0;"><?= __('event.remove_image') ?></label>
        </div>
      <?php endif; ?>
      <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
      <p class="form-hint"><?= __('settings.logo_hint') ?></p>
    </div>
    <div class="form-group">
      <label><?= __('settings.hero_banner') ?></label>
      <?php if (!empty($settings['hero_banner_image'])): ?>
        <img src="<?= upload_url($settings['hero_banner_image']) ?>" class="thumb-md mb-2" alt="">
        <div class="checkbox-row mb-2">
          <input type="checkbox" id="remove_hero_banner_image" name="remove_hero_banner_image" value="1">
          <label for="remove_hero_banner_image" style="margin:0;"><?= __('event.remove_image') ?></label>
        </div>
      <?php endif; ?>
      <input type="file" name="hero_banner_image" class="form-control" accept="image/jpeg,image/png,image/webp">
      <p class="form-hint"><?= __('settings.hero_banner_hint') ?></p>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.socials') ?></h3></div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.website_url') ?></label>
        <input type="url" name="website_url" class="form-control" value="<?= e($settings['website_url'] ?? '') ?>" placeholder="https://...">
      </div>
      <div class="form-group">
        <label><?= __('settings.facebook_url') ?></label>
        <input type="url" name="facebook_url" class="form-control" value="<?= e($settings['facebook_url'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.line_oa_id') ?></label>
        <input type="text" name="line_oa_id" class="form-control" value="<?= e($settings['line_oa_id'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label><?= __('settings.youtube_url') ?></label>
        <input type="url" name="youtube_url" class="form-control" value="<?= e($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/@...">
      </div>
    </div>
    <div class="form-group">
      <label><?= __('settings.google_maps_url') ?></label>
      <input type="url" name="google_maps_url" class="form-control" value="<?= e($settings['google_maps_url'] ?? '') ?>">
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.other_channels') ?></h3></div>
    <p class="form-hint mb-4"><?= __('settings.other_channels_hint') ?></p>

    <?php if ($socialLinks): ?>
      <div class="table-wrap mb-4">
        <table class="table">
          <thead><tr><th><?= __('settings.channel_label') ?></th><th><?= __('settings.channel_url') ?></th><th><?= __('common.actions') ?></th></tr></thead>
          <tbody>
            <?php foreach ($socialLinks as $link): ?>
              <tr>
                <td><input type="text" name="label" form="social-form-<?= (int) $link['id'] ?>" class="form-control" value="<?= e($link['label']) ?>"></td>
                <td><input type="url" name="url" form="social-form-<?= (int) $link['id'] ?>" class="form-control" value="<?= e($link['url']) ?>"></td>
                <td style="display:flex;gap:8px;">
                  <button type="submit" form="social-form-<?= (int) $link['id'] ?>" class="btn btn-secondary btn-sm"><?= __('common.save') ?></button>
                  <button type="submit" form="social-delete-<?= (int) $link['id'] ?>" class="btn btn-danger btn-sm" data-confirm="<?= e(__('settings.remove_channel_confirm')) ?>"><?= __('common.delete') ?></button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label><?= __('settings.channel_label') ?></label>
        <input type="text" name="label" form="social-add-form" class="form-control" placeholder="TikTok" required>
      </div>
      <div class="form-group">
        <label><?= __('settings.channel_url') ?></label>
        <input type="url" name="url" form="social-add-form" class="form-control" placeholder="https://..." required>
      </div>
      <div class="form-group" style="flex:0 0 auto;">
        <button type="submit" form="social-add-form" class="btn btn-primary"><?= __('settings.add_channel') ?></button>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.gallery_section') ?></h3></div>
    <p class="form-hint mb-4"><?= __('settings.gallery_hint') ?></p>

    <?php if ($galleryPhotos): ?>
      <div class="grid grid-cols-4 mb-4">
        <?php foreach ($galleryPhotos as $photo): ?>
          <div class="card" style="padding:10px;">
            <img src="<?= upload_url($photo['image_path']) ?>" class="thumb-sm mb-2" style="width:100%;height:90px;" alt="">
            <?php if (!empty($photo['caption'])): ?><div class="text-sm text-muted mb-2"><?= e($photo['caption']) ?></div><?php endif; ?>
            <button type="submit" form="gallery-delete-<?= (int) $photo['id'] ?>" class="btn btn-danger btn-sm" style="width:100%;"><?= __('common.delete') ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label><?= __('lot.photo') ?></label>
        <input type="file" name="photo" form="gallery-add-form" class="form-control" accept="image/jpeg,image/png,image/webp" required>
      </div>
      <div class="form-group">
        <label><?= __('settings.gallery_caption_placeholder') ?></label>
        <input type="text" name="caption" form="gallery-add-form" class="form-control">
      </div>
      <div class="form-group" style="flex:0 0 auto;">
        <button type="submit" form="gallery-add-form" class="btn btn-primary"><?= __('settings.gallery_add_button') ?></button>
      </div>
    </div>
    <p class="form-hint"><?= __('settings.gallery_size_hint') ?></p>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.general') ?></h3></div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.currency') ?></label>
        <select name="currency_code" class="form-control">
          <?php foreach ($currencies as $code): ?>
            <option value="<?= e($code) ?>" <?= ($settings['currency_code'] ?? '') === $code ? 'selected' : '' ?>><?= e($code) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><?= __('settings.locale') ?></label>
        <select name="default_locale" class="form-control">
          <option value="th" <?= ($settings['default_locale'] ?? '') === 'th' ? 'selected' : '' ?>>ไทย (Thai)</option>
          <option value="en" <?= ($settings['default_locale'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.cancellation_cutoff_days') ?></label>
        <input type="number" min="0" name="cancellation_cutoff_days" class="form-control" value="<?= e((string) ($settings['cancellation_cutoff_days'] ?? 3)) ?>">
      </div>
      <div class="form-group">
        <label><?= __('settings.rate_limit') ?></label>
        <input type="number" min="1" name="booking_rate_limit_per_hour" class="form-control" value="<?= e((string) ($settings['booking_rate_limit_per_hour'] ?? 5)) ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.stripe_section') ?></h3></div>
    <p class="form-hint mb-4"><?= __('settings.stripe_hint') ?></p>

    <div class="checkbox-row mb-2">
      <input type="checkbox" id="stripe_suspended" name="stripe_suspended" value="1" <?= !empty($settings['stripe_suspended']) ? 'checked' : '' ?>>
      <label for="stripe_suspended" style="margin:0;"><?= __('settings.stripe_suspended_label') ?></label>
    </div>
    <p class="form-hint mb-4"><?= __('settings.stripe_suspended_hint') ?></p>

    <div class="form-group">
      <label><?= __('settings.stripe_publishable_key') ?></label>
      <input type="text" name="stripe_publishable_key" class="form-control" value="<?= e($settings['stripe_publishable_key'] ?? '') ?>" placeholder="pk_test_...">
    </div>
    <div class="form-group">
      <label><?= __('settings.stripe_secret_key') ?></label>
      <input type="text" name="stripe_secret_key" class="form-control" value="<?= e($settings['stripe_secret_key'] ?? '') ?>" placeholder="sk_test_...">
    </div>
    <div class="form-group">
      <label><?= __('settings.stripe_webhook_secret') ?></label>
      <input type="text" name="stripe_webhook_secret" class="form-control" value="<?= e($settings['stripe_webhook_secret'] ?? '') ?>" placeholder="whsec_...">
    </div>

    <div class="checkbox-row mb-2">
      <input type="checkbox" id="stripe_pass_fee_to_customer" name="stripe_pass_fee_to_customer" value="1" <?= !empty($settings['stripe_pass_fee_to_customer']) ? 'checked' : '' ?>>
      <label for="stripe_pass_fee_to_customer" style="margin:0;"><?= __('settings.stripe_pass_fee_label') ?></label>
    </div>
    <p class="form-hint mb-4"><?= __('settings.stripe_pass_fee_hint') ?></p>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.stripe_fee_percent') ?></label>
        <input type="number" step="0.01" min="0" max="100" name="stripe_fee_percent" class="form-control" value="<?= e((string) ($settings['stripe_fee_percent'] ?? '2.90')) ?>">
      </div>
      <div class="form-group">
        <label><?= __('settings.stripe_fee_fixed') ?></label>
        <input type="number" step="0.01" min="0" name="stripe_fee_fixed" class="form-control" value="<?= e((string) ($settings['stripe_fee_fixed'] ?? '0.30')) ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.line_section') ?></h3></div>
    <p class="form-hint mb-4"><?= __('settings.line_hint') ?></p>
    <div class="form-group">
      <label><?= __('settings.line_channel_access_token') ?></label>
      <input type="text" name="line_oa_channel_access_token" class="form-control" value="<?= e($settings['line_oa_channel_access_token'] ?? '') ?>" placeholder="•••••••••••••••••••••••••">
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('settings.smtp_section') ?></h3></div>
    <p class="form-hint mb-4"><?= __('settings.smtp_hint') ?></p>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.smtp_host') ?></label>
        <input type="text" name="smtp_host" class="form-control" value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
      </div>
      <div class="form-group">
        <label><?= __('settings.smtp_port') ?></label>
        <input type="number" min="1" max="65535" name="smtp_port" class="form-control" value="<?= e((string) ($settings['smtp_port'] ?? '')) ?>" placeholder="587">
      </div>
      <div class="form-group">
        <label><?= __('settings.smtp_encryption') ?></label>
        <select name="smtp_encryption" class="form-control">
          <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (587)</option>
          <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL/TLS (465)</option>
          <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>><?= __('settings.smtp_encryption_none') ?></option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.smtp_username') ?></label>
        <input type="text" name="smtp_username" class="form-control" value="<?= e($settings['smtp_username'] ?? '') ?>" autocomplete="off">
      </div>
      <div class="form-group">
        <label><?= __('settings.smtp_password') ?></label>
        <input type="password" name="smtp_password" class="form-control" value="<?= e($settings['smtp_password'] ?? '') ?>" autocomplete="new-password">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('settings.smtp_from_email') ?></label>
        <input type="email" name="smtp_from_email" class="form-control" value="<?= e($settings['smtp_from_email'] ?? '') ?>" placeholder="<?= e($settings['org_email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label><?= __('settings.smtp_from_name') ?></label>
        <input type="text" name="smtp_from_name" class="form-control" value="<?= e($settings['smtp_from_name'] ?? '') ?>" placeholder="<?= e($settings['org_name'] ?? '') ?>">
      </div>
    </div>
    <button type="submit" form="test-email-form" class="btn btn-secondary"><?= __('settings.test_email_button') ?></button>
  </div>

  <button type="submit" class="btn btn-primary btn-lg"><?= __('common.save') ?></button>
</form>

<?php // These forms live outside the main settings <form> above (HTML forms can't nest);
      // their fields point back in via the form="..." attribute on each input/button. ?>
<form id="social-add-form" method="post" action="<?= base_url('admin/settings/social-links') ?>" style="display:none;"><?= csrf_field() ?></form>
<?php foreach ($socialLinks as $link): ?>
  <form id="social-form-<?= (int) $link['id'] ?>" method="post" action="<?= base_url('admin/settings/social-links/' . $link['id']) ?>" style="display:none;"><?= csrf_field() ?></form>
  <form id="social-delete-<?= (int) $link['id'] ?>" method="post" action="<?= base_url('admin/settings/social-links/' . $link['id'] . '/delete') ?>" style="display:none;"><?= csrf_field() ?></form>
<?php endforeach; ?>
<form id="gallery-add-form" method="post" action="<?= base_url('admin/settings/gallery') ?>" enctype="multipart/form-data" style="display:none;"><?= csrf_field() ?></form>
<?php foreach ($galleryPhotos as $photo): ?>
  <form id="gallery-delete-<?= (int) $photo['id'] ?>" method="post" action="<?= base_url('admin/settings/gallery/' . $photo['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" style="display:none;"><?= csrf_field() ?></form>
<?php endforeach; ?>
<form id="test-email-form" method="post" action="<?= base_url('admin/settings/test-email') ?>" style="display:none;"><?= csrf_field() ?></form>
