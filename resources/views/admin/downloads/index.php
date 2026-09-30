<?php
/** @var array $categories */
/** @var array $filesByCategory */
?>
<div class="page-header">
  <h1><?= __('downloads.title') ?></h1>
</div>

<div class="card mb-6" style="max-width:480px;">
  <div class="card-header"><h3><?= __('downloads.add_category_title') ?></h3></div>
  <form method="post" action="<?= base_url('admin/downloads/categories') ?>">
    <?= csrf_field() ?>
    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <input type="text" name="name" class="form-control" placeholder="<?= e(__('downloads.category_name_placeholder')) ?>" required>
      </div>
      <div class="form-group" style="flex:0 0 auto;">
        <button type="submit" class="btn btn-primary"><?= __('downloads.add_category_button') ?></button>
      </div>
    </div>
  </form>
</div>

<?php if (!$categories): ?>
  <div class="empty-state"><div class="empty-icon">📥</div><?= __('downloads.no_categories') ?></div>
<?php else: ?>
  <?php foreach ($categories as $category): ?>
    <?php $files = $filesByCategory[$category['id']] ?? []; ?>
    <div class="card mb-6">
      <div class="card-header">
        <h3><?= e($category['name']) ?> <span class="text-sm text-muted">(<?= __('downloads.file_count', ['count' => (int) $category['file_count']]) ?>)</span></h3>
        <form method="post" action="<?= base_url('admin/downloads/categories/' . $category['id'] . '/delete') ?>" data-confirm="<?= e(__('downloads.delete_category_confirm')) ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger btn-sm"><?= __('common.delete') ?></button>
        </form>
      </div>

      <?php if ($files): ?>
        <div class="table-wrap mb-4">
          <table class="table">
            <thead>
              <tr>
                <th><?= __('downloads.file_title_placeholder') ?></th>
                <th></th>
                <th><?= __('common.actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($files as $file): ?>
                <tr>
                  <td><?= e($file['title']) ?></td>
                  <td class="text-sm text-muted"><?= $file['file_size'] ? number_format($file['file_size'] / 1024 / 1024, 2) . ' MB' : '' ?></td>
                  <td>
                    <form method="post" action="<?= base_url('admin/downloads/files/' . $file['id'] . '/delete') ?>" data-confirm="<?= e(__('downloads.delete_file_confirm')) ?>" style="margin:0;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-danger btn-sm"><?= __('common.delete') ?></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-sm text-muted mb-4"><?= __('downloads.no_files') ?></p>
      <?php endif; ?>

      <form method="post" action="<?= base_url('admin/downloads/files') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
        <div class="form-row" style="align-items:flex-end;">
          <div class="form-group">
            <input type="text" name="title" class="form-control" placeholder="<?= e(__('downloads.file_title_placeholder')) ?>" required>
          </div>
          <div class="form-group">
            <input type="file" name="file" class="form-control" accept=".pdf,.mp3,.m4a,.wav,application/pdf,audio/*" required>
          </div>
          <div class="form-group" style="flex:0 0 auto;">
            <button type="submit" class="btn btn-secondary"><?= __('downloads.add_file_button') ?></button>
          </div>
        </div>
        <p class="form-hint mb-0"><?= __('downloads.file_hint') ?></p>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
