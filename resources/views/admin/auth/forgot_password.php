<h2 class="mt-0"><?= __('auth.forgot_password_title') ?></h2>
<p class="text-sm text-muted mb-4"><?= __('auth.forgot_password_hint') ?></p>
<form method="post" action="<?= base_url('admin/forgot-password') ?>">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="email"><?= __('auth.email') ?></label>
    <input type="email" id="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required autofocus>
  </div>
  <button type="submit" class="btn btn-primary btn-block btn-lg"><?= __('auth.send_reset_link_button') ?></button>
</form>
<p class="text-center text-sm mt-4"><a href="<?= base_url('admin/login') ?>">&larr; <?= __('auth.back_to_login') ?></a></p>
