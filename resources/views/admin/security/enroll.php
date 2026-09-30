<?php
/** @var string $secret */
/** @var string $qrUri */
$secretGrouped = trim(chunk_split($secret, 4, ' '));
?>
<div class="page-header">
  <h1><?= __('security.enroll_title') ?></h1>
</div>

<div class="card" style="max-width:520px;">
  <p class="form-hint mb-4"><?= __('security.enroll_hint') ?></p>

  <div id="totp-qr" class="qr-canvas" style="margin:0 auto 16px;"></div>

  <div class="form-group">
    <label><?= __('security.manual_key_label') ?></label>
    <input type="text" class="form-control" readonly value="<?= e($secretGrouped) ?>" onclick="this.select()" style="text-align:center;letter-spacing:2px;">
  </div>

  <form method="post" action="<?= base_url('admin/security/enroll/confirm') ?>" class="mt-4">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="code"><?= __('security.confirm_code_label') ?></label>
      <input type="text" id="code" name="code" class="form-control" style="font-size:22px;text-align:center;letter-spacing:4px;"
             inputmode="numeric" maxlength="6" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><?= __('security.confirm_button') ?></button>
  </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  new QRCode(document.getElementById("totp-qr"), {
    text: <?= json_encode($qrUri) ?>,
    width: 200,
    height: 200,
    colorDark: "#14152B",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
  });
</script>
