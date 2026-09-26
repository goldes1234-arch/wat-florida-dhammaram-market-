<?php
use App\Services\EventStatusService;

$eventName = $event['name_th'];
$description = $event['description_th'];
if (\App\Core\Lang::locale() === 'en') {
    $eventName = $event['name_en'] ?: $event['name_th'];
    $description = $event['description_en'] ?: $event['description_th'];
}
?>

<?php if (!empty($event['banner_image'])): ?>
  <div class="event-hero-media" style="background-image:url('<?= upload_url($event['banner_image']) ?>')">
    <img src="<?= upload_url($event['banner_image']) ?>" alt="<?= e($eventName) ?>">
  </div>
<?php endif; ?>

<div class="page-header">
  <h1><?= e($eventName) ?></h1>
  <span class="<?= EventStatusService::badgeClass($status) ?>"><?= EventStatusService::label($status) ?></span>
</div>

<div class="event-info-grid">
  <div>
    <?php if ($description): ?>
      <div class="card mb-6"><p class="mb-0"><?= nl2br(e($description)) ?></p></div>
    <?php endif; ?>

    <?php if (!empty($event['floorplan_image'])): ?>
      <div class="card mb-6">
        <div class="card-header"><h3><?= __('public.floorplan_title') ?></h3></div>
        <a href="#" data-lightbox-src="<?= upload_url($event['floorplan_image']) ?>" class="floorplan-thumb">
          <img src="<?= upload_url($event['floorplan_image']) ?>" alt="<?= __('public.floorplan_title') ?>">
          <span class="zoom-hint">🔍 <?= __('public.floorplan_hint') ?></span>
        </a>
      </div>
    <?php endif; ?>

    <?php if (!empty($eventPhotos)): ?>
      <div class="card mb-6">
        <div class="card-header"><h3><?= __('public.event_photos_title') ?></h3></div>
        <div class="gallery-carousel" id="galleryCarousel">
          <div class="gallery-carousel-viewport">
            <div class="gallery-carousel-track">
              <?php foreach ($eventPhotos as $photo): ?>
                <div class="gallery-carousel-slide">
                  <a href="#" class="gallery-carousel-media" data-lightbox-src="<?= upload_url($photo['image_path']) ?>">
                    <img src="<?= upload_url($photo['image_path']) ?>" alt="<?= e($eventName) ?>" loading="lazy">
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php if (count($eventPhotos) > 1): ?>
            <div class="gallery-carousel-dots">
              <?php foreach ($eventPhotos as $i => $photo): ?>
                <button type="button" class="gallery-carousel-dot<?= $i === 0 ? ' is-active' : '' ?>" data-index="<?= $i ?>" aria-label="<?= (int) $i + 1 ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h3><?= __('public.lots_title') ?></h3>
      </div>

      <div class="lot-legend-note">
        <div class="legend-note-title">📌 <?= __('public.legend_note_label') ?></div>
        <div class="legend-note-items">
          <span class="legend-chip chip-available"><strong><?= __('public.legend_available') ?></strong> — <?= __('public.legend_note_available') ?></span>
          <span class="legend-chip chip-pending"><strong><?= __('public.legend_pending') ?></strong> — <?= __('public.legend_note_pending') ?></span>
          <span class="legend-chip chip-booked"><strong><?= __('public.legend_booked') ?></strong> — <?= __('public.legend_note_booked') ?></span>
          <span class="legend-chip chip-disabled"><strong><?= __('public.legend_disabled') ?></strong> — <?= __('public.legend_note_disabled') ?></span>
        </div>
      </div>

      <?php if (!$mappedLots && !$photoLots && !$groupedLots): ?>
        <p class="text-muted"><?= __('lot.none') ?></p>
      <?php endif; ?>

      <?php
      // Shared status/clickability/title computation for a lot cell, used by both the
      // CSS-grid booth map and the photo-coordinate map below so the booking/status logic
      // isn't duplicated between the two render branches.
      $lotClickInfo = function (array $lot) use ($event, $status) {
          $clickable = $lot['status'] === 'available' && $status === EventStatusService::OPEN;
          $href = $clickable ? base_url('events/' . $event['slug'] . '/book/' . $lot['id']) : '#';
          $tag = $clickable ? 'a' : 'div';
          $occupied = in_array($lot['status'], ['pending_payment', 'booked'], true) && !empty($lot['booker_name']);
          $displayPhoto = $lot['shop_photo'] ?? $lot['photo'] ?? null;
          $title = e($lot['code']) . ' · ' . money((float) $lot['price']);
          if ($occupied) {
              $title .= ' · ' . e(__('public.booked_by', ['name' => mask_booker_name($lot['booker_name'])]));
          }
          return compact('clickable', 'href', 'tag', 'displayPhoto', 'title');
      };
      ?>

      <?php if ($mappedLots || $photoLots): ?>
        <div class="map-toolbar">
          <button type="button" class="btn btn-secondary btn-sm" id="mapZoomOut" title="<?= e(__('public.map_zoom_out')) ?>">&minus;</button>
          <span id="mapZoomLabel">100%</span>
          <button type="button" class="btn btn-secondary btn-sm" id="mapZoomIn" title="<?= e(__('public.map_zoom_in')) ?>">+</button>
          <button type="button" class="btn btn-secondary btn-sm" id="mapZoomReset"><?= __('public.map_zoom_reset') ?></button>
        </div>
      <?php endif; ?>

      <?php if ($layoutMode === 'photo' && $photoLots): ?>
        <div class="photo-map-viewport">
          <div class="photo-map-canvas" id="photoMapCanvas"
               data-poll-url="<?= base_url('events/' . $event['slug'] . '/lot-status') ?>">
            <img src="<?= upload_url($event['floorplan_image']) ?>" class="photo-map-image" alt="">
            <?php foreach ($photoLots as $lot): ?>
              <?php
              ['clickable' => $clickable, 'href' => $href, 'tag' => $tag, 'title' => $title] = $lotClickInfo($lot);
              $isBox = $lot['map_shape'] === 'box';
              $rotateStyle = $isBox ? ' transform: translate(-50%, -50%) rotate(' . e((string) (float) $lot['map_rotation']) . 'deg);' : '';
              ?>
              <<?= $tag ?> <?= $clickable ? 'href="' . $href . '"' : '' ?>
                class="photo-pin status-<?= e($lot['status']) ?> size-<?= e($lot['map_size']) ?> shape-<?= e($lot['map_shape']) ?>"
                data-lot-id="<?= (int) $lot['id'] ?>"
                style="left: <?= e($lot['map_x']) ?>%; top: <?= e($lot['map_y']) ?>%;<?= $rotateStyle ?>"
                title="<?= $title ?>"><?= e($lot['code']) ?></<?= $tag ?>>
            <?php endforeach; ?>
          </div>
        </div>
      <?php elseif ($mappedLots): ?>
        <div class="booth-map-viewport">
          <div class="booth-map-canvas" id="boothMapCanvas"
               data-poll-url="<?= base_url('events/' . $event['slug'] . '/lot-status') ?>"
               style="--map-cols: <?= (int) $maxCol ?>; --map-rows: <?= (int) $maxRow ?>;">
            <?php foreach ($mappedLots as $lot): ?>
              <?php ['clickable' => $clickable, 'href' => $href, 'tag' => $tag, 'displayPhoto' => $displayPhoto, 'title' => $title] = $lotClickInfo($lot); ?>
              <<?= $tag ?> <?= $clickable ? 'href="' . $href . '"' : '' ?>
                class="booth-cell status-<?= e($lot['status']) ?><?= $displayPhoto ? ' has-photo' : '' ?>"
                data-lot-id="<?= (int) $lot['id'] ?>"
                style="grid-row: <?= (int) $lot['grid_row'] ?>; grid-column: <?= (int) $lot['grid_col'] ?>;"
                title="<?= $title ?>"><?= e($lot['code']) ?><?php if ($displayPhoto): ?><span class="booth-photo-preview"><img src="<?= upload_url($displayPhoto) ?>" alt="<?= e($lot['code']) ?>"></span><?php endif; ?></<?= $tag ?>>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($groupedLots as $group): ?>
        <div class="lot-zone-group">
          <h4><?= $group['zone_name'] ? e($group['zone_name']) : (($mappedLots || $photoLots) ? e(__('public.other_lots_title')) : '') ?></h4>
          <div class="lot-grid">
            <?php foreach ($group['lots'] as $lot): ?>
              <?php
              $statusSuffix = ['available' => 'available', 'pending_payment' => 'pending', 'booked' => 'booked', 'disabled' => 'disabled'][$lot['status']] ?? 'booked';
              $cls = 'lot-chip is-' . $statusSuffix;
              $clickable = $lot['status'] === 'available' && $status === EventStatusService::OPEN;
              $href = $clickable ? base_url('events/' . $event['slug'] . '/book/' . $lot['id']) : '#';
              $tag = $clickable ? 'a' : 'div';
              $displayPhoto = $lot['shop_photo'] ?? $lot['photo'] ?? null;
              ?>
              <<?= $tag ?> <?= $clickable ? 'href="' . $href . '"' : '' ?> class="<?= $cls ?>">
                <?php if ($displayPhoto): ?>
                  <span class="lot-chip-photo" style="background-image:url('<?= upload_url($displayPhoto) ?>')">
                    <img src="<?= upload_url($displayPhoto) ?>" alt="">
                  </span>
                <?php endif; ?>
                <span class="lot-code"><?= e($lot['code']) ?></span>
                <span class="lot-price"><?= money((float) $lot['price']) ?></span>
                <?php if (in_array($lot['status'], ['pending_payment', 'booked'], true) && !empty($lot['booker_name'])): ?>
                  <span class="lot-booker"><?= __('public.booked_by', ['name' => mask_booker_name($lot['booker_name'])]) ?></span>
                <?php endif; ?>
              </<?= $tag ?>>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <div class="card mb-6">
      <div class="info-row"><span class="info-label"><?= __('public.event_dates') ?></span><span class="info-value"><?= e(date('d/m/Y', strtotime($event['start_date']))) ?> – <?= e(date('d/m/Y', strtotime($event['end_date']))) ?></span></div>
      <?php if (!empty($event['venue_name'])): ?>
        <div class="info-row"><span class="info-label"><?= __('public.venue') ?></span><span class="info-value"><?= e($event['venue_name']) ?></span></div>
      <?php endif; ?>
      <div class="info-row"><span class="info-label"><?= __('public.booking_window') ?></span><span class="info-value text-sm"><?= e(date('d/m/Y H:i', strtotime($event['booking_open_at']))) ?> – <?= e(date('d/m/Y H:i', strtotime($event['booking_close_at']))) ?></span></div>
    </div>

    <?php if ($eventContacts): ?>
      <div class="card mb-6">
        <div class="card-header"><h3><?= __('public.event_contacts_title') ?></h3></div>
        <?php foreach ($eventContacts as $contact): ?>
          <div class="event-contact-row">
            <div class="event-contact-name"><?= e($contact['name']) ?></div>
            <a href="tel:<?= e($contact['phone']) ?>" class="event-contact-phone">📞 <?= e($contact['phone']) ?></a>
            <?php if (!empty($contact['contact_channel'])): ?>
              <div class="event-contact-channel"><?= e($contact['contact_channel']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($status === EventStatusService::COMING_SOON): ?>
      <div class="card">
        <div class="card-header"><h3><?= __('public.notify_form_title') ?></h3></div>
        <form method="post" action="<?= base_url('events/' . $event['slug'] . '/interest') ?>">
          <?= csrf_field() ?>
          <div class="hp-field"><label>Website</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
          <div class="form-group">
            <label><?= __('public.notify_form_email') ?></label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="form-group">
            <label><?= __('public.notify_form_phone') ?></label>
            <input type="text" name="phone" class="form-control">
          </div>
          <p class="form-hint mb-4"><?= __('public.notify_hint') ?></p>
          <button type="submit" class="btn btn-accent btn-block"><?= __('public.notify_me') ?></button>
        </form>
      </div>
    <?php endif; ?>

    <?php if ($isSoldOut): ?>
      <div class="card">
        <div class="card-header"><h3><?= icon('bell') ?> <?= __('public.waitlist_title') ?></h3></div>
        <p class="form-hint mb-4"><?= __('public.waitlist_hint') ?></p>
        <form method="post" action="<?= base_url('events/' . $event['slug'] . '/waitlist') ?>">
          <?= csrf_field() ?>
          <div class="hp-field"><label>Website</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
          <div class="form-group">
            <label><?= __('booking.booker_name') ?></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?= __('booking.booker_phone') ?></label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?= __('booking.booker_email') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
            <input type="email" name="email" class="form-control">
          </div>
          <button type="submit" class="btn btn-accent btn-block"><?= icon('plus-circle') ?> <?= __('public.waitlist_join') ?></button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= partial('ads_section', ['advertisements' => $advertisements]) ?>
