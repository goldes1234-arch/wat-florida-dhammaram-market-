<div class="container-narrow" style="padding:0;">
  <h1><?= __('public.book_this_lot') ?></h1>

  <?php if (!empty($lot['photo'])): ?>
    <div class="event-hero-media" style="aspect-ratio:16/9;background-image:url('<?= upload_url($lot['photo']) ?>')">
      <img src="<?= upload_url($lot['photo']) ?>" alt="<?= e($lot['code']) ?>">
    </div>
  <?php endif; ?>

  <div class="booking-lot-summary">
    <div>
      <div class="text-sm text-muted"><?= e($event['name_th']) ?></div>
      <div class="lot-summary-code"><?= __('booking.lot_label') ?> <?= e($lot['code']) ?></div>
    </div>
    <div class="lot-summary-price"><?= money((float) $lot['price']) ?></div>
  </div>

  <form method="post" action="<?= base_url('events/' . $event['slug'] . '/book/' . $lot['id']) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="hp-field"><label>Website</label><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

    <div class="card mb-6">
      <div class="card-header"><h3><?= __('public.your_info') ?></h3></div>
      <div class="form-group">
        <label><?= __('booking.booker_name') ?></label>
        <input type="text" name="booker_name" class="form-control" value="<?= e(old('booker_name')) ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('booking.booker_phone') ?></label>
          <input type="text" name="booker_phone" class="form-control" value="<?= e(old('booker_phone')) ?>" required>
        </div>
        <div class="form-group">
          <label><?= __('booking.booker_email') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
          <input type="email" name="booker_email" class="form-control" value="<?= e(old('booker_email')) ?>">
        </div>
      </div>
      <div class="form-group">
        <label><?= __('public.shop_photo_label') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
        <input type="file" name="shop_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        <p class="form-hint"><?= __('public.shop_photo_hint') ?></p>
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-header"><h3><?= __('public.payment_method_title') ?></h3></div>
      <div class="payment-options">
        <label class="payment-option">
          <input type="radio" name="payment_method" value="onsite_cash" checked>
          <div>
            <div class="option-title"><?= __('booking.method_onsite_cash') ?></div>
            <div class="option-desc"><?= __('public.pay_onsite_desc') ?></div>
          </div>
        </label>
        <?php if ($stripeEnabled): ?>
          <label class="payment-option">
            <input type="radio" name="payment_method" value="stripe">
            <div>
              <div class="option-title"><?= __('booking.method_stripe') ?></div>
              <div class="option-desc"><?= __('public.pay_stripe_desc') ?></div>
            </div>
          </label>
        <?php endif; ?>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block"><?= __('public.submit_booking') ?></button>
  </form>
</div>
