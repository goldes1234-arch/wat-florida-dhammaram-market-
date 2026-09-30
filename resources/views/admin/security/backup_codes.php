<?php /** @var string[] $codes */ ?>
<div class="page-header">
  <h1><?= __('security.backup_codes_title') ?></h1>
</div>

<div class="card" style="max-width:520px;">
  <p class="text-sm mb-4">✅ <?= __('security.enabled_success') ?></p>
  <p class="form-hint mb-4"><?= __('security.backup_codes_hint') ?></p>

  <div style="background:var(--color-slate-light);border:1px solid var(--color-border-light);border-radius:8px;padding:16px;font-family:monospace;font-size:16px;line-height:2;text-align:center;">
    <?php foreach ($codes as $code): ?>
      <div><?= e($code) ?></div>
    <?php endforeach; ?>
  </div>

  <a href="<?= base_url('admin/security') ?>" class="btn btn-primary btn-block mt-6"><?= __('security.backup_codes_saved_button') ?></a>
</div>
