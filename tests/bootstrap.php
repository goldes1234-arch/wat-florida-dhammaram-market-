<?php

define('BASE_PATH', dirname(__DIR__));

// Matches index.php — pinned explicitly so tests see the same clock the real app runs on.
date_default_timezone_set('UTC');

require BASE_PATH . '/app/Core/autoload.php';
require BASE_PATH . '/app/Core/helpers.php';

use App\Core\App;
use App\Core\Env;
use App\Core\Lang;

Env::load(BASE_PATH . '/.env');
App::boot(require BASE_PATH . '/config/config.php');
Lang::setLocale('th');

// Started once, up front, before any test prints to stdout — PHP's session
// extension warns if session_start() runs after output has already occurred,
// even under the CLI SAPI where there's no real HTTP header to violate.
session_start();
