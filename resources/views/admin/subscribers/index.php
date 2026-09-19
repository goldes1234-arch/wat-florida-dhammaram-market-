<div class="page-header">
  <h1><?= __('subscriber.title') ?> — <?= e($event['name_th']) ?></h1>
  <div class="header-actions">
    <a href="<?= base_url('admin/events/' . $event['id'] . '/edit') ?>" class="btn btn-secondary">&larr; <?= __('common.back') ?></a>
  </div>
</div>

<?php if (!$subscribers): ?>
  <div class="empty-state"><div class="empty-icon">🔔</div><?= __('subscriber.none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><?= __('subscriber.email') ?></th>
          <th><?= __('subscriber.phone') ?></th>
          <th><?= __('subscriber.created_at') ?></th>
          <th><?= __('common.status') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subscribers as $s): ?>
          <tr>
            <td><?= e($s['email'] ?: '—') ?></td>
            <td><?= e($s['phone'] ?: '—') ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime($s['created_at']))) ?></td>
            <td>
              <?php if ($s['notified_at']): ?>
                <span class="badge badge-green"><?= __('subscriber.notified') ?></span>
              <?php else: ?>
                <span class="badge badge-slate"><?= __('subscriber.not_notified') ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<h2 class="section-title mt-6"><?= icon('bell') ?> <?= __('subscriber.waitlist_title') ?></h2>
<?php if (!$waitlistEntries): ?>
  <div class="empty-state"><div class="empty-icon"><?= icon('bell') ?></div><?= __('subscriber.waitlist_none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><?= __('booking.booker_name') ?></th>
          <th><?= __('booking.booker_phone') ?></th>
          <th><?= __('booking.booker_email') ?></th>
          <th><?= __('subscriber.created_at') ?></th>
          <th><?= __('common.status') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($waitlistEntries as $w): ?>
          <tr>
            <td><?= e($w['name']) ?></td>
            <td><?= e($w['phone']) ?></td>
            <td><?= e($w['email'] ?: '—') ?></td>
            <td><?= e(date('d/m/Y H:i', strtotime($w['created_at']))) ?></td>
            <td>
              <?php if ($w['notified_at']): ?>
                <span class="badge badge-green"><?= __('subscriber.notified') ?></span>
              <?php else: ?>
                <span class="badge badge-slate"><?= __('subscriber.not_notified') ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
