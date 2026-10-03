<div class="page-header">
  <h1><?= __('booking.detail_title') ?> — <?= e($booking['booking_code']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/bookings') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<div class="event-info-grid">
  <div>
    <div class="card mb-6">
      <div class="card-header">
        <h3><?= __('booking.singular') ?></h3>
        <span class="<?= booking_status_badge_class($booking['status']) ?>"><?= booking_status_label($booking['status']) ?></span>
      </div>
      <div class="info-row"><span class="info-label"><?= __('event.singular') ?></span><span class="info-value"><?= e($booking['event_name_th']) ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('lot.singular') ?></span><span class="info-value"><?= e($booking['lot_code']) ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('booking.booker_name') ?></span><span class="info-value"><?= e($booking['booker_name']) ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('booking.booker_phone') ?></span><span class="info-value"><?= e($booking['booker_phone']) ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('booking.booker_email') ?></span><span class="info-value"><?= e($booking['booker_email'] ?: '—') ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('booking.payment_method') ?></span><span class="info-value"><?= payment_method_label($booking['payment_method']) ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('lot.price') ?></span><span class="info-value"><?= money((float) $booking['price_at_booking'], $booking['currency_code']) ?></span></div>
      <div class="info-row"><span class="info-label"><?= __('common.date') ?></span><span class="info-value"><?= e(date('d/m/Y H:i', strtotime($booking['created_at']))) ?></span></div>
      <?php if (!empty($booking['admin_note'])): ?>
        <div class="info-row"><span class="info-label"><?= __('common.note') ?></span><span class="info-value"><?= e($booking['admin_note']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($booking['refunded_at'])): ?>
        <div class="info-row"><span class="info-label"><?= __('booking.refunded_label') ?></span><span class="info-value">✅ <?= e(date('d/m/Y H:i', strtotime($booking['refunded_at']))) ?></span></div>
      <?php endif; ?>
    </div>

    <div class="card mb-6">
      <div class="card-header"><h3><?= __('booking.items_for_sale_label') ?></h3></div>
      <form method="post" action="<?= base_url('admin/bookings/' . $booking['id'] . '/items') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
          <input type="text" name="items_for_sale" class="form-control" maxlength="200" value="<?= e($booking['items_for_sale'] ?? '') ?>">
          <p class="form-hint"><?= __('booking.items_for_sale_admin_hint') ?></p>
        </div>
        <button type="submit" class="btn btn-secondary"><?= __('common.save') ?></button>
      </form>
    </div>

    <?php if ($booking['status'] === 'pending_payment'): ?>
      <div class="card mb-6">
        <div class="card-header"><h3><?= __('booking.confirm_action') ?></h3></div>
        <form method="post" action="<?= base_url('admin/bookings/' . $booking['id'] . '/confirm') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <input type="text" name="note" class="form-control" placeholder="<?= e(__('booking.confirm_note_placeholder')) ?>">
          </div>
          <button type="submit" class="btn btn-success"><?= __('booking.confirm_action') ?></button>
        </form>
      </div>
      <div class="card mb-6">
        <div class="card-header"><h3><?= __('booking.reject_action') ?></h3></div>
        <form method="post" action="<?= base_url('admin/bookings/' . $booking['id'] . '/reject') ?>" data-confirm="<?= e(__('booking.reject_action')) ?>?">
          <?= csrf_field() ?>
          <div class="form-group">
            <input type="text" name="note" class="form-control" placeholder="<?= e(__('booking.reject_note_placeholder')) ?>">
          </div>
          <button type="submit" class="btn btn-danger"><?= __('booking.reject_action') ?></button>
        </form>
      </div>
    <?php endif; ?>

    <?php if (in_array($booking['status'], ['pending_payment', 'booked'], true)): ?>
      <div class="card mb-6">
        <div class="card-header"><h3><?= __('booking.cancel_action') ?></h3></div>
        <form method="post" action="<?= base_url('admin/bookings/' . $booking['id'] . '/cancel') ?>" data-confirm="<?= e(__('booking.cancel_action')) ?>?">
          <?= csrf_field() ?>
          <div class="form-group">
            <input type="text" name="note" class="form-control" placeholder="<?= e(__('booking.cancel_note_placeholder')) ?>">
          </div>
          <button type="submit" class="btn btn-danger"><?= __('booking.cancel_action') ?></button>
        </form>
      </div>
    <?php endif; ?>

    <?php if ($booking['status'] === 'cancelled' && $booking['payment_method'] === 'stripe' && !empty($booking['stripe_payment_intent_id']) && empty($booking['refunded_at'])): ?>
      <div class="card mb-6">
        <div class="card-header"><h3><?= __('booking.refund_action') ?></h3></div>
        <?php
          $refundDays = (int) (\App\Models\Setting::get()['refund_cutoff_days'] ?? 10);
          $refundWindowOpen = \App\Services\BookingService::isRefundWindowOpen($booking);
        ?>
        <?php if (\App\Core\Auth::isSuperAdmin() || \App\Core\Auth::isFinance()): ?>
          <p class="form-hint mb-4"><?= __('booking.refund_hint') ?></p>
          <?php if ($refundWindowOpen): ?>
            <p class="form-hint mb-4">✅ <?= __('booking.refund_window_open', ['days' => $refundDays]) ?></p>
            <form method="post" action="<?= base_url('admin/bookings/' . $booking['id'] . '/refund') ?>" data-confirm="<?= e(__('booking.refund_confirm')) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger"><?= __('booking.refund_action') ?></button>
            </form>
          <?php elseif (\App\Core\Auth::isSuperAdmin()): ?>
            <p class="form-hint mb-4" style="color:var(--color-danger-dark);">⛔ <?= __('booking.refund_window_closed', ['days' => $refundDays]) ?></p>
            <form method="post" action="<?= base_url('admin/bookings/' . $booking['id'] . '/refund') ?>" data-confirm="<?= e(__('booking.refund_confirm')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="override_window" value="1">
              <div class="form-group">
                <label><?= __('booking.refund_override_label') ?></label>
                <textarea name="override_reason" class="form-control" rows="2" required></textarea>
              </div>
              <button type="submit" class="btn btn-danger"><?= __('booking.refund_override_button') ?></button>
            </form>
          <?php else: ?>
            <p class="form-hint mb-0" style="color:var(--color-danger-dark);">⛔ <?= __('booking.refund_window_closed_finance', ['days' => $refundDays]) ?></p>
          <?php endif; ?>
        <?php else: ?>
          <p class="form-hint mb-0"><?= __('booking.refund_super_admin_only') ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3><?= __('booking.timeline_title') ?></h3></div>
    <?php foreach ($logs as $log): ?>
      <div class="status-log-item">
        <div class="status-log-dot"></div>
        <div>
          <div><strong><?= booking_status_label($log['to_status']) ?></strong>
            <span class="text-sm text-muted">
              (<?= $log['changed_by_type'] === 'admin' ? __('booking.by_admin') : ($log['changed_by_type'] === 'system' ? __('booking.by_system') : __('booking.by_guest')) ?><?= $log['admin_name'] ? ': ' . e($log['admin_name']) : '' ?>)
            </span>
          </div>
          <?php if (!empty($log['note'])): ?><div class="text-sm"><?= e($log['note']) ?></div><?php endif; ?>
          <div class="status-log-meta"><?= e(date('d/m/Y H:i', strtotime($log['created_at']))) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
