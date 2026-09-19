<?php $__messages = flash_messages(); ?>
<?php foreach (($__messages['success'] ?? []) as $msg): ?>
    <div class="alert alert-success" data-autodismiss>✅ <span><?= e($msg) ?></span></div>
<?php endforeach; ?>
<?php foreach (($__messages['error'] ?? []) as $msg): ?>
    <div class="alert alert-error" data-autodismiss>⚠️ <span><?= e($msg) ?></span></div>
<?php endforeach; ?>
