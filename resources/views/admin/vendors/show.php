<?php
/** @var array $vendor */
/** @var array $history */
?>
<div class="page-header">
  <h1>
    <?= e($vendor['name']) ?>
    <?php if (!empty($vendor['deletion_requested_at'])): ?>
      <span class="badge badge-red">🗑️ <?= __('vendor.deletion_requested_badge') ?></span>
    <?php endif; ?>
  </h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/vendors') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<?php if (!empty($vendor['deletion_requested_at'])): ?>
  <div class="card mb-6" style="border-color:var(--color-danger-light);background:var(--color-danger-light);max-width:520px;">
    <p class="text-sm mb-4"><?= __('vendor.deletion_requested_at', ['date' => date('m/d/Y H:i', strtotime($vendor['deletion_requested_at']))]) ?></p>
    <form method="post" action="<?= base_url('admin/vendors/' . $vendor['id'] . '/dismiss-deletion-request') ?>" data-confirm="<?= e(__('vendor.dismiss_deletion_request_confirm')) ?>" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary btn-sm"><?= __('vendor.dismiss_deletion_request_button') ?></button>
    </form>
  </div>
<?php endif; ?>

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
      <label><?= __('booking.items_for_sale_label') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
      <input type="text" name="items_for_sale" class="form-control" maxlength="200" value="<?= e($vendor['items_for_sale'] ?? '') ?>">
      <p class="form-hint"><?= __('vendor.items_for_sale_hint') ?></p>
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

<div class="card mb-6" style="max-width:520px;">
  <div class="card-header"><h3><?= __('vendor.line_title') ?></h3></div>
  <?php if (!empty($vendor['line_pending_user_id'])): ?>
    <div class="alert alert-warning mb-4">
      <p class="text-sm mb-4"><?= __('vendor.line_link_pending', ['date' => date('m/d/Y H:i', strtotime($vendor['line_link_requested_at']))]) ?></p>
      <?php if (!empty($vendor['line_user_id'])): ?>
        <p class="text-sm mb-4"><strong><?= __('vendor.line_link_replaces_warning') ?></strong></p>
      <?php endif; ?>
      <div style="display:flex;gap:8px;">
        <form method="post" action="<?= base_url('admin/vendors/' . $vendor['id'] . '/approve-line-link') ?>" data-confirm="<?= e(__('vendor.line_link_approve_confirm')) ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-primary btn-sm"><?= __('vendor.line_link_approve_button') ?></button>
        </form>
        <form method="post" action="<?= base_url('admin/vendors/' . $vendor['id'] . '/reject-line-link') ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-secondary btn-sm"><?= __('vendor.line_link_reject_button') ?></button>
        </form>
      </div>
    </div>
  <?php endif; ?>
  <?php if (!empty($vendor['line_user_id'])): ?>
    <p class="text-sm mb-4">✅ <?= __('vendor.line_connected') ?></p>
    <form method="post" action="<?= base_url('admin/vendors/' . $vendor['id'] . '/line-message') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label><?= __('vendor.line_message_label') ?></label>
        <textarea name="message" class="form-control" rows="3" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><?= __('vendor.line_send_button') ?></button>
    </form>
  <?php else: ?>
    <p class="form-hint mb-0"><?= __('vendor.line_not_connected') ?></p>
  <?php endif; ?>
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
              <td><?= date('m/d/Y', strtotime((string) $booking['event_start_date'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
