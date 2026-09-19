<div class="page-header">
  <h1><?= __('booking.list_title') ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/bookings/export?' . http_build_query(['event_id' => $selectedEvent, 'status' => $selectedStatus, 'sort' => $selectedSort])) ?>" class="btn btn-secondary"><?= icon('download') ?> <?= __('booking.export_button') ?></a>
    <form method="post" action="<?= base_url('admin/bookings/delete-all') ?>" data-confirm="<?= e(__('booking.delete_all_confirm')) ?>" style="margin:0;">
      <?= csrf_field() ?>
      <input type="hidden" name="event_id" value="<?= e($selectedEvent) ?>">
      <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">
      <button type="submit" class="btn btn-danger"><?= __('booking.delete_all_button') ?></button>
    </form>
  </div>
</div>

<form method="get" action="<?= base_url('admin/bookings') ?>" class="filter-bar">
  <div class="form-group">
    <label><?= __('booking.filter_event') ?></label>
    <select name="event_id" class="form-control" onchange="this.form.submit()">
      <option value=""><?= __('common.all') ?></option>
      <?php foreach ($events as $ev): ?>
        <option value="<?= (int) $ev['id'] ?>" <?= (string) $selectedEvent === (string) $ev['id'] ? 'selected' : '' ?>><?= e($ev['name_th']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label><?= __('booking.filter_status') ?></label>
    <select name="status" class="form-control" onchange="this.form.submit()">
      <option value=""><?= __('common.all') ?></option>
      <?php foreach (['pending_payment', 'booked', 'rejected', 'cancelled'] as $st): ?>
        <option value="<?= $st ?>" <?= $selectedStatus === $st ? 'selected' : '' ?>><?= booking_status_label($st) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label><?= __('booking.filter_sort') ?></label>
    <select name="sort" class="form-control" onchange="this.form.submit()">
      <option value="desc" <?= $selectedSort === 'desc' ? 'selected' : '' ?>><?= __('booking.sort_desc') ?></option>
      <option value="asc" <?= $selectedSort === 'asc' ? 'selected' : '' ?>><?= __('booking.sort_asc') ?></option>
    </select>
  </div>
</form>

<?php if (!$bookings): ?>
  <div class="empty-state"><div class="empty-icon">🎫</div><?= __('booking.not_found') ?></div>
<?php else: ?>
  <form id="bulkDeleteForm" method="post" action="<?= base_url('admin/bookings/delete-selected') ?>" data-confirm="<?= e(__('booking.delete_selected_confirm')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="event_id" value="<?= e($selectedEvent) ?>">
    <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">
    <input type="hidden" name="sort" value="<?= e($selectedSort) ?>">

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAllBookings"></th>
            <th><?= __('booking.code') ?></th>
            <th><?= __('event.singular') ?></th>
            <th><?= __('lot.singular') ?></th>
            <th><?= __('booking.booker_name') ?></th>
            <th><?= __('booking.payment_method') ?></th>
            <th><?= __('common.status') ?></th>
            <th><?= __('common.date') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <?php $deletable = in_array($b['status'], ['rejected', 'cancelled'], true); ?>
            <tr onclick="location.href='<?= base_url('admin/bookings/' . $b['id']) ?>'" style="cursor:pointer;">
              <td onclick="event.stopPropagation()">
                <?php if ($deletable): ?>
                  <input type="checkbox" name="ids[]" value="<?= (int) $b['id'] ?>" class="booking-row-checkbox">
                <?php endif; ?>
              </td>
              <td><strong><?= e($b['booking_code']) ?></strong></td>
              <td><?= e($b['event_name_th']) ?></td>
              <td><?= e($b['lot_code']) ?></td>
              <td><?= e($b['booker_name']) ?><br><span class="text-sm text-muted"><?= e($b['booker_phone']) ?></span></td>
              <td><?= payment_method_label($b['payment_method']) ?></td>
              <td><span class="<?= booking_status_badge_class($b['status']) ?>"><?= booking_status_label($b['status']) ?></span></td>
              <td class="text-sm text-muted"><?= e(date('d/m/Y H:i', strtotime($b['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      <button type="submit" id="deleteSelectedBtn" class="btn btn-danger" disabled><?= __('booking.delete_selected_button') ?></button>
      <p class="form-hint mb-0"><?= __('booking.delete_hint') ?></p>
    </div>
  </form>

  <script>
  (function () {
    var selectAll = document.getElementById('selectAllBookings');
    var deleteBtn = document.getElementById('deleteSelectedBtn');
    var checkboxes = function () { return document.querySelectorAll('.booking-row-checkbox'); };

    function refreshButton() {
      var anyChecked = Array.prototype.some.call(checkboxes(), function (c) { return c.checked; });
      deleteBtn.disabled = !anyChecked;
    }

    selectAll.addEventListener('click', function (e) { e.stopPropagation(); });
    selectAll.addEventListener('change', function () {
      Array.prototype.forEach.call(checkboxes(), function (c) { c.checked = selectAll.checked; });
      refreshButton();
    });

    Array.prototype.forEach.call(checkboxes(), function (c) {
      c.addEventListener('change', refreshButton);
    });
  })();
  </script>
<?php endif; ?>
