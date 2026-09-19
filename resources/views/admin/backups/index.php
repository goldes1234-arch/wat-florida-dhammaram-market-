<div class="page-header">
  <h1><?= __('backup.title') ?></h1>
  <div class="header-actions">
    <form method="post" action="<?= base_url('admin/backups') ?>" style="margin:0;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-primary"><?= icon('download') ?> <?= __('backup.create_button') ?></button>
    </form>
  </div>
</div>

<p class="text-sm text-muted mb-4"><?= __('backup.hint', ['retention' => (int) \App\Core\App::config('backup.retention')]) ?></p>

<?php $cronSecret = \App\Core\App::config('backup.cron_secret'); ?>
<div class="card mb-6" style="border-color:var(--color-warning-light);background:var(--color-accent-light);">
  <?php if (!$cronSecret): ?>
    <p class="mb-0 text-sm"><?= __('backup.cron_not_configured') ?></p>
  <?php else: ?>
    <p class="text-sm mb-2"><?= __('backup.cron_setup_hint') ?></p>
    <code style="display:block;background:#fff;padding:8px 12px;border-radius:6px;font-size:12px;word-break:break-all;"><?= e(full_url('cron/backup')) ?>?token=<?= e($cronSecret) ?></code>
  <?php endif; ?>
</div>

<?php if (!$backups): ?>
  <div class="empty-state"><div class="empty-icon"><?= icon('download') ?></div><?= __('backup.none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><?= __('backup.filename') ?></th>
          <th><?= __('backup.size') ?></th>
          <th><?= __('backup.created_at') ?></th>
          <th><?= __('common.actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($backups as $b): ?>
          <tr>
            <td class="text-sm"><?= e($b['filename']) ?></td>
            <td class="text-sm text-muted"><?= e(number_format($b['size'] / 1024 / 1024, 2)) ?> MB</td>
            <td class="text-sm text-muted"><?= e(date('d/m/Y H:i', $b['created_at'])) ?></td>
            <td>
              <a href="<?= base_url('admin/backups/' . urlencode($b['filename']) . '/download') ?>" class="btn btn-secondary btn-sm"><?= __('backup.download') ?></a>
              <button type="submit" form="backup-delete-<?= e($b['filename']) ?>" class="btn btn-danger btn-sm"><?= __('common.delete') ?></button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php foreach ($backups as $b): ?>
  <form id="backup-delete-<?= e($b['filename']) ?>" method="post" action="<?= base_url('admin/backups/' . urlencode($b['filename']) . '/delete') ?>" data-confirm="<?= e(__('zone.delete_confirm')) ?>" style="display:none;"><?= csrf_field() ?></form>
<?php endforeach; ?>
