<?php
/** @var array $advertisements */
?>
<div class="page-header">
  <h1><?= __('nav.advertisements') ?></h1>
</div>

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
    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label><?= __('settings.ads_image') ?></label>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
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
        <img src="<?= upload_url($ad['image_path']) ?>" class="thumb-sm mb-2" style="width:100%;height:120px;object-fit:contain;background:var(--color-slate-light);" alt="">
        <div class="text-sm mb-2"><strong><?= e($ad['business_name']) ?></strong></div>
        <?php if (!empty($ad['link_url'])): ?><div class="text-sm text-muted mb-2" style="word-break:break-all;"><?= e($ad['link_url']) ?></div><?php endif; ?>
        <form method="post" action="<?= base_url('admin/advertisements/' . $ad['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger btn-sm" style="width:100%;"><?= __('common.delete') ?></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
