<?php
/** @var array|null $lot */
/** @var string $token */
$eventName = $lot ? ($lot['event_name_th'] ?: ($lot['event_name_en'] ?? '')) : '';
?>
<div class="container-narrow" style="padding:0;">
  <div class="page-header">
    <h1><?= __('reservation.confirm_title') ?></h1>
  </div>

  <div class="card">
    <?php if (!$lot): ?>
      <div class="empty-state"><div class="empty-icon">⏳</div><?= __('reservation.expired_or_invalid') ?></div>

    <?php elseif ($lot['status'] === 'reserved'): ?>
      <p class="text-muted mb-2"><?= __('reservation.confirm_hint') ?></p>
      <div class="info-row"><span><?= __('event.singular') ?></span><strong><?= e($eventName) ?></strong></div>
      <div class="info-row"><span><?= __('lot.code') ?></span><strong><?= e($lot['code']) ?></strong></div>
      <div class="info-row"><span><?= __('lot.price') ?></span><strong><?= money((float) $lot['price']) ?></strong></div>
      <div class="info-row"><span><?= __('public.event_dates') ?></span><strong><?= date('d/m/Y', strtotime((string) $lot['event_start_date'])) ?></strong></div>
      <form method="post" action="<?= base_url('reserve/' . $token . '/confirm') ?>" class="mt-6">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary btn-lg btn-block"><?= __('reservation.confirm_button') ?></button>
      </form>

    <?php elseif ($lot['status'] === 'booked' && !empty($lot['reserved_confirmed_at'])): ?>
      <div class="empty-state">
        <div class="empty-icon">✅</div>
        <?= __('reservation.already_confirmed') ?>
      </div>
      <p class="text-center"><a href="<?= base_url('my-booking') ?>"><?= __('nav.my_booking') ?> &rarr;</a></p>

    <?php else: ?>
      <div class="empty-state"><div class="empty-icon">⏳</div><?= __('reservation.expired_or_invalid') ?></div>
    <?php endif; ?>
  </div>
</div>
