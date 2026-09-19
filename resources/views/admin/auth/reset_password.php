<h2 class="mt-0"><?= __('auth.reset_password_title') ?></h2>
<form method="post" action="<?= base_url('admin/reset-password/' . $token) ?>">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="password"><?= __('auth.reset_new_password') ?></label>
    <input type="password" id="password" name="password" class="form-control" minlength="8" required autofocus>
  </div>
  <div class="form-group">
    <label for="password_confirmation"><?= __('auth.reset_confirm_password') ?></label>
    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="8" required>
  </div>
  <button type="submit" class="btn btn-primary btn-block btn-lg"><?= __('auth.reset_password_button') ?></button>
</form>
