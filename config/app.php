<?php

declare(strict_types=1);

use App\Helpers\Env;

$root = dirname(__DIR__);

Env::load($root . '/.env');

// S4 : en production, le debug est TOUJOURS coupé quel que soit APP_DEBUG
$env = Env::get('APP_ENV', 'production');

return [
    'name' => Env::get('APP_NAME', 'MASS BUSINESS WEAR'),
    'env' => $env,
    'debug' => $env === 'production' ? false : Env::bool('APP_DEBUG', false),
    'url' => rtrim(Env::get('APP_URL', 'http://localhost/mass-business-wear/public'), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Africa/Dakar'),
    'root' => $root,

    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::int('DB_PORT', 3306),
        'name' => Env::get('DB_NAME', 'mbw'),
        'user' => Env::get('DB_USER', 'root'),
        'pass' => Env::get('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name' => Env::get('SESSION_NAME', 'mbw_sess'),
        'lifetime' => Env::int('SESSION_LIFETIME', 7200),
    ],

    'upload_dir' => Env::get('UPLOAD_DIR') ?: $root . '/public/uploads',
    'max_upload_mb' => Env::int('MAX_UPLOAD_MB', 8),
    'rate_limit_login' => Env::int('RATE_LIMIT_LOGIN', 8),
];
