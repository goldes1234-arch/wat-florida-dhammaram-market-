<div class="page-header">
  <h1><?= __('checkin.title') ?></h1>
</div>

<div class="card" style="max-width:480px;">
  <div class="card-header"><h3><?= __('checkin.search_title') ?></h3></div>
  <p class="form-hint mb-4"><?= __('checkin.search_hint') ?></p>
  <form method="post" action="<?= base_url('admin/checkin') ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label><?= __('checkin.search_by_label') ?></label>
      <select name="search_by" class="form-control" id="checkinSearchBy">
        <option value="code"><?= __('checkin.search_by_code') ?></option>
        <option value="name"><?= __('checkin.search_by_name') ?></option>
        <option value="phone"><?= __('checkin.search_by_phone') ?></option>
        <option value="email"><?= __('checkin.search_by_email') ?></option>
      </select>
    </div>
    <div class="form-group">
      <input type="text" name="query" class="form-control" id="checkinSearchQuery"
             style="font-size:20px;text-align:center;padding:16px;"
             placeholder="TM-XXXXXX" autofocus required>
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block" style="padding:18px;font-size:16px;"><?= __('checkin.search_button') ?></button>
  </form>

  <button type="button" id="qrScanToggle" class="btn btn-accent btn-block mt-4">📷 <?= __('checkin.scan_qr_button') ?></button>

  <div id="qrScanArea" style="display:none;margin-top:16px;">
    <div id="qr-reader"></div>
    <p class="form-hint text-center mt-2"><?= __('checkin.scan_qr_hint') ?></p>
  </div>
</div>

<script>
(function () {
  var select = document.getElementById('checkinSearchBy');
  var input = document.getElementById('checkinSearchQuery');
  var placeholders = {
    code: 'TM-XXXXXX',
    name: <?= json_encode(__('checkin.search_by_name')) ?>,
    phone: '08XXXXXXXX',
    email: 'name@example.com'
  };
  select.addEventListener('change', function () {
    input.placeholder = placeholders[select.value] || '';
    input.style.textTransform = select.value === 'code' ? 'uppercase' : 'none';
  });
})();

document.getElementById('qrScanToggle').addEventListener('click', function () {
  var area = document.getElementById('qrScanArea');
  var isOpening = area.style.display === 'none';
  area.style.display = isOpening ? 'block' : 'none';
  if (!isOpening) return;

  var script = document.createElement('script');
  script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js';
  script.onload = function () {
    var scanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: 220 }, false);
    scanner.render(function onScanSuccess(decodedText) {
      scanner.clear();
      window.location.href = <?= json_encode(base_url('admin/checkin/')) ?> + encodeURIComponent(decodedText.trim());
    });
  };
  document.body.appendChild(script);
}, { once: true });
</script>
