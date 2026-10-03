<?php
/** @var array $photos */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
?>
<div class="page-header">
  <h1><?= __('public.gallery_title') ?></h1>
  <a href="<?= base_url('') ?>" class="btn btn-secondary">&larr; <?= __('public.gallery_back') ?></a>
</div>

<?php if (!$photos): ?>
  <p class="text-muted"><?= __('public.gallery_empty') ?></p>
<?php else: ?>
  <div class="photo-grid">
    <?php foreach ($photos as $photo): ?>
      <a href="#" class="photo-grid-item" data-lightbox-src="<?= e(upload_url($photo['image_path'])) ?>"
         data-lightbox-group="gallery-page" data-caption="<?= e($photo['caption'] ?? '') ?>">
        <img src="<?= e(upload_url($photo['image_path'])) ?>" alt="<?= e($photo['caption'] ?: __('public.gallery_title')) ?>" loading="lazy">
        <?php if (!empty($photo['caption'])): ?><span class="photo-grid-caption"><?= e($photo['caption']) ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?= partial('pagination', ['page' => $page, 'totalPages' => $totalPages, 'path' => 'gallery']) ?>
<?php endif; ?>
