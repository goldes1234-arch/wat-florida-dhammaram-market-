<?php
use App\Services\EventStatusService;
$heroBannerUrl = !empty($settings['hero_banner_image']) ? upload_url($settings['hero_banner_image']) : null;
?>

<div class="hero-band<?= $heroBannerUrl ? ' has-banner' : '' ?>"
     style="margin:-36px -20px 32px;padding-left:20px;padding-right:20px;<?= $heroBannerUrl ? "background-image:url('" . $heroBannerUrl . "');" : '' ?>">
  <div class="container" style="padding:0;">
    <span class="hero-eyebrow"><?= icon('sparkle') ?> <?= e(__('common.app_name')) ?></span>
    <h1><?= __('public.upcoming_events') ?></h1>
    <p><?= __('public.tagline') ?></p>

    <div class="hero-stats">
      <div class="hero-stat">
        <span class="hero-stat-value"><?= (int) $statEventsCount ?></span>
        <span class="hero-stat-label"><?= __('public.stat_events') ?></span>
      </div>
      <div class="hero-stat">
        <span class="hero-stat-value"><?= (int) $statAvailableLots ?></span>
        <span class="hero-stat-label"><?= __('public.stat_available_lots') ?></span>
      </div>
      <?php if ($statBookedCount > 0): ?>
        <div class="hero-stat">
          <span class="hero-stat-value"><?= (int) $statBookedCount ?></span>
          <span class="hero-stat-label"><?= __('public.stat_booked') ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<h2 class="section-title"><?= __('public.how_it_works_title') ?></h2>
<div class="how-it-works">
  <div class="how-it-works-step">
    <span class="step-number">1</span>
    <span class="step-icon-badge"><?= icon('store') ?></span>
    <h4><?= __('public.how_it_works_step1_title') ?></h4>
    <p><?= __('public.how_it_works_step1_desc') ?></p>
  </div>
  <div class="how-it-works-step">
    <span class="step-number">2</span>
    <span class="step-icon-badge"><?= icon('ticket') ?></span>
    <h4><?= __('public.how_it_works_step2_title') ?></h4>
    <p><?= __('public.how_it_works_step2_desc') ?></p>
  </div>
  <div class="how-it-works-step">
    <span class="step-number">3</span>
    <span class="step-icon-badge"><?= icon('credit-card') ?></span>
    <h4><?= __('public.how_it_works_step3_title') ?></h4>
    <p><?= __('public.how_it_works_step3_desc') ?></p>
  </div>
</div>

<?php if ($featuredEvent): ?>
  <?php
  $closesAt = strtotime($featuredEvent['booking_close_at']);
  $daysLeft = (int) ceil(($closesAt - time()) / 86400);
  if ($daysLeft <= 0) {
      $closingText = __('public.featured_closing_today');
  } elseif ($daysLeft === 1) {
      $closingText = __('public.featured_closing_tomorrow');
  } else {
      $closingText = __('public.featured_closing_days', ['days' => $daysLeft]);
  }
  $flc = $lotCounts[$featuredEvent['id']] ?? null;
  ?>
  <a href="<?= base_url('events/' . $featuredEvent['slug']) ?>" class="featured-event">
    <div class="featured-event-media<?= !empty($featuredEvent['banner_image']) ? ' has-image' : '' ?>"
         <?= !empty($featuredEvent['banner_image']) ? 'style="background-image:url(\'' . upload_url($featuredEvent['banner_image']) . '\')"' : '' ?>>
      <?php if (!empty($featuredEvent['banner_image'])): ?>
        <img src="<?= upload_url($featuredEvent['banner_image']) ?>" alt="<?= e($featuredEvent['name_th']) ?>">
      <?php else: ?>
        <div class="media-placeholder"><?= icon('store') ?> <?= e($featuredEvent['name_th']) ?></div>
      <?php endif; ?>
    </div>
    <div class="featured-event-body">
      <span class="featured-badge"><?= icon('clock') ?> <?= __('public.featured_badge') ?></span>
      <h3><?= e($featuredEvent['name_th']) ?></h3>
      <div class="featured-meta">
        <span><?= icon('calendar') ?> <?= e(date('d/m/Y', strtotime($featuredEvent['start_date']))) ?></span>
        <?php if (!empty($featuredEvent['venue_name'])): ?><span><?= icon('map-pin') ?> <?= e($featuredEvent['venue_name']) ?></span><?php endif; ?>
        <?php if ($flc && $flc['total'] > 0): ?><span><?= icon('ticket') ?> <?= __('public.lots_left', ['count' => $flc['available']]) ?></span><?php endif; ?>
      </div>
      <div class="featured-closing"><?= $closingText ?></div>
      <div><span class="btn btn-primary"><?= __('public.featured_cta') ?></span></div>
    </div>
  </a>
