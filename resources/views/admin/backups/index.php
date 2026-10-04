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

<?php
use App\Services\GoogleDriveService;
$drive = \App\Models\Setting::get(true);
$driveConfigured = GoogleDriveService::isConfigured();
$driveConnected = GoogleDriveService::isConnected();
?>
<div class="card mb-6">
  <div class="card-header"><h3>☁️ <?= __('gdrive.title') ?></h3></div>
  <?php if (!$driveConfigured): ?>
    <p class="text-sm mb-2"><?= __('gdrive.not_configured') ?></p>
    <p class="text-sm mb-2"><?= __('gdrive.redirect_uri_label') ?></p>
    <code style="display:block;background:#fff;padding:8px 12px;border-radius:6px;font-size:12px;word-break:break-all;"><?= e(GoogleDriveService::redirectUri()) ?></code>
  <?php elseif (!$driveConnected): ?>
    <p class="text-sm mb-4"><?= __('gdrive.not_connected') ?></p>
    <a href="<?= base_url('admin/backups/google/connect') ?>" class="btn btn-primary"><?= __('gdrive.connect_button') ?></a>
  <?php else: ?>
    <p class="text-sm mb-2">✅ <?= __('gdrive.connected_as', ['account' => $drive['gdrive_account'] ?: '—']) ?></p>
    <p class="text-sm text-muted mb-2">
      <?= !empty($drive['gdrive_last_upload_at'])
          ? __('gdrive.last_upload', ['date' => date('m/d/Y H:i', strtotime($drive['gdrive_last_upload_at']))])
          : __('gdrive.never_uploaded') ?>
      · <?= __('gdrive.keep_note', ['keep' => (string) (int) \App\Core\App::config('google.keep')]) ?>
    </p>
    <?php if (!empty($drive['gdrive_last_error'])): ?>
      <div class="alert alert-error mb-4"><?= __('gdrive.last_error', ['error' => $drive['gdrive_last_error']]) ?></div>
    <?php endif; ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <form method="post" action="<?= base_url('admin/backups/google/upload-latest') ?>" style="margin:0;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-secondary btn-sm"><?= __('gdrive.upload_now_button') ?></button>
      </form>
      <form method="post" action="<?= base_url('admin/backups/google/disconnect') ?>" style="margin:0;" data-confirm="<?= e(__('gdrive.disconnect_confirm')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger btn-sm"><?= __('gdrive.disconnect_button') ?></button>
      </form>
    </div>
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
            <td class="text-sm text-muted"><?= e(date('m/d/Y H:i', $b['created_at'])) ?></td>
            <td>
              <a href="<?= base_url('admin/backups/' . urlencode($b['filename']) . '/download') ?>" class="btn btn-secondary btn-sm"><?= __('backup.download') ?></a>
              <button type="submit" form="backup-verify-<?= e($b['filename']) ?>" class="btn btn-secondary btn-sm"><?= __('backup.verify_button') ?></button>
              <button
                type="button"
                class="btn btn-danger btn-sm backup-restore-trigger"
                data-form="backup-restore-<?= e($b['filename']) ?>"
                data-filename="<?= e($b['filename']) ?>"
                data-prompt="<?= e(__('backup.restore_prompt', ['filename' => $b['filename']])) ?>"
              ><?= __('backup.restore_button') ?></button>
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
  <form id="backup-verify-<?= e($b['filename']) ?>" method="post" action="<?= base_url('admin/backups/' . urlencode($b['filename']) . '/verify') ?>" style="display:none;"><?= csrf_field() ?></form>
  <form id="backup-restore-<?= e($b['filename']) ?>" method="post" action="<?= base_url('admin/backups/' . urlencode($b['filename']) . '/restore') ?>" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="confirm_filename" value="">
  </form>
<?php endforeach; ?>

<script>
(function () {
  // Restoring overwrites the live database, so a plain confirm() isn't enough friction —
  // the admin has to type the exact filename back before the form is even submitted.
  document.querySelectorAll('.backup-restore-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var typed = window.prompt(btn.getAttribute('data-prompt'));
      if (typed === null || typed !== btn.getAttribute('data-filename')) {
        return;
      }
      var form = document.getElementById(btn.getAttribute('data-form'));
      form.querySelector('input[name="confirm_filename"]').value = typed;
      form.submit();
    });
  });
})();
</script>

<div class="page-header mt-6">
  <h1><?= __('backup.migrations_title') ?></h1>
</div>
<p class="text-sm text-muted mb-4"><?= __('backup.migrations_hint') ?></p>

<?php if (!$migrations): ?>
  <div class="empty-state"><div class="empty-icon"><?= icon('clock') ?></div><?= __('backup.migrations_none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><?= __('backup.migration_filename') ?></th>
          <th><?= __('backup.migration_applied_at') ?></th>
          <th><?= __('common.actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($migrations as $i => $m): ?>
          <tr>
            <td class="text-sm"><?= e($m['filename']) ?></td>
            <td class="text-sm text-muted"><?= e(date('m/d/Y H:i', strtotime($m['applied_at']))) ?></td>
            <td>
              <?php if ($i === 0 && $m['rollback_available']): ?>
                <button type="submit" form="migration-rollback-form" class="btn btn-danger btn-sm"><?= __('backup.rollback_button') ?></button>
              <?php elseif ($i === 0): ?>
                <span class="text-sm text-muted"><?= __('backup.rollback_unavailable') ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <form id="migration-rollback-form" method="post" action="<?= base_url('admin/migrations/rollback') ?>" data-confirm="<?= e(__('backup.rollback_confirm')) ?>" style="display:none;"><?= csrf_field() ?></form>
<?php endif; ?>
