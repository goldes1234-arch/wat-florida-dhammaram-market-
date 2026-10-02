<?php
/** @var int $page */
/** @var int $totalPages */
/** @var string $path base admin path, e.g. 'admin/bookings' */
/** @var array $query other filter/search params to preserve across page links */
$query = $query ?? [];
?>
<?php if ($totalPages > 1): ?>
  <nav class="pagination" aria-label="<?= e(__('common.page_of', ['page' => (string) $page, 'total' => (string) $totalPages])) ?>">
    <?php if ($page > 1): ?>
      <a class="btn btn-secondary btn-sm" href="<?= base_url($path . '?' . http_build_query(array_merge($query, ['page' => $page - 1]))) ?>"><?= __('common.prev') ?></a>
    <?php else: ?>
      <span class="btn btn-secondary btn-sm is-disabled"><?= __('common.prev') ?></span>
    <?php endif; ?>

    <span class="pagination-status"><?= __('common.page_of', ['page' => (string) $page, 'total' => (string) $totalPages]) ?></span>

    <?php if ($page < $totalPages): ?>
      <a class="btn btn-secondary btn-sm" href="<?= base_url($path . '?' . http_build_query(array_merge($query, ['page' => $page + 1]))) ?>"><?= __('common.next') ?></a>
    <?php else: ?>
      <span class="btn btn-secondary btn-sm is-disabled"><?= __('common.next') ?></span>
    <?php endif; ?>
  </nav>
<?php endif; ?>
