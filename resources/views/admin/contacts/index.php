<div class="page-header">
  <h1><?= __('contact.admin_title') ?></h1>
</div>

<?php if (!$messages): ?>
  <div class="empty-state"><div class="empty-icon">✉️</div><?= __('contact.none') ?></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><?= __('common.status') ?></th>
          <th><?= __('contact.form_name') ?></th>
          <th><?= __('contact.contact_info') ?></th>
          <th><?= __('contact.form_message') ?></th>
          <th><?= __('subscriber.created_at') ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($messages as $m): ?>
          <tr>
            <td>
              <?php if ($m['is_read']): ?>
                <span class="badge badge-slate"><?= __('contact.status_read') ?></span>
              <?php else: ?>
                <span class="badge badge-amber"><?= __('contact.status_unread') ?></span>
              <?php endif; ?>
            </td>
            <td><strong><?= e($m['name']) ?></strong></td>
            <td>
              <?php if (!empty($m['email'])): ?><div><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></div><?php endif; ?>
              <?php if (!empty($m['phone'])): ?><div><a href="tel:<?= e($m['phone']) ?>"><?= e($m['phone']) ?></a></div><?php endif; ?>
            </td>
            <td style="max-width:320px;white-space:pre-line;"><?= e($m['message']) ?></td>
            <td class="text-sm text-muted"><?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?></td>
            <td>
              <?php if (!$m['is_read']): ?>
                <form method="post" action="<?= base_url('admin/contacts/' . $m['id'] . '/read') ?>" style="margin:0;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-secondary btn-sm"><?= __('contact.mark_read') ?></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
