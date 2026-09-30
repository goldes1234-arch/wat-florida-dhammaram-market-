<?php
/** @var array $category */
/** @var array $files */
?>
<div class="container-narrow" style="padding:0;">
  <div class="page-header">
    <h1><?= e($category['name']) ?></h1>
  </div>

  <?php if (!$files): ?>
    <div class="empty-state"><div class="empty-icon">📥</div><?= __('downloads.empty_state') ?></div>
  <?php else: ?>
    <div class="card">
      <div class="download-file-list">
        <?php foreach ($files as $file): ?>
          <a href="<?= upload_url($file['file_path']) ?>" class="download-file-row" download>
            <span class="download-file-icon"><?= icon('download') ?></span>
            <span class="download-file-info">
              <span class="download-file-title"><?= e($file['title']) ?></span>
              <?php if ($file['file_size']): ?>
                <span class="download-file-size"><?= number_format($file['file_size'] / 1024 / 1024, 2) ?> MB</span>
              <?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
