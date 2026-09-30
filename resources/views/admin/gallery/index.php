<?php
/** @var array $galleryPhotos */
?>
<div class="page-header">
  <h1><?= __('nav.gallery') ?></h1>
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
          <form method="post" action="<?= base_url('admin/gallery/' . $photo['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger btn-sm" style="width:100%;"><?= __('common.delete') ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= base_url('admin/gallery') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label><?= __('lot.photo') ?></label>
        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
      </div>
      <div class="form-group">
        <label><?= __('settings.gallery_caption_placeholder') ?></label>
        <input type="text" name="caption" class="form-control">
      </div>
      <div class="form-group" style="flex:0 0 auto;">
        <button type="submit" class="btn btn-primary"><?= __('settings.gallery_add_button') ?></button>
      </div>
    </div>
    <p class="form-hint"><?= __('settings.gallery_size_hint') ?></p>
  </form>
</div>
