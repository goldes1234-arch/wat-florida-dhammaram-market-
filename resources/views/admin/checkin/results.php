<div class="page-header">
  <h1><?= __('checkin.title') ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/checkin') ?>" class="btn btn-secondary">&larr; <?= __('checkin.search_another') ?></a>
  </div>
</div>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th><?= __('booking.code') ?></th>
        <th><?= __('booking.booker_name') ?></th>
        <th><?= __('booking.booker_phone') ?></th>
        <th><?= __('lot.singular') ?></th>
        <th><?= __('common.status') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr onclick="location.href='<?= base_url('admin/checkin/' . $b['booking_code']) ?>'" style="cursor:pointer;">
          <td><strong><?= e($b['booking_code']) ?></strong></td>
          <td><?= e($b['booker_name']) ?></td>
          <td><?= e($b['booker_phone']) ?></td>
          <td><?= e($b['lot_code']) ?></td>
          <td><span class="<?= booking_status_badge_class($b['status']) ?>"><?= booking_status_label($b['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
