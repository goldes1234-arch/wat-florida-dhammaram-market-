<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

// Pinned explicitly rather than left to the server's php.ini default, since a few
// places (password reset / vendor portal token expiry) compare a PHP-computed
// timestamp against a value read back from the database — see AdminUser::
// findByValidResetTokenHash() for why that comparison needs PHP's clock, not
// MySQL's NOW(), to avoid a silent mismatch against whatever local timezone the
// DB server happens to be in.
date_default_timezone_set('UTC');

require BASE_PATH . '/app/Core/autoload.php';
require BASE_PATH . '/app/Core/helpers.php';

use App\Core\App;
use App\Core\Env;
use App\Core\Flash;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Models\Setting;

Env::load(BASE_PATH . '/.env');
App::boot(require BASE_PATH . '/config/config.php');

session_name('temple_market_session');
session_start();

// Defense-in-depth headers on every response. CSP keeps 'unsafe-inline' for
// script/style since the views rely on inline <script> blocks and style="" attributes
// throughout (a nonce-based rewrite is a larger follow-up) — it still blocks loading
// any remote script/style outside the two CDNs this app actually uses.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "frame-ancestors 'self'; "
    . "base-uri 'self'; "
    . "form-action 'self'");

$locale = Session::get('locale');
if (!$locale) {
    try {
        $locale = Setting::get()['default_locale'] ?? 'th';
    } catch (\Throwable $e) {
        $locale = 'th';
    }
}
Lang::setLocale($locale);

$debug = App::config('app.debug');

ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

set_exception_handler(function (\Throwable $e) use ($debug) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    if ($debug) {
        echo '<pre style="padding:20px;font-family:monospace;white-space:pre-wrap;">'
            . e($e->getMessage()) . "\n\n" . e($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Something went wrong</h1><p>Please try again later.</p>';
    }
});

$router = new Router();
require BASE_PATH . '/config/routes.php';

$router->dispatch(Request::capture());

Flash::consumeOld();
