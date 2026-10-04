<?php
/** @var array $advertisements */
/** @var bool $onEventPage true on an event page, where "selling here" can say "this event" */

// More than a row's worth turns the grid into an auto-advancing carousel.
$adsCarousel = count($advertisements) > 4;
$onEventPage = !empty($onEventPage);
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
        $images = $ad['images'] ?? [['path' => $ad['image_path'], 'thumb' => $ad['image_path']]];
        $photoCount = count($images);
        $cover = $images[0];
        $group = 'ad-' . (int) $ad['id'];
        $badge = in_array($ad['badge'] ?? null, \App\Models\Advertisement::BADGES, true) ? $ad['badge'] : null;
        $dial = preg_replace('/[^0-9+]/', '', (string) ($ad['phone'] ?? ''));
        $actions = array_filter([
            $dial !== '' ? ['call', 'tel:' . $dial, '📞', __('public.ad_call'), false] : null,
            !empty($ad['map_url']) ? ['map', $ad['map_url'], '📍', __('public.ad_map'), true] : null,
            !empty($ad['line_url']) ? ['line', $ad['line_url'], '💬', __('public.ad_line'), true] : null,
            !empty($ad['link_url']) ? ['web', $ad['link_url'], '🔗', __('public.ad_website'), true] : null,
        ]);
        $selling = $ad['selling'] ?? [];
        ?>
        <div role="button" tabindex="0" class="ad-card<?= $badge === 'featured' ? ' is-featured' : '' ?>"
             data-lightbox-src="<?= e(upload_url($cover['path'])) ?>" data-thumb="<?= e(upload_url($cover['thumb'])) ?>"
             data-caption="<?= e($ad['business_name']) ?>"<?= $photoCount > 1 ? ' data-lightbox-group="' . $group . '"' : '' ?>>
          <div class="ad-card-media"<?= $photoCount > 1 ? ' data-ad-slides="' . $photoCount . '"' : '' ?>>
            <?php foreach ($images as $i => $im): ?>
              <?php
              // Photos close to the card's 4:3 shape fill it edge to edge; very tall/wide images
              // (logos, banners) are shown whole on a blurred backdrop instead of being cropped.
              $ratio = upload_aspect_ratio($im['thumb']);
              $fits = $ratio === null || ($ratio >= 1.1 && $ratio <= 2.0);
              $thumbUrl = upload_url($im['thumb']);
              ?>
              <span class="ad-slide<?= $fits ? ' is-cover' : '' ?><?= $i === 0 ? ' is-active' : '' ?>" style="background-image:url('<?= e($thumbUrl) ?>')">
                <img src="<?= e($thumbUrl) ?>" alt="<?= $i === 0 ? e($ad['business_name']) : '' ?>"<?= $i === 0 ? '' : ' loading="lazy"' ?>>
              </span>
            <?php endforeach; ?>
            <?php if ($badge): ?><span class="ad-badge ad-badge-<?= $badge ?>"><?= $badge === 'featured' ? '★ ' : '' ?><?= __('ads.badge_' . $badge) ?></span><?php endif; ?>
            <?php if ($photoCount > 1): ?>
              <span class="ad-card-count">🖼 <?= $photoCount ?></span>
              <span class="ad-dots" aria-hidden="true"><?php for ($i = 0; $i < $photoCount; $i++): ?><i<?= $i === 0 ? ' class="is-active"' : '' ?>></i><?php endfor; ?></span>
            <?php endif; ?>
          </div>
          <span class="ad-card-name"><?= e($ad['business_name']) ?></span>
          <?php if ($selling): ?>
            <?php
            $first = $selling[0];
            $lots = implode(', ', $first['lots']);
            $more = count($selling) - 1;
            ?>
            <span class="ad-card-selling">🛖
              <?php if ($onEventPage): ?>
                <?= e(__('public.ad_selling_here', ['lots' => $lots])) ?>
              <?php else: ?>
                <?= e(__('public.ad_selling_event', ['event' => $first['name'], 'lots' => $lots])) ?><?= $more > 0 ? ' ' . e(__('public.ad_selling_more', ['count' => (string) $more])) : '' ?>
              <?php endif; ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($ad['description'])): ?>
            <span class="ad-card-desc-wrap">
              <span class="ad-card-desc"><?= e($ad['description']) ?></span>
              <button type="button" class="ad-more" hidden data-more="<?= e(__('public.ad_read_more')) ?>" data-less="<?= e(__('public.ad_read_less')) ?>"><?= __('public.ad_read_more') ?></button>
            </span>
          <?php endif; ?>
          <span class="ad-card-cta">🔍 <?= $photoCount > 1 ? __('public.ad_view_photos', ['count' => (string) $photoCount]) : __('public.ad_view_photo') ?></span>
          <?php if ($actions): ?>
            <span class="ad-actions">
              <?php foreach ($actions as [$kind, $href, $icon, $label, $external]): ?>
                <a href="<?= e($href) ?>" class="ad-action ad-action-<?= $kind ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?>><?= $icon ?> <?= e($label) ?></a>
              <?php endforeach; ?>
            </span>
          <?php endif; ?>
          <?php foreach (array_slice($images, 1) as $extra): ?>
            <span hidden data-lightbox-src="<?= e(upload_url($extra['path'])) ?>" data-thumb="<?= e(upload_url($extra['thumb'])) ?>" data-caption="<?= e($ad['business_name']) ?>" data-lightbox-group="<?= $group ?>"></span>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($adsCarousel): ?>
      <button type="button" class="ads-nav ads-nav-next" aria-label="<?= e(__('public.ads_next')) ?>">&rsaquo;</button>
    <?php endif; ?>
  </div>
<?php endif; ?>
