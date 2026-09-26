<?php
$user = \App\Core\Auth::user();
$active = $active ?? '';
$locale = \App\Core\Lang::locale();
?><!doctype html>
<html lang="<?= e($locale) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? __('nav.dashboard')) ?> · <?= e(__('common.app_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/design-system.css') ?>">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-brand"><span class="brand-mark"><?= icon('store') ?></span> <span><?= e(__('common.app_name')) ?></span></div>
    <ul class="admin-nav">
      <?php if (\App\Core\Auth::isCheckinOnly()): ?>
        <li><a href="<?= base_url('admin/checkin') ?>" class="<?= $active === 'checkin' ? 'is-active' : '' ?>"><span class="nav-icon icon-green"><?= icon('check-circle') ?></span> <?= __('nav.checkin') ?></a></li>
      <?php else: ?>
        <li><a href="<?= base_url('admin') ?>" class="<?= $active === 'dashboard' ? 'is-active' : '' ?>"><span class="nav-icon icon-indigo"><?= icon('dashboard') ?></span> <?= __('nav.dashboard') ?></a></li>
        <li><a href="<?= base_url('admin/events') ?>" class="<?= $active === 'events' ? 'is-active' : '' ?>"><span class="nav-icon icon-violet"><?= icon('calendar') ?></span> <?= __('nav.events') ?></a></li>
        <li><a href="<?= base_url('admin/bookings') ?>" class="<?= $active === 'bookings' ? 'is-active' : '' ?>"><span class="nav-icon icon-magenta"><?= icon('ticket') ?></span> <?= __('nav.bookings') ?></a></li>
        <li><a href="<?= base_url('admin/checkin') ?>" class="<?= $active === 'checkin' ? 'is-active' : '' ?>"><span class="nav-icon icon-green"><?= icon('check-circle') ?></span> <?= __('nav.checkin') ?></a></li>
        <li>
          <a href="<?= base_url('admin/contacts') ?>" class="<?= $active === 'contacts' ? 'is-active' : '' ?>">
            <span class="nav-icon icon-amber"><?= icon('mail') ?></span> <?= __('nav.contacts') ?>
            <?php $unreadContacts = \App\Models\ContactMessage::unreadCount(); ?>
            <?php if ($unreadContacts > 0): ?><span class="nav-badge"><?= $unreadContacts ?></span><?php endif; ?>
          </a>
        </li>
        <?php if (\App\Core\Auth::isSuperAdmin()): ?>
        <li><a href="<?= base_url('admin/staff') ?>" class="<?= $active === 'staff' ? 'is-active' : '' ?>"><span class="nav-icon icon-blue"><?= icon('users') ?></span> <?= __('nav.staff') ?></a></li>
        <?php endif; ?>
        <li>
          <a href="<?= base_url('admin/advertisements') ?>" class="<?= $active === 'advertisements' ? 'is-active' : '' ?>">
            <span class="nav-icon icon-teal"><?= icon('store') ?></span> <?= __('nav.advertisements') ?>
            <?php $pendingAdsCount = \App\Models\Advertisement::pendingCount(); ?>
            <?php if ($pendingAdsCount > 0): ?><span class="nav-badge"><?= $pendingAdsCount ?></span><?php endif; ?>
          </a>
        </li>
        <?php if (\App\Core\Auth::isSuperAdmin()): ?>
        <li><a href="<?= base_url('admin/backups') ?>" class="<?= $active === 'backups' ? 'is-active' : '' ?>"><span class="nav-icon icon-amber"><?= icon('download') ?></span> <?= __('nav.backups') ?></a></li>
        <li><a href="<?= base_url('admin/activity-log') ?>" class="<?= $active === 'activity_log' ? 'is-active' : '' ?>"><span class="nav-icon icon-magenta"><?= icon('clock') ?></span> <?= __('nav.activity_log') ?></a></li>
        <li><a href="<?= base_url('admin/settings') ?>" class="<?= $active === 'settings' ? 'is-active' : '' ?>"><span class="nav-icon icon-teal"><?= icon('settings') ?></span> <?= __('nav.settings') ?></a></li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
    <ul class="admin-nav" style="margin-top:20px;border-top:1px solid var(--color-border-light);padding-top:14px;">
      <li><a href="<?= base_url('') ?>" target="_blank" rel="noopener"><span class="nav-icon"><?= icon('globe') ?></span> <?= __('nav.view_site') ?></a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button type="button" class="sidebar-toggle" data-toggle="#adminSidebar"><?= icon('menu') ?></button>
        <h1><?= e($title ?? __('nav.dashboard')) ?></h1>
      </div>
      <div class="topbar-user">
        <div class="lang-switch">
          <a href="<?= base_url('lang/th') ?>" class="<?= $locale === 'th' ? 'is-active' : '' ?>">TH</a>
          <a href="<?= base_url('lang/en') ?>" class="<?= $locale === 'en' ? 'is-active' : '' ?>">EN</a>
        </div>
        <span class="user-name"><?= e($user['name'] ?? '') ?></span>
        <form method="post" action="<?= base_url('admin/logout') ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-secondary btn-sm"><?= __('nav.logout') ?></button>
        </form>
      </div>
    </header>

    <div class="admin-content">
      <?= partial('flash') ?>
      <?= $content ?>
    </div>
  </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
