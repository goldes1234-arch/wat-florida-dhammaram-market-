<?php
/** @var array $advertisements */
?>
<?php if ($advertisements): ?>
  <div class="ads-section-header">
    <h2 class="section-title" style="margin-bottom:0;"><?= __('public.ads_section_title') ?></h2>
    <a href="<?= base_url('advertise') ?>" class="ads-list-shop-link"><?= __('ads.list_your_shop_prompt') ?> <?= __('ads.list_your_shop_link') ?></a>
  </div>
  <div class="ads-grid">
    <?php foreach ($advertisements as $ad): ?>
      <?php $tag = $ad['link_url'] ? 'a' : 'div'; ?>
      <<?= $tag ?> <?= $ad['link_url'] ? 'href="' . e($ad['link_url']) . '" target="_blank" rel="noopener"' : '' ?> class="ad-card">
        <span class="ad-card-media" style="background-image:url('<?= upload_url($ad['image_path']) ?>')">
          <img src="<?= upload_url($ad['image_path']) ?>" alt="<?= e($ad['business_name']) ?>" loading="lazy">
        </span>
        <span class="ad-card-name"><?= e($ad['business_name']) ?></span>
        <?php if (!empty($ad['description'])): ?>
          <span class="ad-card-desc"><?= e($ad['description']) ?></span>
        <?php endif; ?>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