<?php endif; ?>

<?php if (!$gridEvents): ?>
  <?php if (!$featuredEvent): ?>
    <div class="empty-state">
      <div class="empty-icon"><?= icon('calendar') ?></div>
      <p><?= __('public.no_events') ?></p>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="grid grid-cols-3">
    <?php foreach ($gridEvents as $event): ?>
      <?php
      $status = EventStatusService::compute($event);
      $lc = $lotCounts[$event['id']] ?? null;
      ?>
      <a href="<?= base_url('events/' . $event['slug']) ?>" class="event-card" style="text-decoration:none;color:inherit;">
        <div class="event-card-media<?= !empty($event['banner_image']) ? ' has-image' : '' ?>"
             <?= !empty($event['banner_image']) ? 'style="background-image:url(\'' . upload_url($event['banner_image']) . '\')"' : '' ?>>
          <?php if (!empty($event['banner_image'])): ?>
            <img src="<?= upload_url($event['banner_image']) ?>" alt="<?= e($event['name_th']) ?>">
          <?php else: ?>
            <div class="media-placeholder"><?= icon('store') ?> <?= e($event['name_th']) ?></div>
          <?php endif; ?>
          <div class="status-overlay">
            <span class="<?= EventStatusService::badgeClass($status) ?>"><?= EventStatusService::label($status) ?></span>
          </div>
          <?php if ($lc && $lc['total'] > 0): ?>
            <div class="lots-left-overlay">
              <?php if ($lc['available'] <= 0): ?>
                <span class="lots-badge is-full"><?= icon('ticket') ?> <?= __('public.lots_full') ?></span>
              <?php elseif ($lc['available'] <= 5): ?>
                <span class="lots-badge is-low"><?= icon('ticket') ?> <?= __('public.lots_left', ['count' => $lc['available']]) ?></span>
              <?php else: ?>
                <span class="lots-badge is-available"><?= icon('ticket') ?> <?= __('public.lots_left', ['count' => $lc['available']]) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="event-card-body">
          <h3><?= e($event['name_th']) ?></h3>
          <div class="event-card-meta">
            <span><?= icon('calendar') ?> <?= e(date('d/m/Y', strtotime($event['start_date']))) ?> – <?= e(date('d/m/Y', strtotime($event['end_date']))) ?></span>
            <?php if (!empty($event['venue_name'])): ?><span><?= icon('map-pin') ?> <?= e($event['venue_name']) ?></span><?php endif; ?>
          </div>
          <div class="event-card-footer">
            <span class="btn btn-secondary btn-sm btn-block"><?= __('public.event_detail_title') ?></span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($settings['line_oa_id']) || !empty($settings['org_email'])): ?>
  <div class="follow-banner">
    <div>
      <h3><?= __('public.follow_title') ?></h3>
      <p><?= __('public.follow_text') ?></p>
    </div>
    <div class="follow-banner-actions">
      <?php if (!empty($settings['line_oa_id'])): ?>
        <a href="https://line.me/R/ti/p/<?= rawurlencode($settings['line_oa_id']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary"><?= icon('message-circle') ?> <?= __('public.follow_line') ?></a>
      <?php endif; ?>
      <?php if (!empty($settings['org_email'])): ?>
        <a href="mailto:<?= e($settings['org_email']) ?>" class="btn btn-secondary"><?= icon('mail') ?> <?= __('public.follow_email') ?></a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?= partial('ads_section', ['advertisements' => $advertisements]) ?>

