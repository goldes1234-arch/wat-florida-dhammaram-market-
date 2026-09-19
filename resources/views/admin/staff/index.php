<div class="page-header">
  <h1><?= __('staff.list_title') ?></h1>
</div>

<div class="card mb-6">
  <div class="card-header"><h3><?= __('staff.add_title') ?></h3></div>
  <form method="post" action="<?= base_url('admin/staff') ?>">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('staff.name') ?></label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="form-group">
        <label><?= __('staff.email') ?></label>
        <input type="email" name="email" class="form-control" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('staff.password') ?></label>
        <input type="password" name="password" class="form-control" minlength="8" required>
      </div>
      <div class="form-group">
        <label><?= __('staff.role') ?></label>
        <select name="role" class="form-control">
          <option value="staff"><?= __('staff.role_staff') ?></option>
          <option value="checkin"><?= __('staff.role_checkin') ?></option>
          <option value="super_admin"><?= __('staff.role_super_admin') ?></option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= __('staff.add_title') ?></button>
  </form>
</div>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th><?= __('staff.name') ?></th>
        <th><?= __('staff.email') ?></th>
        <th><?= __('staff.role') ?></th>
        <th><?= __('staff.last_login') ?></th>
        <th><?= __('staff.status') ?></th>
        <th><?= __('common.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge badge-indigo"><?= __('staff.role_' . $u['role']) ?></span></td>
          <td class="text-sm text-muted"><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : __('staff.never_logged_in') ?></td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="badge badge-green"><?= __('staff.active') ?></span>
            <?php else: ?>
              <span class="badge badge-slate"><?= __('staff.inactive') ?></span>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" action="<?= base_url('admin/staff/' . $u['id'] . '/toggle-active') ?>" style="margin:0;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-secondary btn-sm"><?= $u['is_active'] ? __('staff.inactive') : __('staff.active') ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
