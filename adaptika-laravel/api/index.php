<?php

// Enable verbose error reporting for Vercel debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Force debug mode on Vercel to inspect exact stack traces if error occurs
putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG'] = 'true';
$_SERVER['APP_DEBUG'] = 'true';

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

// Prepare writable storage directories in /tmp for Vercel Serverless environment
$storageDirs = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache'
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Database setup: SQLite fallback
$localDb = __DIR__ . '/../database/database.sqlite';
$tmpDb = '/tmp/database.sqlite';
if (file_exists($localDb) && !file_exists($tmpDb)) {
    @copy($localDb, $tmpDb);
} elseif (!file_exists($tmpDb)) {
    @touch($tmpDb);
}

// Forward request to Laravel public entrypoint
require __DIR__ . '/../public/index.php';
