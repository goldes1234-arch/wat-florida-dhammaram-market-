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
        <label><?= __('staff.phone') ?></label>
        <input type="text" name="phone" class="form-control">
      </div>
      <div class="form-group">
        <label><?= __('staff.password') ?></label>
        <input type="password" name="password" class="form-control" minlength="8" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= __('staff.role') ?></label>
        <select name="role" class="form-control">
          <option value="staff"><?= __('staff.role_staff') ?></option>
          <option value="checkin"><?= __('staff.role_checkin') ?></option>
          <option value="finance"><?= __('staff.role_finance') ?></option>
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
        <th><?= __('staff.phone') ?></th>
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
          <td class="text-sm"><?= $u['phone'] ? e($u['phone']) : '<span class="text-muted">' . __('staff.phone_none') . '</span>' ?></td>
          <td><span class="badge badge-indigo"><?= __('staff.role_' . $u['role']) ?></span></td>
          <td class="text-sm text-muted"><?= $u['last_login_at'] ? e(date('m/d/Y H:i', strtotime($u['last_login_at']))) : __('staff.never_logged_in') ?></td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="badge badge-green"><?= __('staff.active') ?></span>
            <?php else: ?>
              <span class="badge badge-slate"><?= __('staff.inactive') ?></span>
            <?php endif; ?>
          </td>
          <td style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php if ($u['role'] === 'staff'): ?>
              <a href="<?= base_url('admin/staff/' . $u['id'] . '/events') ?>" class="btn btn-secondary btn-sm"><?= __('staff.manage_event_access') ?></a>
            <?php endif; ?>
            <form method="post" action="<?= base_url('admin/staff/' . $u['id'] . '/toggle-active') ?>" style="margin:0;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-secondary btn-sm"><?= $u['is_active'] ? __('staff.inactive') : __('staff.active') ?></button>
            </form>
            <button type="button" class="btn btn-secondary btn-sm" data-toggle="#pw-form-<?= $u['id'] ?>"><?= __('staff.set_password_button') ?></button>
            <button type="button" class="btn btn-secondary btn-sm" data-toggle="#phone-form-<?= $u['id'] ?>"><?= __('staff.edit_phone_button') ?></button>
            <?php if ($u['role'] === 'checkin'): ?>
              <form method="post" action="<?= base_url('admin/staff/' . $u['id'] . '/checkin-link/generate') ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm"><?= __('staff.checkin_link_generate_button') ?></button>
              </form>
              <?php if (!empty($u['checkin_link_token_hash'])): ?>
                <form method="post" action="<?= base_url('admin/staff/' . $u['id'] . '/checkin-link/revoke') ?>" data-confirm="<?= e(__('staff.checkin_link_revoke_confirm')) ?>" style="margin:0;">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-secondary btn-sm"><?= __('staff.checkin_link_revoke_button') ?></button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
            <form method="post" action="<?= base_url('admin/staff/' . $u['id'] . '/delete') ?>" data-confirm="<?= e(__('staff.delete_confirm')) ?>" style="margin:0;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-sm"><?= __('staff.delete_button') ?></button>
            </form>
          </td>
        </tr>
        <tr id="pw-form-<?= $u['id'] ?>" class="staff-pw-row">
          <td colspan="7">
            <form method="post" action="<?= base_url('admin/staff/' . $u['id'] . '/set-password') ?>" style="display:flex;gap:8px;align-items:center;">
              <?= csrf_field() ?>
              <label style="margin:0;"><?= __('staff.new_password') ?></label>
              <input type="password" name="password" class="form-control" minlength="8" required style="max-width:260px;">
              <button type="submit" class="btn btn-primary btn-sm"><?= __('staff.set_password_button') ?></button>
            </form>
          </td>
        </tr>
        <tr id="phone-form-<?= $u['id'] ?>" class="staff-pw-row">
          <td colspan="7">
            <form method="post" action="<?= base_url('admin/staff/' . $u['id'] . '/phone') ?>" style="display:flex;gap:8px;align-items:center;">
              <?= csrf_field() ?>
              <label style="margin:0;"><?= __('staff.phone') ?></label>
              <input type="text" name="phone" class="form-control" value="<?= e($u['phone'] ?? '') ?>" style="max-width:260px;">
              <button type="submit" class="btn btn-primary btn-sm"><?= __('staff.edit_phone_button') ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
