<?php

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Lang;
use App\Core\Request;
use App\Core\View;

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    return Request::basePath() . '/' . ltrim($path, '/');
}

/**
 * Appends the file's last-modified time as a cache-busting query string, so an edited
 * CSS/JS file is picked up immediately instead of serving a browser-cached stale copy.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $url = base_url('assets/' . $path);

    $file = BASE_PATH . '/assets/' . $path;
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }

    return $url;
}

function full_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_url($path);
}

function upload_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    return base_url('uploads/' . ltrim($path, '/'));
}

function full_upload_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    return full_url('uploads/' . ltrim($path, '/'));
}

/** Absolute URL of the current request — used for the canonical/og:url meta tag. */
function current_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');
}

function __(string $key, array $replace = []): string
{
    return Lang::get($key, $replace);
}

function partial(string $name, array $data = []): string
{
    return View::partial($name, $data);
}

function csrf_field(): string
{
    return Csrf::field();
}

function old(string $key, string $default = ''): string
{
    return Flash::old($key, $default);
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function flash_messages(): array
{
    return Flash::consume();
}

function money(float $amount, ?string $currencyCode = null): string
{
    return \App\Services\CurrencyService::format($amount, $currencyCode);
}

function lot_status_label(string $status): string
{
    return match ($status) {
        'available' => __('lot.status_available'),
        'pending_payment' => __('lot.status_pending_payment'),
        'booked' => __('lot.status_booked'),
        'disabled' => __('lot.status_disabled'),
        default => $status,
    };
}

function lot_status_badge_class(string $status): string
{
    return match ($status) {
        'available' => 'badge badge-green',
        'pending_payment' => 'badge badge-amber',
        'booked' => 'badge badge-slate',
        'disabled' => 'badge badge-red',
        default => 'badge',
    };
}

function booking_status_label(string $status): string
{
    return match ($status) {
        'pending_payment' => __('booking.status_pending_payment'),
        'booked' => __('booking.status_booked'),
        'rejected' => __('booking.status_rejected'),
        'cancelled' => __('booking.status_cancelled'),
        default => $status,
    };
}

function booking_status_badge_class(string $status): string
{
    return match ($status) {
        'pending_payment' => 'badge badge-amber',
        'booked' => 'badge badge-green',
        'rejected' => 'badge badge-red',
        'cancelled' => 'badge badge-slate',
        default => 'badge',
    };
}

/** Privacy-preserving display for a booker's name on public pages: "สมชาย ใจดี" -> "สมชาย ใ." */
function mask_booker_name(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name), 2);
    if (!$parts || count($parts) < 2 || $parts[1] === '') {
        return $parts[0] ?? $name;
    }
    $initial = mb_substr($parts[1], 0, 1, 'UTF-8');
    return $parts[0] . ' ' . $initial . '.';
}

function payment_method_label(string $method): string
{
    return match ($method) {
        'onsite_cash' => __('booking.method_onsite_cash'),
        'bank_transfer' => __('booking.method_bank_transfer'),
        'stripe' => __('booking.method_stripe'),
        default => $method,
    };
}

/**
 * Inline SVG icon (Lucide-style, stroke-based, currentColor) — used in place of emoji
 * throughout the admin/public UI for a consistent, premium look across all platforms
 * (emoji render inconsistently between OS/browsers, which looks unpolished).
 */
function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'map-pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'ticket' => '<path d="M3 9a3 3 0 1 0 0 6v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a3 3 0 1 1 0-6V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/><line x1="13" y1="5" x2="13" y2="19" stroke-dasharray="2 3"/>',
        'credit-card' => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
        'mail' => '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'message-circle' => '<path d="M21 11.5a8.38 8.38 0 0 1-4.8 7.6 8.5 8.5 0 0 1-8.4-.3L3 20l1.3-4.9a8.38 8.38 0 0 1-1.2-4.4 8.5 8.5 0 0 1 8.5-8.2h.3a8.48 8.48 0 0 1 8 7.8z"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'store' => '<path d="M3 9l1-5h16l1 5"/><path d="M3 9a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"/><path d="M4 9v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/><path d="M9 21v-6h6v6"/>',
        'sparkle' => '<path d="M12 2l1.8 6.2L20 10l-6.2 1.8L12 18l-1.8-6.2L4 10l6.2-1.8z"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'plus-circle' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
        'menu' => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'youtube' => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="currentColor" stroke="none"/>',
        'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
    ];

    if (!isset($paths[$name])) {
        return '';
    }

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}
