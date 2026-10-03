<?php
/** @var array $advertisements */

// More than a row's worth turns the grid into an auto-advancing carousel.
$adsCarousel = count($advertisements) > 4;
?>
<?php if ($advertisements): ?>
  <div class="ads-section-header">
    <h2 class="section-title" style="margin-bottom:0;"><?= __('public.ads_section_title') ?></h2>
    <a href="<?= base_url('advertise') ?>" class="ads-list-shop-link"><?= __('ads.list_your_shop_prompt') ?> <?= __('ads.list_your_shop_link') ?></a>
  </div>
  <div class="ads-wrap<?= $adsCarousel ? ' is-carousel' : '' ?>" <?= $adsCarousel ? 'id="adsCarousel"' : '' ?>>
    <?php if ($adsCarousel): ?>
      <button type="button" class="ads-nav ads-nav-prev" aria-label="<?= e(__('public.ads_prev')) ?>">&lsaquo;</button>
    <?php endif; ?>
    <div class="ads-grid">
      <?php foreach ($advertisements as $ad): ?>
        <?php
        $hasLink = !empty($ad['link_url']);
        $ratio = upload_aspect_ratio($ad['image_path']);
        // Photos close to the card's 4:3 shape fill it edge to edge; very tall/wide images
        // (logos, banners) are shown whole on a blurred backdrop instead of being cropped.
        $fits = $ratio === null || ($ratio >= 1.1 && $ratio <= 2.0);
        $imgUrl = upload_url($ad['image_path']);
        $attrs = $hasLink
            ? 'href="' . e($ad['link_url']) . '" target="_blank" rel="noopener"'
            : 'role="button" tabindex="0" data-lightbox-src="' . e($imgUrl) . '"';
        $tag = $hasLink ? 'a' : 'div';
        ?>
        <<?= $tag ?> <?= $attrs ?> class="ad-card">
          <span class="ad-card-media<?= $fits ? ' is-cover' : '' ?>" style="background-image:url('<?= e($imgUrl) ?>')">
            <img src="<?= e($imgUrl) ?>" alt="<?= e($ad['business_name']) ?>" loading="lazy">
          </span>
          <span class="ad-card-name"><?= e($ad['business_name']) ?></span>
          <?php if (!empty($ad['description'])): ?>
            <span class="ad-card-desc"><?= e($ad['description']) ?></span>
          <?php endif; ?>
          <span class="ad-card-cta"><?= $hasLink ? __('public.ad_visit') . ' &rarr;' : '🔍 ' . __('public.ad_view_photo') ?></span>
        </<?= $tag ?>>
      <?php endforeach; ?>
    </div>
    <?php if ($adsCarousel): ?>
      <button type="button" class="ads-nav ads-nav-next" aria-label="<?= e(__('public.ads_next')) ?>">&rsaquo;</button>
    <?php endif; ?>
  </div>
<?php endif; ?>
