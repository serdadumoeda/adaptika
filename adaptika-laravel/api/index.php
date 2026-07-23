<?php

// Verbose error reporting for Vercel debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG'] = 'true';
$_SERVER['APP_DEBUG'] = 'true';

// Set Serverless Environment Variables
putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';
$_SERVER['VERCEL'] = '1';

putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';

// Explicit check for Composer autoload
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo '<div style="font-family:sans-serif; padding:30px; background:#fef2f2; border:1px solid #f87171; border-radius:8px; margin:40px;">';
    echo '<h2 style="color:#991b1b; margin-top:0;">⚠️ Vercel Deployment Error: vendor/autoload.php missing</h2>';
    echo '<p style="color:#7f1d1d;">Dependencies PHP (Composer) belum terpasang di server Vercel.</p>';
    echo '<p style="color:#7f1d1d;">Lokasi vendor yang dicari: <code>' . htmlspecialchars($autoloadPath) . '</code></p>';
    echo '</div>';
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
