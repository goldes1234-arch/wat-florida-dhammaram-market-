<div class="container-narrow" style="padding:0;">
  <h1><?= __('public.search_title') ?></h1>

  <?php foreach ($bookings as $b): ?>
    <a href="<?= base_url('my-booking/' . $b['booking_code']) ?>" class="card mb-4" style="display:block;text-decoration:none;color:inherit;">
      <div class="card-header" style="margin-bottom:0;padding-bottom:0;border-bottom:none;">
        <div>
          <strong><?= e($b['booking_code']) ?></strong>
          <div class="text-sm text-muted"><?= e($b['event_name_th']) ?> — <?= __('booking.lot_label') ?> <?= e($b['lot_code']) ?></div>
        </div>
        <span class="<?= booking_status_badge_class($b['status']) ?>"><?= booking_status_label($b['status']) ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
