<?php
/**
 * Text fields of a shop listing, shared by the "add" form and each card's "edit details" form.
 * @var array|null $ad      the shop being edited, or null when adding
 * @var array      $vendors from Vendor::options()
 */
$ad = $ad ?? [];
$v = static fn (string $key) => e((string) ($ad[$key] ?? ''));
?>
<div class="form-row">
  <div class="form-group">
    <label><?= __('settings.ads_business_name') ?></label>
    <input type="text" name="business_name" class="form-control" value="<?= $v('business_name') ?>" required>
  </div>
  <div class="form-group">
    <label><?= __('settings.ads_link_url') ?></label>
    <input type="url" name="link_url" class="form-control" value="<?= $v('link_url') ?>" placeholder="https://...">
  </div>
</div>
<p class="form-hint mb-4"><?= __('settings.ads_link_url_hint') ?></p>
<div class="form-group">
  <label><?= __('settings.ads_description') ?></label>
  <textarea name="description" class="form-control" rows="2"><?= $v('description') ?></textarea>
</div>
<div class="form-row">
  <div class="form-group">
    <label><?= __('ads.field_badge') ?></label>
    <select name="badge" class="form-control">
      <option value=""><?= __('ads.badge_none') ?></option>
      <?php foreach (\App\Models\Advertisement::BADGES as $badge): ?>
        <option value="<?= $badge ?>"<?= ($ad['badge'] ?? '') === $badge ? ' selected' : '' ?>><?= __('ads.badge_' . $badge) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label><?= __('ads.field_phone') ?></label>
    <input type="tel" name="phone" class="form-control" value="<?= $v('phone') ?>" placeholder="+1 407 555 0123">
    <p class="form-hint"><?= __('ads.field_phone_hint') ?></p>
  </div>
</div>
<div class="form-row">
  <div class="form-group">
    <label><?= __('ads.field_map') ?></label>
    <input type="url" name="map_url" class="form-control" value="<?= $v('map_url') ?>" placeholder="https://maps.app.goo.gl/...">
    <p class="form-hint"><?= __('ads.field_map_hint') ?></p>
  </div>
  <div class="form-group">
    <label><?= __('ads.field_line') ?></label>
    <input type="url" name="line_url" class="form-control" value="<?= $v('line_url') ?>" placeholder="https://line.me/ti/p/...">
    <p class="form-hint"><?= __('ads.field_line_hint') ?></p>
  </div>
</div>
<div class="form-group">
  <label><?= __('ads.field_vendor') ?></label>
  <select name="vendor_id" class="form-control">
    <option value="0"><?= __('ads.vendor_none') ?></option>
    <?php foreach ($vendors as $vendor): ?>
      <option value="<?= (int) $vendor['id'] ?>"<?= (int) ($ad['vendor_id'] ?? 0) === (int) $vendor['id'] ? ' selected' : '' ?>><?= e($vendor['name']) ?> · <?= e($vendor['phone']) ?></option>
    <?php endforeach; ?>
  </select>
  <p class="form-hint"><?= __('ads.vendor_hint') ?></p>
</div>
