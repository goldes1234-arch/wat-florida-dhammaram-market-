<div class="page-header">
  <h1><?= __('event.create_title') ?></h1>
</div>
<?= \App\Core\View::renderToString('admin/events/_form', [
    'event' => null,
    'contacts' => $contacts,
    'copyableEvents' => $copyableEvents,
    'action' => base_url('admin/events'),
    'submitLabel' => __('common.create'),
]) ?>
