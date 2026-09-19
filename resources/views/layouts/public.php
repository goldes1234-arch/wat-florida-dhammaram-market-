<?php
$settings = $settings ?? \App\Models\Setting::get();
$locale = \App\Core\Lang::locale();
$orgName = $settings['org_name'] ?: __('common.app_name');

$metaTitle = $metaTitle ?? ($title ?? __('common.app_name')) . ' · ' . $orgName;
$metaDescription = $metaDescription ?? __('public.tagline');
$metaImage = $metaImage ?? (!empty($settings['logo_path']) ? full_upload_url($settings['logo_path']) : '');
$metaType = $metaType ?? 'website';
$metaUrl = current_url();
?><!doctype html>
<html lang="<?= e($locale) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? __('common.app_name')) ?> · <?= e($orgName) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">

<meta property="og:site_name" content="<?= e($orgName) ?>">
<meta property="og:type" content="<?= e($metaType) ?>">
<meta property="og:title" content="<?= e($metaTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($metaUrl) ?>">
<?php if ($metaImage): ?><meta property="og:image" content="<?= e($metaImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= $metaImage ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($metaTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">
<?php if ($metaImage): ?><meta name="twitter:image" content="<?= e($metaImage) ?>"><?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
<link rel="stylesheet" href="<?= asset('css/public.css') ?>">
</head>
<body>
<nav class="public-nav">
  <div class="container nav-inner">
    <a href="<?= base_url('') ?>" class="public-brand">
      <?php if (!empty($settings['logo_path'])): ?>
        <img src="<?= upload_url($settings['logo_path']) ?>" alt="<?= e($settings['org_name']) ?>">
      <?php else: ?>
        <span class="brand-mark"><?= icon('store') ?></span>
      <?php endif; ?>
      <span><?= e($settings['org_name'] ?: __('common.app_name')) ?></span>
    </a>
    <div class="public-nav-links">
      <a href="<?= base_url('') ?>"><?= __('nav.home') ?></a>
      <a href="<?= base_url('my-booking') ?>"><?= __('nav.my_booking') ?></a>
      <a href="<?= base_url('contact') ?>"><?= __('nav.contact') ?></a>
      <div class="lang-switch">
        <a href="<?= base_url('lang/th') ?>" class="<?= $locale === 'th' ? 'is-active' : '' ?>">TH</a>
        <a href="<?= base_url('lang/en') ?>" class="<?= $locale === 'en' ? 'is-active' : '' ?>">EN</a>
      </div>
      <a href="<?= base_url('admin/login') ?>" class="text-muted"><?= __('nav.admin') ?></a>
    </div>
  </div>
</nav>

<main class="public-main">
  <div class="container">
    <?= partial('flash') ?>
    <?= $content ?>
  </div>
</main>

<?php
$footerLinks = [];
if (!empty($settings['website_url'])) {
    $footerLinks[] = ['icon' => 'globe', 'label' => __('settings.website_url'), 'url' => $settings['website_url']];
}
if (!empty($settings['facebook_url'])) {
    $footerLinks[] = ['icon' => 'facebook', 'label' => 'Facebook', 'url' => $settings['facebook_url']];
}
if (!empty($settings['line_oa_id'])) {
    $footerLinks[] = ['icon' => 'message-circle', 'label' => 'LINE', 'url' => 'https://line.me/R/ti/p/' . rawurlencode($settings['line_oa_id'])];
}
if (!empty($settings['youtube_url'])) {
    $footerLinks[] = ['icon' => 'youtube', 'label' => 'YouTube', 'url' => $settings['youtube_url']];
}
if (!empty($settings['google_maps_url'])) {
    $footerLinks[] = ['icon' => 'map-pin', 'label' => __('public.venue'), 'url' => $settings['google_maps_url']];
}
foreach (\App\Models\SocialLink::all() as $extraLink) {
    $footerLinks[] = ['icon' => 'link', 'label' => $extraLink['label'], 'url' => $extraLink['url']];
}
?>
<footer class="site-footer">
  <div class="container">
    <?php if (!empty($settings['org_address'])): ?><div class="mb-2"><?= e($settings['org_address']) ?></div><?php endif; ?>
    <?php if ($footerLinks): ?>
      <div class="footer-links">
        <?php foreach ($footerLinks as $fl): ?>
          <a href="<?= e($fl['url']) ?>" target="_blank" rel="noopener noreferrer"><?= icon($fl['icon']) ?> <?= e($fl['label']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    &copy; <?= date('Y') ?> <?= e($settings['org_name'] ?: __('common.app_name')) ?>
  </div>
</footer>

<div class="lightbox-overlay" id="lightbox">
  <button class="lightbox-close" type="button">&times;</button>
  <img src="" alt="">
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/public.js') ?>"></script>
</body>
</html>
