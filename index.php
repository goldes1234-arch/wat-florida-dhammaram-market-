<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

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
