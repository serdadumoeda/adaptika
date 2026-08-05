<?php

// Set Serverless Environment Variables
putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';

putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';

putenv('APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php');
putenv('APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php');
putenv('APP_ROUTES_CACHE=/tmp/bootstrap/cache/routes-v7.php');
putenv('APP_CONFIG_CACHE=/tmp/bootstrap/cache/config.php');
putenv('APP_EVENTS_CACHE=/tmp/bootstrap/cache/events.php');

// Explicit check for Composer autoload
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo 'Vendor autoload missing.';
    exit(1);
}

// Fallback APP_KEY if environment variable is missing on Vercel
if (!getenv('APP_KEY') && empty($_ENV['APP_KEY'])) {
    $fallbackKey = 'base64:c3VwZXJzZWNyZXRrZXlmb3JhZGFwdGlrYXA2MjAyNg==';
    putenv("APP_KEY={$fallbackKey}");
    $_ENV['APP_KEY'] = $fallbackKey;
    $_SERVER['APP_KEY'] = $fallbackKey;
}

// Fallback SESSION_DRIVER and CACHE_STORE to array/cookie for serverless
putenv('SESSION_DRIVER=cookie');
$_ENV['SESSION_DRIVER'] = 'cookie';
putenv('CACHE_STORE=array');
$_ENV['CACHE_STORE'] = 'array';

// Force SQLite setup unless an external DB_HOST is explicitly configured
if (empty($_ENV['DB_HOST']) && empty(getenv('DB_HOST'))) {
    putenv('DB_CONNECTION=sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_CONNECTION'] = 'sqlite';

    $seedDb = __DIR__ . '/../database/database_seed.sqlite';
    $tmpDb = '/tmp/database.sqlite';

    if (file_exists($seedDb) && (!file_exists($tmpDb) || filesize($tmpDb) === 0)) {
        @copy($seedDb, $tmpDb);
    } elseif (!file_exists($tmpDb)) {
        @touch($tmpDb);
    }

    putenv("DB_DATABASE={$tmpDb}");
    $_ENV['DB_DATABASE'] = $tmpDb;
    $_SERVER['DB_DATABASE'] = $tmpDb;
}

// Prepare writable storage directories in /tmp for Vercel Serverless environment
$storageDirs = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/storage/fonts',
    '/tmp/storage/temp',
    '/tmp/bootstrap/cache'
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Forward request to Laravel public entrypoint
require __DIR__ . '/../public/index.php';
