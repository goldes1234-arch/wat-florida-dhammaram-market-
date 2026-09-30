<?php

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/autoload.php';
require BASE_PATH . '/app/Core/helpers.php';

use App\Core\App;
use App\Core\Env;
use App\Core\Lang;

Env::load(BASE_PATH . '/.env');
App::boot(require BASE_PATH . '/config/config.php');
Lang::setLocale('th');
