<div class="page-header">
  <h1><?= __('activity.title') ?></h1>
</div>

<p class="text-sm text-muted mb-4"><?= __('activity.hint') ?></p>

<?php if (!$logs): ?>
  <div class="empty-state"><div class="empty-icon"><?= icon('clock') ?></div><?= __('activity.none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><?= __('activity.when') ?></th>
          <th><?= __('activity.who') ?></th>
          <th><?= __('activity.what') ?></th>
          <th><?= __('activity.ip') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td class="text-sm text-muted" style="white-space:nowrap;"><?= e(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
            <td class="text-sm"><?= e($log['admin_name'] ?? __('activity.unknown_admin')) ?></td>
            <td class="text-sm"><?= e($log['description']) ?></td>
            <td class="text-sm text-muted"><?= e($log['ip_address'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
