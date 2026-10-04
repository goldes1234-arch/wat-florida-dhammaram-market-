<?php
/** @var string $search */
/** @var array<string,int> $statusCounts */
$listQuery = array_filter(['event_id' => $selectedEvent, 'status' => $selectedStatus, 'sort' => $selectedSort, 'q' => $search], static fn ($v) => $v !== '' && $v !== null);
$returnTo = 'admin/bookings' . ($listQuery ? '?' . http_build_query($listQuery + (($page ?? 1) > 1 ? ['page' => $page] : [])) : '');
$chipBase = array_filter(['event_id' => $selectedEvent, 'sort' => $selectedSort, 'q' => $search], static fn ($v) => $v !== '' && $v !== null);
$chipUrl = static fn (string $st) => base_url('admin/bookings' . ($chipBase || $st !== '' ? '?' . http_build_query($chipBase + ($st !== '' ? ['status' => $st] : [])) : ''));
$allCount = array_sum($statusCounts);
?>
<div class="page-header">
  <h1><?= __('booking.list_title') ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/bookings/export?' . http_build_query($listQuery)) ?>" class="btn btn-secondary"><?= icon('download') ?> <?= __('booking.export_button') ?></a>
    <details class="menu-more">
      <summary class="btn btn-secondary" aria-label="<?= e(__('common.more_actions')) ?>">&hellip;</summary>
      <div class="menu-more-panel">
        <form method="post" action="<?= base_url('admin/bookings/delete-all') ?>" data-confirm="<?= e(__('booking.delete_all_confirm')) ?>" style="margin:0;">
          <?= csrf_field() ?>
          <input type="hidden" name="event_id" value="<?= e($selectedEvent) ?>">
          <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">
          <button type="submit" class="menu-more-danger"><?= __('booking.delete_all_button') ?></button>
          <p class="form-hint mb-0"><?= __('booking.delete_hint') ?></p>
        </form>
      </div>
    </details>
  </div>
</div>

<form method="get" action="<?= base_url('admin/bookings') ?>" class="filter-bar">
  <div class="form-group search-field">
    <label><?= __('booking.search_label') ?></label>
    <input type="search" name="q" class="form-control" value="<?= e($search) ?>" placeholder="<?= e(__('booking.search_placeholder')) ?>">
  </div>
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
    <label><?= __('booking.filter_sort') ?></label>
    <select name="sort" class="form-control" onchange="this.form.submit()">
      <option value="desc" <?= $selectedSort === 'desc' ? 'selected' : '' ?>><?= __('booking.sort_desc') ?></option>
      <option value="asc" <?= $selectedSort === 'asc' ? 'selected' : '' ?>><?= __('booking.sort_asc') ?></option>
    </select>
  </div>
  <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">
  <div class="form-group" style="flex:0 0 auto;align-self:flex-end;">
    <button type="submit" class="btn btn-primary"><?= __('booking.search_button') ?></button>
  </div>
</form>

<div class="status-chips mb-4">
  <a href="<?= e($chipUrl('')) ?>" class="status-chip<?= $selectedStatus === '' ? ' is-active' : '' ?>"><?= __('common.all') ?> <span><?= (int) $allCount ?></span></a>
  <?php foreach (['pending_payment', 'booked', 'rejected', 'cancelled'] as $st): ?>
    <a href="<?= e($chipUrl($st)) ?>" class="status-chip<?= $selectedStatus === $st ? ' is-active' : '' ?>"><?= booking_status_label($st) ?> <span><?= (int) ($statusCounts[$st] ?? 0) ?></span></a>
  <?php endforeach; ?>
</div>

