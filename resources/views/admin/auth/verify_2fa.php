<h2 class="mt-0"><?= __('auth.verify_2fa_title') ?></h2>
<p class="form-hint mb-4"><?= __('auth.verify_2fa_hint') ?></p>
<form method="post" action="<?= base_url('admin/login/verify-2fa') ?>">
  <?= csrf_field() ?>
  <div class="form-group">
    <label for="code"><?= __('auth.verify_2fa_code_label') ?></label>
    <input type="text" id="code" name="code" class="form-control" style="font-size:22px;text-align:center;letter-spacing:4px;"
           inputmode="numeric" autocomplete="one-time-code" maxlength="8" required autofocus>
  </div>
  <button type="submit" class="btn btn-primary btn-block btn-lg"><?= __('auth.verify_2fa_button') ?></button>
</form>
<p class="text-center text-sm mt-4"><?= __('auth.verify_2fa_backup_hint') ?></p>
<p class="text-center text-sm mt-2"><a href="<?= base_url('admin/login') ?>"><?= __('auth.back_to_login') ?></a></p>
