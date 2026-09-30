<?php
/** @var array $vendor */
/** @var array $history */
?>
<div class="page-header">
  <h1><?= e($vendor['name']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/vendors') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<div class="card mb-6" style="max-width:520px;">
  <form method="post" action="<?= base_url('admin/vendors/' . $vendor['id']) ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label><?= __('vendor.name') ?></label>
      <input type="text" name="name" class="form-control" value="<?= e($vendor['name']) ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('vendor.phone') ?></label>
        <input type="text" name="phone" class="form-control" value="<?= e($vendor['phone']) ?>" required>
      </div>
      <div class="form-group">
        <label><?= __('vendor.email') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
        <input type="email" name="email" class="form-control" value="<?= e($vendor['email'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label><?= __('vendor.notes') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
      <textarea name="notes" class="form-control" rows="3"><?= e($vendor['notes'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><?= __('common.save') ?></button>
  </form>

  <form method="post" action="<?= base_url('admin/vendors/' . $vendor['id'] . '/delete') ?>" class="mt-4" data-confirm="<?= e(__('vendor.delete_confirm')) ?>">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger btn-block"><?= __('vendor.delete_button') ?></button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h3><?= __('vendor.history_title') ?></h3></div>
  <?php if (!$history): ?>
    <p class="text-sm text-muted mb-0"><?= __('vendor.no_history') ?></p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th><?= __('event.singular') ?></th>
            <th><?= __('lot.code') ?></th>
            <th><?= __('booking.code') ?></th>
            <th><?= __('common.status') ?></th>
            <th><?= __('public.event_dates') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $booking): ?>
            <tr onclick="location.href='<?= base_url('admin/bookings/' . $booking['id']) ?>'" style="cursor:pointer;">
              <td><?= e($booking['event_name_th']) ?></td>
              <td><strong><?= e($booking['lot_code']) ?></strong></td>
              <td><?= e($booking['booking_code']) ?></td>
              <td><span class="<?= booking_status_badge_class($booking['status']) ?>"><?= booking_status_label($booking['status']) ?></span></td>
              <td><?= date('d/m/Y', strtotime((string) $booking['event_start_date'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