<?php if ($bookings): ?>
  <?php foreach ($bookings as $b): ?>
    <?php if ($b['status'] === 'pending_payment'): ?>
      <form id="qa-confirm-<?= (int) $b['id'] ?>" method="post" action="<?= base_url('admin/bookings/' . $b['id'] . '/confirm') ?>" style="display:none"><?= csrf_field() ?><input type="hidden" name="return" value="<?= e($returnTo) ?>"></form>
      <form id="qa-reject-<?= (int) $b['id'] ?>" method="post" action="<?= base_url('admin/bookings/' . $b['id'] . '/reject') ?>" style="display:none" data-confirm="<?= e(__('booking.quick_reject_confirm', ['code' => $b['booking_code']])) ?>"><?= csrf_field() ?><input type="hidden" name="return" value="<?= e($returnTo) ?>"></form>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<?php if (!$bookings): ?>
  <div class="empty-state"><div class="empty-icon">🎫</div><?= __('booking.not_found') ?></div>
<?php else: ?>
  <form id="bulkDeleteForm" method="post" action="<?= base_url('admin/bookings/delete-selected') ?>" data-confirm="<?= e(__('booking.delete_selected_confirm')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="event_id" value="<?= e($selectedEvent) ?>">
    <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">
    <input type="hidden" name="sort" value="<?= e($selectedSort) ?>">

    <div class="table-wrap">
      <table class="table table-cards">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAllBookings"></th>
            <th><?= __('booking.code') ?></th>
            <th><?= __('event.singular') ?></th>
            <th><?= __('lot.singular') ?></th>
            <th><?= __('booking.booker_name') ?></th>
            <th><?= __('booking.items_for_sale_label') ?></th>
            <th><?= __('booking.payment_method') ?></th>
            <th><?= __('common.status') ?></th>
            <th><?= __('common.date') ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <?php $deletable = in_array($b['status'], ['rejected', 'cancelled'], true); ?>
            <tr onclick="location.href='<?= base_url('admin/bookings/' . $b['id']) ?>'" style="cursor:pointer;">
              <td onclick="event.stopPropagation()" class="cell-select">
                <?php if ($deletable): ?>
                  <input type="checkbox" name="ids[]" value="<?= (int) $b['id'] ?>" class="booking-row-checkbox">
                <?php endif; ?>
              </td>
              <td data-label="<?= e(__('booking.code')) ?>"><strong><?= e($b['booking_code']) ?></strong></td>
              <td data-label="<?= e(__('event.singular')) ?>"><?= e($b['event_name_th']) ?></td>
              <td data-label="<?= e(__('lot.singular')) ?>"><?= e($b['lot_code']) ?></td>
              <td data-label="<?= e(__('booking.booker_name')) ?>"><?= e($b['booker_name']) ?><br><span class="text-sm text-muted"><?= e($b['booker_phone']) ?></span></td>
              <td data-label="<?= e(__('booking.items_for_sale_label')) ?>" class="text-sm"><?= e($b['items_for_sale'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
              <td data-label="<?= e(__('booking.payment_method')) ?>"><?= payment_method_label($b['payment_method']) ?></td>
              <td data-label="<?= e(__('common.status')) ?>"><span class="<?= booking_status_badge_class($b['status']) ?>"><?= booking_status_label($b['status']) ?></span></td>
              <td data-label="<?= e(__('common.date')) ?>" class="text-sm text-muted"><?= e(date('m/d/Y H:i', strtotime($b['created_at']))) ?></td>
              <td onclick="event.stopPropagation()" class="cell-actions cell-actions-stack">
                <?php if ($b['status'] === 'pending_payment'): ?>
                  <button type="submit" form="qa-confirm-<?= (int) $b['id'] ?>" class="btn btn-success btn-sm"><?= __('booking.quick_confirm') ?></button>
                  <button type="submit" form="qa-reject-<?= (int) $b['id'] ?>" class="btn btn-secondary btn-sm"><?= __('booking.quick_reject') ?></button>
                <?php endif; ?>
              </td>
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

  <?= partial('pagination', [
    'page' => $page,
    'totalPages' => $totalPages,
    'path' => 'admin/bookings',
    'query' => $listQuery,
  ]) ?>

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
