<?php
/** @var array $advertisements */
/** @var array $pendingAdvertisements */
?>
<div class="page-header">
  <h1><?= __('nav.advertisements') ?></h1>
</div>

<?php if ($pendingAdvertisements): ?>
  <div class="card mb-6">
    <div class="card-header"><h3>⏳ <?= __('settings.ads_pending_section') ?> (<?= count($pendingAdvertisements) ?>)</h3></div>
    <p class="form-hint mb-4"><?= __('settings.ads_pending_hint') ?></p>
    <div class="grid grid-cols-4">
      <?php foreach ($pendingAdvertisements as $ad): ?>
        <div class="card" style="padding:10px;border-color:#EAB308;">
          <img src="<?= upload_url($ad['thumb_path'] ?: $ad['image_path']) ?>" class="thumb-sm mb-2" style="width:100%;height:120px;object-fit:contain;background:var(--color-slate-light);" alt="">
          <?php if (count($ad['images']) > 1): ?>
            <div class="ad-admin-thumbs mb-2">
              <?php foreach (array_slice($ad['images'], 1) as $im): ?><img src="<?= upload_url($im['thumb']) ?>" alt=""><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="text-sm mb-2"><strong><?= e($ad['business_name']) ?></strong></div>
          <?php if (!empty($ad['description'])): ?><div class="text-sm text-muted mb-2"><?= e($ad['description']) ?></div><?php endif; ?>
          <?php if (!empty($ad['contact_name']) || !empty($ad['contact_phone'])): ?>
            <div class="text-sm text-muted mb-2"><?= __('settings.ads_pending_contact') ?>: <?= e($ad['contact_name']) ?> <?= e($ad['contact_phone']) ?></div>
          <?php endif; ?>
          <?php if (!empty($ad['link_url'])): ?><div class="text-sm text-muted mb-2" style="word-break:break-all;"><?= e($ad['link_url']) ?></div><?php endif; ?>
          <div style="display:flex;gap:8px;">
            <form method="post" action="<?= base_url('admin/advertisements/' . $ad['id'] . '/approve') ?>" style="margin:0;flex:1;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-primary btn-sm" style="width:100%;"><?= __('settings.ads_approve_button') ?></button>
            </form>
            <form method="post" action="<?= base_url('admin/advertisements/' . $ad['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" style="margin:0;flex:1;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-sm" style="width:100%;"><?= __('common.delete') ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="card mb-6">
  <div class="card-header"><h3><?= __('settings.ads_add_button') ?></h3></div>
  <p class="form-hint mb-4"><?= __('settings.ads_hint') ?></p>
  <form method="post" action="<?= base_url('admin/advertisements') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label><?= __('settings.ads_business_name') ?></label>
        <input type="text" name="business_name" class="form-control" required>
      </div>
      <div class="form-group">
        <label><?= __('settings.ads_link_url') ?></label>
        <input type="url" name="link_url" class="form-control" placeholder="https://...">
      </div>
    </div>
    <p class="form-hint mb-4"><?= __('settings.ads_link_url_hint') ?></p>
    <div class="form-group">
      <label><?= __('settings.ads_description') ?></label>
      <textarea name="description" class="form-control" rows="2"></textarea>
    </div>
    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label><?= __('settings.ads_image') ?></label>
        <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required data-shrink-max="1800">
      </div>
      <div class="form-group" style="flex:0 0 auto;">
        <button type="submit" class="btn btn-primary"><?= __('settings.ads_add_button') ?></button>
      </div>
    </div>
    <p class="form-hint"><?= __('settings.ads_size_hint') ?></p>
  </form>
</div>

<?php if (!$advertisements): ?>
  <div class="empty-state"><div class="empty-icon">🏪</div><?= __('settings.ads_none') ?></div>
<?php else: ?>
  <div class="grid grid-cols-4">
    <?php foreach ($advertisements as $ad): ?>
      <div class="card" style="padding:10px;">
        <img src="<?= upload_url($ad['thumb_path'] ?: $ad['image_path']) ?>" class="thumb-sm mb-2" style="width:100%;height:120px;object-fit:contain;background:var(--color-slate-light);" alt="">
        <div class="text-sm mb-2"><strong><?= e($ad['business_name']) ?></strong></div>
        <?php if (!empty($ad['description'])): ?><div class="text-sm text-muted mb-2"><?= e($ad['description']) ?></div><?php endif; ?>
        <?php if (!empty($ad['link_url'])): ?><div class="text-sm text-muted mb-2" style="word-break:break-all;"><?= e($ad['link_url']) ?></div><?php endif; ?>
        <?php $extras = array_slice($ad['images'], 1); $room = \App\Models\Advertisement::MAX_IMAGES - count($ad['images']); ?>
        <div class="text-sm text-muted mb-2"><?= __('ads.photos_count', ['count' => (string) count($ad['images']), 'max' => (string) \App\Models\Advertisement::MAX_IMAGES]) ?></div>
        <?php if ($extras): ?>
          <div class="ad-admin-thumbs mb-2">
            <?php foreach ($extras as $im): ?>
              <form method="post" action="<?= base_url('admin/advertisements/' . $ad['id'] . '/images/' . $im['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>">
                <?= csrf_field() ?>
                <img src="<?= upload_url($im['thumb']) ?>" alt="">
                <button type="submit" class="ad-admin-thumb-remove" aria-label="<?= e(__('common.delete')) ?>">×</button>
              </form>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($room > 0): ?>
          <form method="post" action="<?= base_url('admin/advertisements/' . $ad['id'] . '/images') ?>" enctype="multipart/form-data" class="mb-2" style="display:flex;flex-direction:column;gap:6px;">
            <?= csrf_field() ?>
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required data-shrink-max="1800" class="form-control" style="min-width:0;font-size:12px;padding:4px;">
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;"><?= __('ads.add_photos') ?></button>
          </form>
        <?php endif; ?>
        <form method="post" action="<?= base_url('admin/advertisements/' . $ad['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger btn-sm" style="width:100%;"><?= __('common.delete') ?></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
