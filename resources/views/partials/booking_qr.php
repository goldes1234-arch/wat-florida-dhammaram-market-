<?php /** @var array $booking */ ?>
<div class="qr-box">
  <div id="qrcode-<?= (int) $booking['id'] ?>" class="qr-canvas"></div>
  <p class="text-sm text-muted mb-0"><?= __('public.qr_hint') ?></p>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  new QRCode(document.getElementById("qrcode-<?= (int) $booking['id'] ?>"), {
    text: <?= json_encode($booking['booking_code']) ?>,
    width: 160,
    height: 160,
    colorDark: "#14152B",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
  });
</script>
