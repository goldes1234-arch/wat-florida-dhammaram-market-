<?php
/** @var array $staffUser */
/** @var array $events */
/** @var int[] $assignedEventIds */
?>
<div class="page-header">
  <h1><?= __('staff.event_access_title') ?>: <?= e($staffUser['name']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/staff') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<div class="card" style="max-width:560px;">
  <p class="form-hint mb-4"><?= __('staff.event_access_hint') ?></p>

  <form method="post" action="<?= base_url('admin/staff/' . $staffUser['id'] . '/events') ?>">
    <?= csrf_field() ?>
    <?php if (!$events): ?>
      <p class="text-sm text-muted"><?= __('event.no_events') ?></p>
    <?php else: ?>
      <div style="max-height:340px;overflow-y:auto;border:1px solid var(--color-border-light);border-radius:8px;padding:10px;">
        <?php foreach ($events as $ev): ?>
          <div class="checkbox-row mb-2">
            <input type="checkbox" name="event_ids[]" value="<?= (int) $ev['id'] ?>" id="ev<?= (int) $ev['id'] ?>"
                   <?= in_array((int) $ev['id'], $assignedEventIds, true) ? 'checked' : '' ?>>
            <label for="ev<?= (int) $ev['id'] ?>" style="margin:0;">
              <?= e($ev['name_th']) ?> <span class="text-muted text-sm">(<?= e(date('d/m/Y', strtotime($ev['start_date']))) ?>)</span>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary mt-4"><?= __('common.save') ?></button>
  </form>
</div>
