<?php

use App\Controllers\Admin;
use App\Controllers\Public;

/** @var \App\Core\Router $router */

// ---------------------------------------------------------------- Public
$router->get('/', [Public\HomeController::class, 'index']);
$router->get('/lang/{locale}', [Public\LocaleController::class, 'switch']);
$router->get('/events/{slug}', [Public\EventController::class, 'show']);
$router->get('/events/{slug}/lot-status', [Public\EventController::class, 'lotStatus']);
$router->post('/events/{slug}/interest', [Public\SubscribeController::class, 'store']);
$router->post('/events/{slug}/waitlist', [Public\WaitlistController::class, 'store']);
$router->get('/events/{slug}/book/{lotId}', [Public\BookingController::class, 'create']);
$router->post('/events/{slug}/book/{lotId}', [Public\BookingController::class, 'store']);
$router->get('/booking/{code}/confirmation', [Public\BookingController::class, 'confirmation']);
$router->get('/booking/{code}/receipt', [Public\BookingController::class, 'receipt']);
$router->get('/booking/{code}/stripe-return', [Public\BookingController::class, 'stripeReturn']);
$router->get('/booking/{code}/stripe-cancelled', [Public\BookingController::class, 'stripeCancelled']);
$router->post('/stripe/webhook', [Public\StripeWebhookController::class, 'handle']);
$router->post('/line/webhook', [Public\LineWebhookController::class, 'handle']);
$router->get('/my-booking', [Public\MyBookingController::class, 'lookupForm']);
$router->post('/my-booking', [Public\MyBookingController::class, 'search']);
$router->get('/my-booking/{code}', [Public\MyBookingController::class, 'show']);
$router->post('/my-booking/{code}/cancel', [Public\MyBookingController::class, 'cancel']);
$router->get('/contact', [Public\ContactController::class, 'form']);
$router->post('/contact', [Public\ContactController::class, 'store']);
$router->get('/advertise', [Public\AdvertisementController::class, 'form']);
$router->post('/advertise', [Public\AdvertisementController::class, 'store']);
$router->get('/downloads/{id}', [Public\DownloadController::class, 'show']);
$router->get('/vendor/portal/{token}', [Public\VendorPortalController::class, 'show']);
$router->get('/checkin-link/{token}', [Public\CheckinLinkController::class, 'show']);
$router->get('/reserve/{token}', [Public\ReservationController::class, 'show']);
$router->post('/reserve/{token}/confirm', [Public\ReservationController::class, 'confirm']);
$router->get('/health', [Public\HealthController::class, 'check']);
$router->get('/cron/backup', [Public\CronController::class, 'backup']);
$router->get('/cron/post-deploy', [Public\CronController::class, 'postDeploy']);

// ---------------------------------------------------------------- Admin
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/admin/login', [Admin\AuthController::class, 'loginForm']);
    $router->post('/admin/login', [Admin\AuthController::class, 'login']);
    $router->get('/admin/login/verify-2fa', [Admin\AuthController::class, 'verify2faForm']);
    $router->post('/admin/login/verify-2fa', [Admin\AuthController::class, 'verify2fa']);
    $router->get('/admin/forgot-password', [Admin\AuthController::class, 'forgotPasswordForm']);
    $router->post('/admin/forgot-password', [Admin\AuthController::class, 'sendResetLink']);
    $router->get('/admin/reset-password/{token}', [Admin\AuthController::class, 'resetPasswordForm']);
    $router->post('/admin/reset-password/{token}', [Admin\AuthController::class, 'resetPassword']);
});

