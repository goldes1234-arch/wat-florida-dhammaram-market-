<div class="page-header">
  <h1><?= e($event['name_th']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/' . $event['id'] . '/zones') ?>" class="btn btn-secondary"><?= __('event.manage_zones') ?></a>
    <a href="<?= base_url('admin/events/' . $event['id'] . '/lots') ?>" class="btn btn-secondary"><?= __('event.manage_lots') ?></a>
    <a href="<?= base_url('admin/events/' . $event['id'] . '/subscribers') ?>" class="btn btn-secondary"><?= __('event.view_subscribers') ?></a>
    <a href="<?= base_url('events/' . $event['slug']) ?>" target="_blank" class="btn btn-secondary"><?= __('event.view_public') ?></a>
  </div>
</div>
<?= \App\Core\View::renderToString('admin/events/_form', [
    'event' => $event,
    'contacts' => $contacts,
    'copyableEvents' => $copyableEvents,
    'eventPhotos' => $eventPhotos,
    'action' => base_url('admin/events/' . $event['id']),
    'submitLabel' => __('common.save'),
]) ?>
