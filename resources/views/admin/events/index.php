<?php use App\Services\EventStatusService; ?>
<div class="page-header">
  <h1><?= __('event.list_title') ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/create') ?>" class="btn btn-primary">+ <?= __('event.create_title') ?></a>
  </div>
</div>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th><?= __('event.singular') ?></th>
        <th><?= __('event.start_date') ?></th>
        <th><?= __('common.status') ?></th>
        <th><?= __('common.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$events): ?>
        <tr><td colspan="4" class="table-empty"><?= __('event.no_events') ?></td></tr>
      <?php endif; ?>
      <?php foreach ($events as $event): ?>
        <?php $status = EventStatusService::compute($event); ?>
        <tr>
          <td>
            <strong><?= e($event['name_th']) ?></strong>
            <?php if (!$event['is_published']): ?>
              <div class="text-sm text-muted"><?= __('event.draft_badge') ?></div>
            <?php endif; ?>
          </td>
          <td><?= e(date('d/m/Y', strtotime($event['start_date']))) ?></td>
          <td>
            <?php if ($event['is_published']): ?>
              <span class="<?= EventStatusService::badgeClass($status) ?>"><?= EventStatusService::label($status) ?></span>
            <?php else: ?>
              <span class="badge badge-slate"><?= __('event.draft_badge') ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <a href="<?= base_url('admin/events/' . $event['id'] . '/edit') ?>" class="btn btn-secondary btn-sm"><?= __('common.edit') ?></a>
              <a href="<?= base_url('admin/events/' . $event['id'] . '/lots') ?>" class="btn btn-secondary btn-sm"><?= __('event.manage_lots') ?></a>
              <a href="<?= base_url('events/' . $event['slug']) ?>" target="_blank" class="btn btn-secondary btn-sm"><?= __('event.view_public') ?></a>
              <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/delete') ?>" data-confirm="<?= e(__('event.delete_confirm')) ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger btn-sm"><?= __('common.delete') ?></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
