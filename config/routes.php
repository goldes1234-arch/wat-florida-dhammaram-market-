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
$router->get('/my-booking', [Public\MyBookingController::class, 'lookupForm']);
$router->post('/my-booking', [Public\MyBookingController::class, 'search']);
$router->get('/my-booking/{code}', [Public\MyBookingController::class, 'show']);
$router->post('/my-booking/{code}/cancel', [Public\MyBookingController::class, 'cancel']);
$router->get('/contact', [Public\ContactController::class, 'form']);
$router->post('/contact', [Public\ContactController::class, 'store']);

// ---------------------------------------------------------------- Admin
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/admin/login', [Admin\AuthController::class, 'loginForm']);
    $router->post('/admin/login', [Admin\AuthController::class, 'login']);
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

    // Everything else is off-limits to the "checkin" role (see staff_or_admin middleware).
    $router->group(['middleware' => ['staff_or_admin']], function ($router) {
        $router->get('/admin', [Admin\DashboardController::class, 'index']);

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
        $router->get('/admin/lots/{id}/edit', [Admin\LotController::class, 'edit']);
        $router->post('/admin/lots/{id}', [Admin\LotController::class, 'update']);
        $router->post('/admin/lots/{id}/toggle-disable', [Admin\LotController::class, 'toggleDisable']);
        $router->post('/admin/lots/{id}/delete', [Admin\LotController::class, 'destroy']);
        $router->post('/admin/events/{eventId}/lots/delete-all', [Admin\LotController::class, 'destroyAll']);
        $router->post('/admin/events/{eventId}/lots/delete-selected', [Admin\LotController::class, 'destroySelected']);

        $router->get('/admin/bookings', [Admin\BookingController::class, 'index']);
        $router->post('/admin/bookings/delete-selected', [Admin\BookingController::class, 'destroySelected']);
        $router->post('/admin/bookings/delete-all', [Admin\BookingController::class, 'destroyAll']);
        $router->get('/admin/bookings/export', [Admin\BookingController::class, 'export']);
        $router->get('/admin/bookings/{id}', [Admin\BookingController::class, 'show']);
        $router->post('/admin/bookings/{id}/confirm', [Admin\BookingController::class, 'confirm']);
        $router->post('/admin/bookings/{id}/reject', [Admin\BookingController::class, 'reject']);
        $router->post('/admin/bookings/{id}/cancel', [Admin\BookingController::class, 'cancel']);

        $router->get('/admin/events/{eventId}/subscribers', [Admin\SubscriberController::class, 'index']);

        $router->get('/admin/contacts', [Admin\ContactController::class, 'index']);
        $router->post('/admin/contacts/{id}/read', [Admin\ContactController::class, 'markRead']);

        $router->group(['middleware' => ['super_admin']], function ($router) {
            $router->get('/admin/settings', [Admin\SettingsController::class, 'edit']);
            $router->post('/admin/settings', [Admin\SettingsController::class, 'update']);
            $router->post('/admin/settings/social-links', [Admin\SettingsController::class, 'storeSocialLink']);
            $router->post('/admin/settings/social-links/{id}', [Admin\SettingsController::class, 'updateSocialLink']);
            $router->post('/admin/settings/social-links/{id}/delete', [Admin\SettingsController::class, 'destroySocialLink']);
            $router->post('/admin/settings/gallery', [Admin\SettingsController::class, 'storeGalleryPhoto']);
            $router->post('/admin/settings/gallery/{id}/delete', [Admin\SettingsController::class, 'destroyGalleryPhoto']);
            $router->post('/admin/settings/test-email', [Admin\SettingsController::class, 'testEmail']);
            $router->get('/admin/staff', [Admin\StaffController::class, 'index']);
            $router->post('/admin/staff', [Admin\StaffController::class, 'store']);
            $router->post('/admin/staff/{id}/toggle-active', [Admin\StaffController::class, 'toggleActive']);
        });
    });
});
