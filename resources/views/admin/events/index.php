<?php
use App\Services\EventStatusService;

/** @var array $events */
/** @var array $pastEvents */
/** @var array<int,array{available:int,total:int}> $lotCounts */

$renderRows = static function (array $list) use ($lotCounts): void {
    foreach ($list as $event) {
        $status = EventStatusService::compute($event);
        $lc = $lotCounts[(int) $event['id']] ?? ['available' => 0, 'total' => 0];
        $taken = max(0, $lc['total'] - $lc['available']);
        $pct = $lc['total'] > 0 ? (int) round($taken / $lc['total'] * 100) : 0;
        ?>
        <tr>
          <td data-label="<?= e(__('event.singular')) ?>">
            <strong><?= e($event['name_th']) ?></strong>
            <?php if (!$event['is_published']): ?>
              <div class="text-sm text-muted"><?= __('event.draft_badge') ?></div>
            <?php endif; ?>
          </td>
          <td data-label="<?= e(__('event.start_date')) ?>"><?= e(date('m/d/Y', strtotime($event['start_date']))) ?><?= $event['end_date'] !== $event['start_date'] ? ' – ' . e(date('m/d/Y', strtotime($event['end_date']))) : '' ?></td>
          <td data-label="<?= e(__('common.status')) ?>">
            <?php if ($event['is_published']): ?>
              <span class="<?= EventStatusService::badgeClass($status) ?>"><?= EventStatusService::label($status) ?></span>
            <?php else: ?>
              <span class="badge badge-slate"><?= __('event.draft_badge') ?></span>
            <?php endif; ?>
          </td>
          <td data-label="<?= e(__('event.lots_progress_label')) ?>">
            <?php if ($lc['total'] > 0): ?>
              <div class="lots-progress" title="<?= e(__('event.lots_progress', ['taken' => (string) $taken, 'total' => (string) $lc['total']])) ?>">
                <div class="lots-progress-bar"><span style="width:<?= $pct ?>%"></span></div>
                <span class="text-sm"><?= e(__('event.lots_progress', ['taken' => (string) $taken, 'total' => (string) $lc['total']])) ?></span>
              </div>
            <?php else: ?>
              <span class="text-muted text-sm">—</span>
            <?php endif; ?>
          </td>
          <td class="cell-actions">
            <a href="<?= base_url('admin/events/' . $event['id'] . '/edit') ?>" class="btn btn-secondary btn-sm"><?= __('common.edit') ?></a>
            <a href="<?= base_url('admin/events/' . $event['id'] . '/lots') ?>" class="btn btn-secondary btn-sm"><?= __('event.manage_lots') ?></a>
            <details class="menu-more menu-more-sm">
              <summary class="btn btn-secondary btn-sm" aria-label="<?= e(__('common.more_actions')) ?>">&hellip;</summary>
              <div class="menu-more-panel">
                <a href="<?= base_url('events/' . $event['slug']) ?>" target="_blank" class="menu-more-link"><?= __('event.view_public') ?></a>
                <form method="post" action="<?= base_url('admin/events/' . $event['id'] . '/delete') ?>" data-confirm="<?= e(__('event.delete_confirm')) ?>" style="margin:0;">
                  <?= csrf_field() ?>
                  <button type="submit" class="menu-more-danger"><?= __('common.delete') ?></button>
                </form>
              </div>
            </details>
          </td>
        </tr>
        <?php
    }
};
?>
<div class="page-header">
  <h1><?= __('event.list_title') ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/create') ?>" class="btn btn-primary">+ <?= __('event.create_title') ?></a>
  </div>
</div>

<div class="table-wrap">
  <table class="table table-cards">
    <thead>
      <tr>
        <th><?= __('event.singular') ?></th>
        <th><?= __('event.start_date') ?></th>
        <th><?= __('common.status') ?></th>
        <th><?= __('event.lots_progress_label') ?></th>
        <th><?= __('common.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$events): ?>
        <tr><td colspan="5" class="table-empty"><?= __('event.no_events') ?></td></tr>
      <?php endif; ?>
      <?php $renderRows($events); ?>
    </tbody>
  </table>
</div>

<?php if ($pastEvents): ?>
  <details class="past-events-admin mt-6">
    <summary><?= __('event.past_events', ['count' => (string) count($pastEvents)]) ?></summary>
    <div class="table-wrap mt-4">
      <table class="table table-cards">
        <tbody><?php $renderRows($pastEvents); ?></tbody>
      </table>
    </div>
  </details>
<?php endif; ?>
