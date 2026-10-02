<?php if (\App\Core\App::config('app.env') === 'staging'): ?>
  <div style="position:sticky;top:0;z-index:9999;background:#B91C1C;color:#fff;text-align:center;
              font-weight:700;font-size:13px;padding:6px 12px;letter-spacing:.02em;">
    ⚠ STAGING — <?= e(__('common.staging_banner')) ?>
  </div>
<?php endif; ?>
