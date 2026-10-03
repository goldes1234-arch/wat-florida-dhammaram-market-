<?php

use App\Core\Env;

return [
    'app' => [
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::get('APP_DEBUG', 'false') === 'true',
        'url' => rtrim(Env::get('APP_URL', ''), '/'),
    ],
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'temple_market'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'socket' => Env::get('DB_SOCKET', ''),
    ],
    'mail' => [
        'from_address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'Temple Market'),
    ],
    'google' => [
        'client_id' => Env::get('GOOGLE_DRIVE_CLIENT_ID', ''),
        'client_secret' => Env::get('GOOGLE_DRIVE_CLIENT_SECRET', ''),
        // How many backup copies to keep in Drive before the oldest are deleted.
        'keep' => (int) Env::get('GOOGLE_DRIVE_KEEP', 30),
    ],
    'backup' => [
        'mysqldump_path' => Env::get('MYSQLDUMP_PATH', ''),
        'mysql_path' => Env::get('MYSQL_PATH', ''),
        'retention' => (int) Env::get('BACKUP_RETENTION', 14),
        'cron_secret' => Env::get('BACKUP_CRON_SECRET', ''),
    ],
];
