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
        $images = $ad['images'] ?? [['path' => $ad['image_path'], 'thumb' => $ad['image_path']]];
        $photoCount = count($images);
        $cover = $images[0];
        $ratio = upload_aspect_ratio($cover['path']);
        // Photos close to the card's 4:3 shape fill it edge to edge; very tall/wide images
        // (logos, banners) are shown whole on a blurred backdrop instead of being cropped.
        $fits = $ratio === null || ($ratio >= 1.1 && $ratio <= 2.0);
        $imgUrl = upload_url($cover['thumb']);
        // A shop with several photos opens them in the lightbox (its link, if any, becomes a button on the card);
        // a single-photo shop keeps the old behaviour: the card is the link, or opens its one photo.
        $opensPhotos = $photoCount > 1 || !$hasLink;
        $group = 'ad-' . (int) $ad['id'];
        if ($opensPhotos) {
            $attrs = 'role="button" tabindex="0" data-lightbox-src="' . e(upload_url($cover['path'])) . '" data-thumb="' . e($imgUrl)
                . '" data-caption="' . e($ad['business_name']) . '"' . ($photoCount > 1 ? ' data-lightbox-group="' . $group . '"' : '');
        } else {
            $attrs = 'href="' . e($ad['link_url']) . '" target="_blank" rel="noopener"';
        }
        $tag = $opensPhotos ? 'div' : 'a';
        ?>
        <<?= $tag ?> <?= $attrs ?> class="ad-card">
          <span class="ad-card-media<?= $fits ? ' is-cover' : '' ?>" style="background-image:url('<?= e($imgUrl) ?>')">
            <img src="<?= e($imgUrl) ?>" alt="<?= e($ad['business_name']) ?>" loading="lazy">
            <?php if ($photoCount > 1): ?><span class="ad-card-count">🖼 <?= $photoCount ?></span><?php endif; ?>
          </span>
          <span class="ad-card-name"><?= e($ad['business_name']) ?></span>
          <?php if (!empty($ad['description'])): ?>
            <span class="ad-card-desc"><?= e($ad['description']) ?></span>
          <?php endif; ?>
          <span class="ad-card-cta">
            <?php if ($opensPhotos): ?>
              🔍 <?= $photoCount > 1 ? __('public.ad_view_photos', ['count' => (string) $photoCount]) : __('public.ad_view_photo') ?>
              <?php if ($hasLink): ?>
                <a href="<?= e($ad['link_url']) ?>" target="_blank" rel="noopener" class="ad-card-visit"><?= __('public.ad_visit') ?> &rarr;</a>
              <?php endif; ?>
            <?php else: ?>
              <?= __('public.ad_visit') ?> &rarr;
            <?php endif; ?>
          </span>
          <?php if ($photoCount > 1): ?>
            <?php foreach (array_slice($images, 1) as $extra): ?>
              <span hidden data-lightbox-src="<?= e(upload_url($extra['path'])) ?>" data-thumb="<?= e(upload_url($extra['thumb'])) ?>" data-caption="<?= e($ad['business_name']) ?>" data-lightbox-group="<?= $group ?>"></span>
            <?php endforeach; ?>
          <?php endif; ?>
        </<?= $tag ?>>
      <?php endforeach; ?>
    </div>
    <?php if ($adsCarousel): ?>
      <button type="button" class="ads-nav ads-nav-next" aria-label="<?= e(__('public.ads_next')) ?>">&rsaquo;</button>
    <?php endif; ?>
  </div>
<?php endif; ?>
