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

putenv('APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php');
putenv('APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php');
putenv('APP_ROUTES_CACHE=/tmp/bootstrap/cache/routes-v7.php');
putenv('APP_CONFIG_CACHE=/tmp/bootstrap/cache/config.php');
putenv('APP_EVENTS_CACHE=/tmp/bootstrap/cache/events.php');

// Explicit check for Composer autoload
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(200);
    echo '<div style="font-family:sans-serif; padding:30px; background:#fef2f2; border:1px solid #f87171; border-radius:8px; margin:40px;">';
    echo '<h2 style="color:#991b1b; margin-top:0;">⚠️ Vercel Deployment Error: vendor/autoload.php missing</h2>';
    echo '<p style="color:#7f1d1d;">Dependencies PHP (Composer) belum terpasang di server Vercel.</p>';
    echo '<p style="color:#7f1d1d;">Lokasi vendor yang dicari: <code>' . htmlspecialchars($autoloadPath) . '</code></p>';
    echo '</div>';
    exit(0);
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
    '/tmp/bootstrap/cache'
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Forward request to Laravel public entrypoint with Throwable Debugger Wrapper
try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(200);
    echo '<div style="font-family:sans-serif; padding:30px; background:#fff1f2; border:2px solid #e11d48; border-radius:12px; margin:20px;">';
    echo '<h2 style="color:#9f1239; margin-top:0;">⚠️ ADAPTIKA Vercel Exception Debugger</h2>';
    echo '<p style="font-size:16px;"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p style="font-size:14px; color:#4c0519;"><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';

    $curr = $e;
    $depth = 1;
    while ($prev = $curr->getPrevious()) {
        echo '<div style="background:#fecdd3; padding:15px; border-radius:8px; margin:15px 0;">';
        echo '<h3 style="color:#881337; margin-top:0;">Root Cause Exception #' . $depth . ':</h3>';
        echo '<p style="font-size:15px; color:#881337;"><strong>Root Error:</strong> ' . htmlspecialchars($prev->getMessage()) . '</p>';
        echo '<p style="font-size:13px; color:#881337;"><strong>Root File:</strong> ' . htmlspecialchars($prev->getFile()) . ':' . $prev->getLine() . '</p>';
        echo '<pre style="background:#4c0519; color:#fff; padding:10px; border-radius:6px; font-size:11px; overflow-x:auto;">' . htmlspecialchars($prev->getTraceAsString()) . '</pre>';
        echo '</div>';
        $curr = $prev;
        $depth++;
    }

    echo '<h3 style="color:#9f1239;">Stack Trace:</h3>';
    echo '<pre style="background:#881337; color:#fff; padding:15px; border-radius:8px; overflow-x:auto; font-size:12px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
    exit(0);
}
