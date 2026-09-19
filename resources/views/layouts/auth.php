<!doctype html>
<html lang="<?= e(\App\Core\Lang::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? __('auth.login_title')) ?> · <?= e(__('common.app_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
<style>
  body {
    display: flex; align-items: center; justify-content: center; min-height: 100vh;
    background: var(--gradient-mesh), var(--color-bg);
  }
  .auth-card { width: 100%; max-width: 380px; padding: 20px; }
  .auth-card .card { box-shadow: var(--shadow-lg); border-color: var(--color-border-light); animation: fadeInUp .45s cubic-bezier(.16,.84,.44,1) both; }
  .auth-brand { text-align: center; margin-bottom: 22px; font-weight: 800; font-size: 18px; }
  .auth-brand .brand-mark {
    display: inline-flex; width: 46px; height: 46px; border-radius: 13px;
    background: var(--gradient-brand); box-shadow: var(--shadow-glow-primary);
    color: #fff; align-items: center; justify-content: center; font-size: 21px; margin-bottom: 10px;
  }
  .auth-brand .brand-mark .icon { width: 22px; height: 22px; }
</style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-brand">
      <div class="brand-mark"><?= icon('store') ?></div>
      <div><?= e(__('common.app_name')) ?></div>
    </div>
    <div class="card">
      <?= partial('flash') ?>
      <?= $content ?>
    </div>
    <p class="text-center text-sm text-muted mt-4"><a href="<?= base_url('') ?>">&larr; <?= __('common.back_to_home') ?></a></p>
  </div>
</body>
</html>
