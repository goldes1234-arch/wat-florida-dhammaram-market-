<div class="page-header">
  <h1><?= __('zone.list_title') ?> — <?= e($event['name_th']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/' . $event['id'] . '/edit') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<?php if (!empty($copyableEvents)): ?>
  <div class="card mb-6">
    <div class="card-header"><h3><?= __('zone.copy_label') ?></h3></div>
    <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/zones/copy') ?>" class="form-row" style="align-items:flex-end;">
      <div class="form-group" style="flex:2 1 260px;">
        <select name="source_event_id" class="form-control">
          <option value=""><?= __('zone.copy_placeholder') ?></option>
          <?php foreach ($copyableEvents as $ce): ?>
            <option value="<?= (int) $ce['id'] ?>"><?= e($ce['name_th']) ?> (<?= e(date('d/m/Y', strtotime($ce['start_date']))) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="flex:0 0 auto;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-secondary"><?= __('zone.copy_button') ?></button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="card mb-6">
  <div class="card-header"><h3><?= __('zone.add') ?></h3></div>
  <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/zones') ?>">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('zone.name') ?></label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="form-group">
        <label><?= __('zone.default_price') ?></label>
        <input type="number" step="0.01" min="0" name="default_price" class="form-control" required>
      </div>
      <div class="form-group" style="flex:0 0 auto;align-self:flex-end;">
        <button type="submit" class="btn btn-primary"><?= __('zone.add') ?></button>
      </div>
    </div>
  </form>
</div>

<?php if (!$zones): ?>
  <div class="empty-state"><div class="empty-icon">🏷️</div><?= __('zone.none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th><?= __('zone.name') ?></th><th><?= __('zone.default_price') ?></th><th><?= __('common.actions') ?></th></tr>
      </thead>
      <tbody>
        <?php foreach ($zones as $zone): ?>
          <tr>
            <td><input type="text" name="name" form="zone-form-<?= (int) $zone['id'] ?>" class="form-control" value="<?= e($zone['name']) ?>"></td>
            <td><input type="number" step="0.01" min="0" name="default_price" form="zone-form-<?= (int) $zone['id'] ?>" class="form-control" value="<?= e((string) $zone['default_price']) ?>"></td>
            <td style="display:flex;gap:8px;">
              <button type="submit" form="zone-form-<?= (int) $zone['id'] ?>" class="btn btn-secondary btn-sm"><?= __('common.save') ?></button>
              <form method="post" action="<?= base_url('admin/zones/' . $zone['id'] . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger btn-sm"><?= __('common.delete') ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php foreach ($zones as $zone): ?>
    <form id="zone-form-<?= (int) $zone['id'] ?>" method="post" action="<?= base_url('admin/zones/' . $zone['id']) ?>" style="display:none;">
      <?= csrf_field() ?>
    </form>
  <?php endforeach; ?>
<?php endif; ?>