<?php
$galleryItems = [];
foreach ($galleryPhotos as $gp) {
    $galleryItems[] = ['src' => upload_url($gp['image_path']), 'caption' => $gp['caption'] ?: '', 'url' => null];
}
foreach ($events as $ge) {
    if (!empty($ge['banner_image'])) {
        $galleryItems[] = ['src' => upload_url($ge['banner_image']), 'caption' => $ge['name_th'], 'url' => base_url('events/' . $ge['slug'])];
    }
}
$galleryItems = array_slice($galleryItems, 0, 12);
?>
<?php if ($galleryItems): ?>
  <h2 class="section-title"><?= __('public.gallery_title') ?></h2>
  <div class="gallery-carousel" id="galleryCarousel">
    <div class="gallery-carousel-viewport">
      <div class="gallery-carousel-track">
        <?php foreach ($galleryItems as $gi): ?>
          <div class="gallery-carousel-slide">
            <a href="#" class="gallery-carousel-media" data-lightbox-src="<?= e($gi['src']) ?>">
              <img src="<?= e($gi['src']) ?>" alt="<?= e($gi['caption']) ?>" loading="lazy">
            </a>
            <?php if ($gi['caption']): ?>
              <?php if ($gi['url']): ?>
                <a href="<?= e($gi['url']) ?>" class="gallery-caption"><?= e($gi['caption']) ?></a>
              <?php else: ?>
                <span class="gallery-caption"><?= e($gi['caption']) ?></span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if (count($galleryItems) > 1): ?>
      <div class="gallery-carousel-dots">
        <?php foreach ($galleryItems as $i => $gi): ?>
          <button type="button" class="gallery-carousel-dot<?= $i === 0 ? ' is-active' : '' ?>" data-index="<?= $i ?>" aria-label="<?= (int) $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<h2 class="section-title"><?= __('public.faq_title') ?></h2>
<div class="faq-section">
  <details class="faq-item">
    <summary><?= __('public.faq_q1') ?></summary>
    <div class="faq-answer"><?= __('public.faq_a1') ?></div>
  </details>
  <details class="faq-item">
    <summary><?= __('public.faq_q2') ?></summary>
    <div class="faq-answer">
      <?= __('public.faq_a2') ?>
      <ul>
        <li><?= payment_method_label('onsite_cash') ?></li>
        <?php if (!empty($stripeEnabled)): ?><li><?= payment_method_label('stripe') ?></li><?php endif; ?>
      </ul>
    </div>
  </details>
  <details class="faq-item">
    <summary><?= __('public.faq_q3') ?></summary>
    <div class="faq-answer"><?= __('public.faq_a3', ['days' => (int) ($settings['cancellation_cutoff_days'] ?? 3)]) ?></div>
  </details>
  <details class="faq-item">
    <summary><?= __('public.faq_q4') ?></summary>
    <div class="faq-answer"><?= __('public.faq_a4') ?></div>
  </details>
  <details class="faq-item">
    <summary><?= __('public.faq_q5') ?></summary>
    <div class="faq-answer"><?= __('public.faq_a5') ?></div>
  </details>
</div>

<div class="contact-cta-banner">
  <div>
    <h3><?= __('public.contact_banner_title') ?></h3>
    <p><?= __('public.contact_banner_text') ?></p>
  </div>
  <a href="<?= base_url('contact') ?>" class="btn btn-lg"><?= __('public.contact_banner_button') ?></a>
</div>
