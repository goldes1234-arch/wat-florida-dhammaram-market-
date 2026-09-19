<h2 class="mt-0"><?= __('auth.login_title') ?></h2>
<form method="post" action="<?= base_url('admin/login') ?>">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="email"><?= __('auth.email') ?></label>
    <input type="email" id="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required autofocus>
  </div>
  <div class="form-group">
    <label for="password"><?= __('auth.password') ?></label>
    <input type="password" id="password" name="password" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary btn-block btn-lg"><?= __('auth.login_button') ?></button>
</form>
