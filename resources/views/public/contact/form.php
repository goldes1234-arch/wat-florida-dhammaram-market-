<div class="container-narrow" style="padding:0;">
  <div class="page-header">
    <h1><?= __('contact.title') ?></h1>
  </div>
  <p class="text-muted mb-6"><?= __('contact.subtitle') ?></p>

  <?php if (!empty($settings['org_email']) || !empty($settings['org_phone']) || !empty($settings['line_oa_id'])): ?>
    <div class="contact-channels mb-6">
      <?php if (!empty($settings['org_email'])): ?>
        <a href="mailto:<?= e($settings['org_email']) ?>" class="contact-channel">
          <span class="contact-channel-icon"><?= icon('mail') ?></span>
          <span class="contact-channel-label"><?= __('contact.email_us') ?></span>
          <span class="contact-channel-value"><?= e($settings['org_email']) ?></span>
        </a>
      <?php endif; ?>
      <?php if (!empty($settings['org_phone'])): ?>
        <a href="tel:<?= e($settings['org_phone']) ?>" class="contact-channel">
          <span class="contact-channel-icon"><?= icon('phone') ?></span>
          <span class="contact-channel-label"><?= __('contact.call_us') ?></span>
          <span class="contact-channel-value"><?= e($settings['org_phone']) ?></span>
        </a>
      <?php endif; ?>
      <?php if (!empty($settings['line_oa_id'])): ?>
        <a href="https://line.me/R/ti/p/<?= rawurlencode($settings['line_oa_id']) ?>" target="_blank" rel="noopener noreferrer" class="contact-channel">
          <span class="contact-channel-icon"><?= icon('message-circle') ?></span>
          <span class="contact-channel-label"><?= __('contact.line_us') ?></span>
          <span class="contact-channel-value">LINE: <?= e($settings['line_oa_id']) ?></span>
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h3><?= __('contact.form_title') ?></h3></div>
    <form method="post" action="<?= base_url('contact') ?>">
      <?= csrf_field() ?>
      <div class="hp-field"><label>Website</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

      <div class="form-group">
        <label><?= __('contact.form_name') ?></label>
        <input type="text" name="name" class="form-control" value="<?= e(old('name')) ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('contact.form_email') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
          <input type="email" name="email" class="form-control" value="<?= e(old('email')) ?>">
        </div>
        <div class="form-group">
          <label><?= __('contact.form_phone') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
          <input type="text" name="phone" class="form-control" value="<?= e(old('phone')) ?>">
        </div>
      </div>
      <p class="form-hint mb-4"><?= __('contact.missing_contact') ?></p>
      <div class="form-group">
        <label><?= __('contact.form_message') ?></label>
        <textarea name="message" class="form-control" rows="5" required><?= e(old('message')) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg btn-block"><?= __('contact.form_submit') ?></button>
    </form>
  </div>
</div>
