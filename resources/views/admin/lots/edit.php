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
