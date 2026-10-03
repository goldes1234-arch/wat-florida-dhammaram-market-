<?php
/** @var array $photos */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $albums */
/** @var int $eventId */
$locale = \App\Core\Lang::locale();
?>
<div class="page-header">
  <h1><?= __('public.gallery_title') ?></h1>
  <a href="<?= base_url('') ?>" class="btn btn-secondary">&larr; <?= __('public.gallery_back') ?></a>
</div>

<?php if (count($albums) > 0): ?>
  <div class="album-chips mb-4">
    <a href="<?= base_url('gallery') ?>" class="chip<?= $eventId === 0 ? ' is-active' : '' ?>"><?= __('public.gallery_all_albums') ?></a>
    <?php foreach ($albums as $album): ?>
      <?php $albumName = $locale === 'en' ? ($album['name_en'] ?: $album['name_th']) : $album['name_th']; ?>
      <a href="<?= base_url('gallery?event=' . (int) $album['id']) ?>" class="chip<?= $eventId === (int) $album['id'] ? ' is-active' : '' ?>"><?= e($albumName) ?> <small>(<?= (int) $album['photo_count'] ?>)</small></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$photos): ?>
  <p class="text-muted"><?= __('public.gallery_empty') ?></p>
<?php else: ?>
  <div class="photo-grid">
    <?php foreach ($photos as $photo): ?>
      <a href="#" class="photo-grid-item" data-lightbox-src="<?= e(upload_url($photo['image_path'])) ?>"
         data-lightbox-group="gallery-page" data-caption="<?= e($photo['caption'] ?? '') ?>" data-thumb="<?= e(upload_url($photo['thumb_path'] ?: $photo['image_path'])) ?>">
        <img src="<?= e(upload_url($photo['thumb_path'] ?: $photo['image_path'])) ?>" alt="<?= e($photo['caption'] ?: __('public.gallery_title')) ?>" loading="lazy">
        <?php if (!empty($photo['caption'])): ?><span class="photo-grid-caption"><?= e($photo['caption']) ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?= partial('pagination', ['page' => $page, 'totalPages' => $totalPages, 'path' => 'gallery', 'query' => $eventId ? ['event' => $eventId] : []]) ?>
<?php endif; ?>
