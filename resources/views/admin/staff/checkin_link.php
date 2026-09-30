<?php
/** @var array $staffUser */
/** @var string $link */
?>
<div class="page-header">
  <h1><?= __('staff.checkin_link_title') ?></h1>
</div>

<div class="card" style="max-width:520px;">
  <p class="text-sm mb-4">✅ <?= __('staff.checkin_link_generated_for', ['name' => $staffUser['name']]) ?></p>
  <p class="form-hint mb-4"><?= __('staff.checkin_link_hint') ?></p>

  <div id="checkin-link-qr" class="qr-canvas" style="margin:0 auto 16px;"></div>

  <div class="form-group">
    <label><?= __('staff.checkin_link_label') ?></label>
    <input type="text" class="form-control" readonly value="<?= e($link) ?>" onclick="this.select()">
  </div>

  <a href="<?= base_url('admin/staff') ?>" class="btn btn-primary btn-block mt-6"><?= __('staff.checkin_link_done_button') ?></a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  new QRCode(document.getElementById("checkin-link-qr"), {
    text: <?= json_encode($link) ?>,
    width: 200,
    height: 200,
    colorDark: "#14152B",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
  });
</script>
