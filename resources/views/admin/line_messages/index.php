<?php
/** @var array $vendors */
/** @var bool $lineEnabled */
?>
<div class="page-header">
  <h1><?= __('line_message.title') ?></h1>
</div>

<?php if (!$lineEnabled): ?>
  <div class="card" style="max-width:560px;">
    <p class="form-hint mb-0"><?= __('line_message.line_not_configured') ?> <a href="<?= base_url('admin/settings') ?>"><?= __('nav.settings') ?></a></p>
  </div>
<?php elseif (!$vendors): ?>
  <div class="card" style="max-width:560px;">
    <div class="empty-state"><div class="empty-icon">💬</div><?= __('line_message.no_linked_vendors') ?></div>
  </div>
<?php else: ?>
  <div class="card" style="max-width:560px;">
    <p class="form-hint mb-4"><?= __('line_message.hint') ?></p>
    <form method="post" action="<?= base_url('admin/line-messages') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label><?= __('line_message.select_vendors') ?></label>
        <div style="max-height:260px;overflow-y:auto;border:1px solid var(--color-border-light);border-radius:8px;padding:10px;">
          <?php foreach ($vendors as $vendor): ?>
            <div class="checkbox-row mb-2">
              <input type="checkbox" name="vendor_ids[]" value="<?= (int) $vendor['id'] ?>" id="v<?= (int) $vendor['id'] ?>">
              <label for="v<?= (int) $vendor['id'] ?>" style="margin:0;"><?= e($vendor['name']) ?> (<?= e($vendor['phone']) ?>)</label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-group">
        <label><?= __('line_message.message_label') ?></label>
        <textarea name="message" class="form-control" rows="4" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><?= __('line_message.send_button') ?></button>
    </form>
  </div>
<?php endif; ?>
