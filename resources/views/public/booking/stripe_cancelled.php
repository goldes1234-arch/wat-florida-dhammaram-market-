<div class="container-narrow" style="padding:0;text-align:center;">
  <div style="font-size:44px;">⚠️</div>
  <h1><?= __('public.stripe_cancelled_title') ?></h1>
  <p class="text-muted"><?= __('public.stripe_cancelled_body') ?></p>

  <div class="confirmation-code-box">
    <div class="code-label"><?= __('public.confirmation_code_label') ?></div>
    <div class="code-value"><?= e($booking['booking_code']) ?></div>
  </div>

  <a href="<?= base_url('my-booking/' . $booking['booking_code']) ?>" class="btn btn-primary"><?= __('public.booking_status') ?></a>
</div>
