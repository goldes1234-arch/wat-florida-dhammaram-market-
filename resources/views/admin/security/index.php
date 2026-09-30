<?php /** @var array $user */ ?>
<div class="page-header">
  <h1><?= __('security.title') ?></h1>
</div>

<div class="card" style="max-width:520px;">
  <div class="card-header"><h3><?= __('security.2fa_title') ?></h3></div>
  <?php if (!empty($user['totp_enabled'])): ?>
    <p class="text-sm mb-4">✅ <?= __('security.2fa_enabled_hint') ?></p>
    <form method="post" action="<?= base_url('admin/security/disable') ?>" data-confirm="<?= e(__('security.disable_confirm')) ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger"><?= __('security.disable_button') ?></button>
    </form>
  <?php else: ?>
    <p class="form-hint mb-4"><?= __('security.2fa_disabled_hint') ?></p>
    <a href="<?= base_url('admin/security/enroll') ?>" class="btn btn-primary"><?= __('security.enroll_button') ?></a>
  <?php endif; ?>
</div>
