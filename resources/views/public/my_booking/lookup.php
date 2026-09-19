<div class="container-narrow" style="padding:0;">
  <h1><?= __('public.search_title') ?></h1>
  <p class="text-muted"><?= __('public.search_hint') ?></p>

  <div class="card mb-6">
    <div class="card-header"><h3><?= __('public.search_by_code') ?></h3></div>
    <form method="post" action="<?= base_url('my-booking') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <input type="text" name="booking_code" class="form-control" placeholder="TM-XXXXXX" style="text-transform:uppercase;">
      </div>
      <button type="submit" class="btn btn-primary btn-block"><?= __('public.search_button') ?></button>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('public.search_by_phone_email') ?></h3></div>
    <form method="post" action="<?= base_url('my-booking') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label><?= __('booking.booker_phone') ?></label>
        <input type="text" name="phone" class="form-control">
      </div>
      <div class="form-group">
        <label><?= __('booking.booker_email') ?></label>
        <input type="email" name="email" class="form-control">
      </div>
      <button type="submit" class="btn btn-secondary btn-block"><?= __('public.search_button') ?></button>
    </form>
  </div>
</div>
