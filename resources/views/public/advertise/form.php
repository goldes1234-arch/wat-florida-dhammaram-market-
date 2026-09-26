<div class="container-narrow" style="padding:0;">
  <div class="page-header">
    <h1><?= __('ads.public_form_title') ?></h1>
  </div>
  <p class="text-muted mb-6"><?= __('ads.public_form_subtitle') ?></p>

  <div class="card">
    <form method="post" action="<?= base_url('advertise') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="hp-field"><label>Website</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

      <div class="form-group">
        <label><?= __('settings.ads_business_name') ?></label>
        <input type="text" name="business_name" class="form-control" value="<?= e(old('business_name')) ?>" required>
      </div>

      <div class="form-group">
        <label><?= __('ads.public_form_description') ?></label>
        <textarea name="description" class="form-control" rows="4" required><?= e(old('description')) ?></textarea>
        <p class="form-hint"><?= __('ads.public_form_description_hint') ?></p>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label><?= __('ads.public_form_contact_name') ?></label>
          <input type="text" name="contact_name" class="form-control" value="<?= e(old('contact_name')) ?>" required>
        </div>
        <div class="form-group">
          <label><?= __('ads.public_form_contact_phone') ?></label>
          <input type="text" name="contact_phone" class="form-control" value="<?= e(old('contact_phone')) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label><?= __('settings.ads_link_url') ?></label>
        <input type="url" name="link_url" class="form-control" value="<?= e(old('link_url')) ?>" placeholder="https://...">
        <p class="form-hint"><?= __('settings.ads_link_url_hint') ?></p>
      </div>

      <div class="form-group">
        <label><?= __('settings.ads_image') ?></label>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
        <p class="form-hint"><?= __('settings.ads_size_hint') ?></p>
      </div>

      <button type="submit" class="btn btn-primary btn-lg btn-block"><?= __('ads.public_form_submit') ?></button>
    </form>
  </div>
</div>
