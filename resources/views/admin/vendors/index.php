<?php
/** @var array $vendors */
/** @var string $search */
?>
<div class="page-header">
  <h1><?= __('nav.vendors') ?></h1>
</div>

<form method="get" action="<?= base_url('admin/vendors') ?>" class="filter-bar">
  <div class="form-group">
    <label><?= __('vendor.search_label') ?></label>
    <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="<?= e(__('vendor.search_placeholder')) ?>">
  </div>
  <div class="form-group" style="flex:0 0 auto;align-self:flex-end;">
    <button type="submit" class="btn btn-secondary"><?= __('common.search') ?></button>
  </div>
</form>

<details class="card add-vendor mb-6" style="max-width:520px;"<?= (!$vendors && $search === '') ? ' open' : '' ?>>
  <summary class="btn btn-primary">+ <?= __('vendor.add_title') ?></summary>
  <div class="add-vendor-body">
  <form method="post" action="<?= base_url('admin/vendors') ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label><?= __('vendor.name') ?></label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('vendor.phone') ?></label>
        <input type="text" name="phone" class="form-control" required>
      </div>
      <div class="form-group">
        <label><?= __('vendor.email') ?> <span class="optional-tag">(<?= __('common.optional') ?>)</span></label>
        <input type="email" name="email" class="form-control">
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= __('vendor.add_button') ?></button>
  </form>
</div>
</details>

<?php if (!$vendors): ?>
  <div class="empty-state"><div class="empty-icon">🧑‍🌾</div><?= __('vendor.none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><?= __('vendor.name') ?></th>
          <th><?= __('vendor.phone') ?></th>
          <th><?= __('vendor.email') ?></th>
          <th><?= __('vendor.booking_count') ?></th>
          <th>LINE</th>
          <th><?= __('common.actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vendors as $vendor): ?>
          <tr>
            <td>
              <strong><?= e($vendor['name']) ?></strong>
              <?php if (!empty($vendor['deletion_requested_at'])): ?>
                <span class="badge badge-red" title="<?= e(__('vendor.deletion_requested_at', ['date' => date('d/m/Y H:i', strtotime($vendor['deletion_requested_at']))])) ?>">🗑️ <?= __('vendor.deletion_requested_badge') ?></span>
              <?php endif; ?>
            </td>
            <td><?= e($vendor['phone']) ?></td>
            <td><?= e($vendor['email'] ?? '—') ?></td>
            <td><span class="badge badge-indigo"><?= (int) $vendor['booking_count'] ?></span></td>
            <td>
              <?php if (!empty($vendor['line_user_id'])): ?><span class="badge badge-green">✓</span><?php endif; ?>
              <?php if (!empty($vendor['line_pending_user_id'])): ?><span class="badge badge-amber" title="<?= e(__('vendor.line_link_pending_tooltip')) ?>">⏳ <?= __('vendor.line_link_pending_badge') ?></span><?php endif; ?>
              <?php if (empty($vendor['line_user_id']) && empty($vendor['line_pending_user_id'])): ?>—<?php endif; ?>
            </td>
            <td><a href="<?= base_url('admin/vendors/' . $vendor['id']) ?>" class="btn btn-secondary btn-sm"><?= __('common.view') ?></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?= partial('pagination', [
    'page' => $page,
    'totalPages' => $totalPages,
    'path' => 'admin/vendors',
    'query' => ['q' => $search],
  ]) ?>
<?php endif; ?>
