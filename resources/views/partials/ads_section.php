<?php
/** @var array $advertisements */
?>
<?php if ($advertisements): ?>
  <h2 class="section-title"><?= __('public.ads_section_title') ?></h2>
  <div class="ads-grid">
    <?php foreach ($advertisements as $ad): ?>
      <?php $tag = $ad['link_url'] ? 'a' : 'div'; ?>
      <<?= $tag ?> <?= $ad['link_url'] ? 'href="' . e($ad['link_url']) . '" target="_blank" rel="noopener"' : '' ?> class="ad-card">
        <span class="ad-card-media" style="background-image:url('<?= upload_url($ad['image_path']) ?>')">
          <img src="<?= upload_url($ad['image_path']) ?>" alt="<?= e($ad['business_name']) ?>" loading="lazy">
        </span>
        <span class="ad-card-name"><?= e($ad['business_name']) ?></span>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