$router->group(['middleware' => ['auth']], function ($router) {
    $router->post('/admin/logout', [Admin\AuthController::class, 'logout']);

    // Reachable by every authenticated role (including "checkin").
    $router->get('/admin/checkin', [Admin\CheckinController::class, 'lookupForm']);
    $router->post('/admin/checkin', [Admin\CheckinController::class, 'search']);
    $router->get('/admin/checkin/{code}', [Admin\CheckinController::class, 'show']);
    $router->post('/admin/checkin/{code}/confirm', [Admin\CheckinController::class, 'confirm']);

    $router->get('/admin/security', [Admin\SecurityController::class, 'index']);
    $router->get('/admin/security/enroll', [Admin\SecurityController::class, 'enrollStart']);
    $router->post('/admin/security/enroll/confirm', [Admin\SecurityController::class, 'enrollConfirm']);
    $router->post('/admin/security/disable', [Admin\SecurityController::class, 'disable']);

    // Everything else is off-limits to the "checkin" role (see staff_or_admin middleware).
    $router->group(['middleware' => ['staff_or_admin']], function ($router) {
        // Money-related — reachable by finance too, not just staff/super_admin (see not_finance below).
        $router->get('/admin', [Admin\DashboardController::class, 'index']);

        $router->get('/admin/reports', [Admin\ReportController::class, 'index']);
        $router->get('/admin/reports/export-events', [Admin\ReportController::class, 'exportEvents']);

        $router->get('/admin/bookings', [Admin\BookingController::class, 'index']);
        $router->post('/admin/bookings/delete-selected', [Admin\BookingController::class, 'destroySelected']);
        $router->post('/admin/bookings/delete-all', [Admin\BookingController::class, 'destroyAll']);
        $router->get('/admin/bookings/export', [Admin\BookingController::class, 'export']);
        $router->get('/admin/bookings/{id}', [Admin\BookingController::class, 'show']);
        $router->post('/admin/bookings/{id}/confirm', [Admin\BookingController::class, 'confirm']);
        $router->post('/admin/bookings/{id}/reject', [Admin\BookingController::class, 'reject']);
        $router->post('/admin/bookings/{id}/cancel', [Admin\BookingController::class, 'cancel']);

        $router->group(['middleware' => ['finance_or_super_admin']], function ($router) {
            $router->post('/admin/bookings/{id}/refund', [Admin\BookingController::class, 'refund']);
        });

        // Event/lot/vendor management and everything else below — staff and
        // super_admin only; finance is redirected away (see not_finance middleware).
        $router->group(['middleware' => ['not_finance']], function ($router) {
            $router->get('/admin/events', [Admin\EventController::class, 'index']);
            $router->get('/admin/events/create', [Admin\EventController::class, 'create']);
            $router->post('/admin/events', [Admin\EventController::class, 'store']);
            $router->get('/admin/events/{id}/edit', [Admin\EventController::class, 'edit']);
            $router->post('/admin/events/{id}', [Admin\EventController::class, 'update']);
            $router->post('/admin/events/{id}/delete', [Admin\EventController::class, 'destroy']);
            $router->get('/admin/events/{id}/contacts', [Admin\EventController::class, 'contactsJson']);
            $router->post('/admin/events/{id}/photos', [Admin\EventController::class, 'storePhoto']);
            $router->post('/admin/events/{id}/photos/{photoId}/delete', [Admin\EventController::class, 'destroyPhoto']);

            $router->get('/admin/events/{eventId}/zones', [Admin\ZoneController::class, 'index']);
            $router->post('/admin/events/{eventId}/zones', [Admin\ZoneController::class, 'store']);
            $router->post('/admin/events/{eventId}/zones/copy', [Admin\ZoneController::class, 'copyFrom']);
            $router->post('/admin/zones/{id}', [Admin\ZoneController::class, 'update']);
            $router->post('/admin/zones/{id}/delete', [Admin\ZoneController::class, 'destroy']);

            $router->get('/admin/events/{eventId}/lots', [Admin\LotController::class, 'index']);
            $router->post('/admin/events/{eventId}/lots', [Admin\LotController::class, 'store']);
            $router->post('/admin/events/{eventId}/lots/bulk', [Admin\LotController::class, 'bulkStore']);
            $router->post('/admin/events/{eventId}/lots/grid', [Admin\LotController::class, 'gridStore']);
            $router->get('/admin/events/{eventId}/lots/map', [Admin\LotController::class, 'mapEditor']);
            $router->post('/admin/events/{eventId}/lots/map-position', [Admin\LotController::class, 'savePosition']);
            $router->post('/admin/events/{eventId}/lots/map-size', [Admin\LotController::class, 'saveSize']);
            $router->post('/admin/events/{eventId}/lots/map-shape', [Admin\LotController::class, 'saveShape']);
            $router->post('/admin/events/{eventId}/lots/map-rotation', [Admin\LotController::class, 'saveRotation']);
            $router->post('/admin/events/{eventId}/lots/map-quick-add', [Admin\LotController::class, 'mapQuickAdd']);
            $router->get('/admin/lots/{id}/edit', [Admin\LotController::class, 'edit']);
            $router->post('/admin/lots/{id}', [Admin\LotController::class, 'update']);
            $router->post('/admin/lots/{id}/inline-update', [Admin\LotController::class, 'inlineUpdate']);
            $router->post('/admin/lots/{id}/toggle-disable', [Admin\LotController::class, 'toggleDisable']);
            $router->post('/admin/lots/{id}/reserve', [Admin\LotController::class, 'reserve']);
            $router->post('/admin/lots/{id}/cancel-reservation', [Admin\LotController::class, 'cancelReservation']);
            $router->post('/admin/lots/{id}/delete', [Admin\LotController::class, 'destroy']);
            $router->post('/admin/events/{eventId}/lots/delete-all', [Admin\LotController::class, 'destroyAll']);
            $router->post('/admin/events/{eventId}/lots/delete-selected', [Admin\LotController::class, 'destroySelected']);

            $router->get('/admin/events/{eventId}/subscribers', [Admin\SubscriberController::class, 'index']);

            $router->get('/admin/contacts', [Admin\ContactController::class, 'index']);
            $router->post('/admin/contacts/{id}/read', [Admin\ContactController::class, 'markRead']);

            $router->get('/admin/advertisements', [Admin\AdvertisementController::class, 'index']);
            $router->post('/admin/advertisements', [Admin\AdvertisementController::class, 'store']);
            $router->post('/admin/advertisements/{id}/approve', [Admin\AdvertisementController::class, 'approve']);
            $router->post('/admin/advertisements/{id}/delete', [Admin\AdvertisementController::class, 'destroy']);

            $router->get('/admin/vendors', [Admin\VendorController::class, 'index']);
            $router->post('/admin/vendors', [Admin\VendorController::class, 'store']);
            $router->get('/admin/vendors/{id}', [Admin\VendorController::class, 'show']);
            $router->post('/admin/vendors/{id}', [Admin\VendorController::class, 'update']);
            $router->post('/admin/vendors/{id}/delete', [Admin\VendorController::class, 'destroy']);
            $router->post('/admin/vendors/{id}/line-message', [Admin\VendorController::class, 'sendLineMessage']);

            $router->get('/admin/line-messages', [Admin\LineMessageController::class, 'index']);
            $router->post('/admin/line-messages', [Admin\LineMessageController::class, 'send']);

            $router->get('/admin/downloads', [Admin\DownloadController::class, 'index']);
            $router->post('/admin/downloads/categories', [Admin\DownloadController::class, 'storeCategory']);
            $router->post('/admin/downloads/categories/{id}/delete', [Admin\DownloadController::class, 'destroyCategory']);
            $router->post('/admin/downloads/files', [Admin\DownloadController::class, 'storeFile']);
            $router->post('/admin/downloads/files/{id}/delete', [Admin\DownloadController::class, 'destroyFile']);
        });

        $router->group(['middleware' => ['super_admin']], function ($router) {
            $router->get('/admin/settings', [Admin\SettingsController::class, 'edit']);
            $router->post('/admin/settings', [Admin\SettingsController::class, 'update']);
            $router->post('/admin/settings/social-links', [Admin\SettingsController::class, 'storeSocialLink']);
            $router->post('/admin/settings/social-links/{id}', [Admin\SettingsController::class, 'updateSocialLink']);
            $router->post('/admin/settings/social-links/{id}/delete', [Admin\SettingsController::class, 'destroySocialLink']);
            $router->post('/admin/settings/test-email', [Admin\SettingsController::class, 'testEmail']);

            $router->get('/admin/gallery', [Admin\GalleryController::class, 'index']);
            $router->post('/admin/gallery', [Admin\GalleryController::class, 'store']);
            $router->post('/admin/gallery/{id}/delete', [Admin\GalleryController::class, 'destroy']);

            $router->get('/admin/activity-log', [Admin\ActivityLogController::class, 'index']);

            $router->get('/admin/backups', [Admin\BackupController::class, 'index']);
            $router->post('/admin/backups', [Admin\BackupController::class, 'store']);
            $router->get('/admin/backups/{filename}/download', [Admin\BackupController::class, 'download']);
            $router->post('/admin/backups/{filename}/delete', [Admin\BackupController::class, 'destroy']);
            $router->post('/admin/backups/{filename}/verify', [Admin\BackupController::class, 'verify']);
            $router->post('/admin/backups/{filename}/restore', [Admin\BackupController::class, 'restore']);
            $router->post('/admin/migrations/rollback', [Admin\BackupController::class, 'rollbackLastMigration']);
            $router->get('/admin/staff', [Admin\StaffController::class, 'index']);
            $router->post('/admin/staff', [Admin\StaffController::class, 'store']);
            $router->post('/admin/staff/{id}/toggle-active', [Admin\StaffController::class, 'toggleActive']);
            $router->post('/admin/staff/{id}/delete', [Admin\StaffController::class, 'destroy']);
            $router->post('/admin/staff/{id}/set-password', [Admin\StaffController::class, 'setPassword']);
            $router->post('/admin/staff/{id}/phone', [Admin\StaffController::class, 'updatePhone']);
            $router->post('/admin/staff/{id}/checkin-link/generate', [Admin\StaffController::class, 'generateCheckinLink']);
            $router->post('/admin/staff/{id}/checkin-link/revoke', [Admin\StaffController::class, 'revokeCheckinLink']);
            $router->get('/admin/staff/{id}/events', [Admin\StaffController::class, 'eventsForm']);
            $router->post('/admin/staff/{id}/events', [Admin\StaffController::class, 'updateEventAccess']);
        });
    });
});
