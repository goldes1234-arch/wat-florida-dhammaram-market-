<div class="page-header">
  <h1><?= __('lot.singular') ?> <?= e($lot['code']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/' . $lot['event_id'] . '/lots') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<div class="card" style="max-width:520px;">
  <form method="post" action="<?= base_url('admin/lots/' . $lot['id']) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-group">
      <label><?= __('lot.code') ?></label>
      <input type="text" name="code" class="form-control" value="<?= e($lot['code']) ?>" required>
    </div>
    <div class="form-group">
      <label><?= __('lot.photo') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
      <?php if (!empty($lot['photo'])): ?>
        <img src="<?= upload_url($lot['photo']) ?>" class="thumb-md mb-2" alt="">
        <div class="checkbox-row mb-2">
          <input type="checkbox" id="remove_photo" name="remove_photo" value="1">
          <label for="remove_photo" style="margin:0;"><?= __('lot.remove_photo') ?></label>
        </div>
      <?php endif; ?>
      <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
      <p class="form-hint"><?= __('lot.photo_hint') ?></p>
    </div>
    <div class="form-group">
      <label><?= __('lot.zone') ?></label>
      <select name="zone_id" class="form-control">
        <option value=""><?= __('lot.no_zone') ?></option>
        <?php foreach ($zones as $zone): ?>
          <option value="<?= (int) $zone['id'] ?>" <?= (int) $lot['zone_id'] === (int) $zone['id'] ? 'selected' : '' ?>><?= e($zone['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label><?= __('lot.price') ?></label>
      <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= e((string) $lot['price']) ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('lot.grid_row') ?></label>
        <input type="number" min="1" name="grid_row" class="form-control" value="<?= e((string) ($lot['grid_row'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label><?= __('lot.grid_col') ?></label>
        <input type="number" min="1" name="grid_col" class="form-control" value="<?= e((string) ($lot['grid_col'] ?? '')) ?>">
      </div>
    </div>
    <p class="form-hint mb-4">กำหนดตำแหน่งบนผังแผนที่ล็อกแบบอินเตอร์แอกทีฟ (ถ้าเว้นว่างไว้ ล็อกนี้จะแสดงในรายการ "ล็อกอื่นๆ" แทน)</p>
    <div class="form-group">
      <label><?= __('common.status') ?></label>
      <div><span class="<?= lot_status_badge_class($lot['status']) ?>"><?= lot_status_label($lot['status']) ?></span></div>
      <p class="form-hint"><?= __('lot.status_pending_payment') ?>/<?= __('lot.status_booked') ?> <?= mb_strtolower(__('common.status')) ?> <?= __('common.actions') ?>: <a href="<?= base_url('admin/bookings') ?>"><?= __('nav.bookings') ?></a></p>
    </div>
    <button type="submit" class="btn btn-primary"><?= __('common.save') ?></button>
  </form>

  <?php if (in_array($lot['status'], ['available', 'disabled'], true)): ?>
    <form method="post" action="<?= base_url('admin/lots/' . $lot['id'] . '/toggle-disable') ?>" class="mt-4">
      <?= csrf_field() ?>
      <?php if ($lot['status'] === 'disabled'): ?>
        <button type="submit" class="btn btn-success btn-block"><?= __('lot.enable_action') ?></button>
      <?php else: ?>
        <button type="submit" class="btn btn-danger btn-block" data-confirm="<?= e(__('lot.disable_action')) ?>?"><?= __('lot.disable_action') ?></button>
      <?php endif; ?>
    </form>
  <?php endif; ?>
</div>

<?php if ($lot['status'] === 'available'): ?>
  <div class="card mt-6" style="max-width:520px;">
    <div class="card-header"><h3><?= __('lot.reserve_for_vendor_title') ?></h3></div>
    <p class="form-hint mb-4"><?= __('lot.reserve_for_vendor_hint') ?></p>
    <form method="post" action="<?= base_url('admin/lots/' . $lot['id'] . '/reserve') ?>">
      <?= csrf_field() ?>
      <?php if ($vendors): ?>
        <div class="form-group">
          <label><?= __('lot.reserve_pick_vendor') ?></label>
          <select id="reserveVendorPicker" class="form-control">
            <option value=""><?= __('lot.reserve_pick_vendor_new') ?></option>
            <?php foreach ($vendors as $v): ?>
              <option value="<?= (int) $v['id'] ?>" data-name="<?= e($v['name']) ?>" data-phone="<?= e($v['phone']) ?>" data-email="<?= e($v['email'] ?? '') ?>">
                <?= e($v['name']) ?> (<?= e($v['phone']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="form-group">
        <label><?= __('lot.reserve_vendor_name') ?></label>
        <input type="text" id="reserveVendorName" name="reserved_vendor_name" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label><?= __('lot.reserve_vendor_phone') ?></label>
          <input type="text" id="reserveVendorPhone" name="reserved_vendor_phone" class="form-control" required>
        </div>
        <div class="form-group">
          <label><?= __('lot.reserve_vendor_email') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
          <input type="email" id="reserveVendorEmail" name="reserved_vendor_email" class="form-control">
        </div>
      </div>
      <p class="form-hint mb-4"><?= __('lot.reserve_vendor_email_hint') ?></p>
      <button type="submit" class="btn btn-primary"><?= __('lot.reserve_button') ?></button>
    </form>
  </div>
  <?php if ($vendors): ?>
    <script>
    document.getElementById('reserveVendorPicker').addEventListener('change', function () {
      var opt = this.options[this.selectedIndex];
      document.getElementById('reserveVendorName').value = opt.getAttribute('data-name') || '';
      document.getElementById('reserveVendorPhone').value = opt.getAttribute('data-phone') || '';
      document.getElementById('reserveVendorEmail').value = opt.getAttribute('data-email') || '';
    });
    </script>
  <?php endif; ?>

<?php elseif ($lot['status'] === 'reserved'): ?>
  <?php
  $deadlineDays = (int) (\App\Models\Setting::get()['reserved_confirm_deadline_days'] ?? 10);
  $deadlineAt = strtotime((string) $lot['event_start_date']) - ($deadlineDays * 86400);
  ?>
  <div class="card mt-6" style="max-width:520px;">
    <div class="card-header"><h3><?= __('lot.reserve_pending_title') ?></h3></div>
    <div class="info-row"><span><?= __('lot.reserve_vendor_name') ?></span><strong><?= e($lot['reserved_vendor_name']) ?></strong></div>
    <div class="info-row"><span><?= __('lot.reserve_vendor_phone') ?></span><strong><?= e($lot['reserved_vendor_phone']) ?></strong></div>
    <div class="info-row"><span><?= __('lot.reserve_vendor_email') ?></span><strong><?= $lot['reserved_vendor_email'] ? e($lot['reserved_vendor_email']) : '—' ?></strong></div>
    <div class="info-row"><span><?= __('lot.reserve_deadline_label') ?></span><strong><?= date('d/m/Y', $deadlineAt) ?></strong></div>
    <p class="form-hint mb-4"><?= empty($lot['reserved_vendor_email']) ? __('lot.reserve_link_hint_no_email') : __('lot.reserve_link_hint') ?></p>
    <div class="form-group">
      <input type="text" class="form-control" readonly value="<?= e(full_url('reserve/' . $lot['reserved_token'])) ?>" onclick="this.select()">
    </div>
    <form method="post" action="<?= base_url('admin/lots/' . $lot['id'] . '/cancel-reservation') ?>" class="mt-4">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger btn-block" data-confirm="<?= e(__('lot.reserve_cancel_confirm')) ?>"><?= __('lot.reserve_cancel_button') ?></button>
    </form>
  </div>

<?php elseif ($lot['status'] === 'booked' && !empty($lot['reserved_confirmed_at'])): ?>
  <div class="card mt-6" style="max-width:520px;">
    <p class="text-sm text-muted mb-0">✅ <?= __('lot.reserve_confirmed_note', ['date' => date('d/m/Y H:i', strtotime((string) $lot['reserved_confirmed_at']))]) ?></p>
  </div>
<?php endif; ?>
