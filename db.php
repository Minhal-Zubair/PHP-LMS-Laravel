<?php

/**
 * Local dev config fallback.
 *
 * getenv() only sees variables actually set in the process that started
 * PHP. That's fine for `php -S` in a terminal where you set $env:... first,
 * but it silently fails if:
 *   - you open a NEW terminal without re-setting them, or
 *   - you're served via XAMPP/Apache, which runs as a background service
 *     and never sees PowerShell's $env:/export values at all.
 *
 * To avoid re-diagnosing this every session: create a file named
 * `db_config.local.php` in this same folder (it's gitignored — never
 * commit real credentials) defining the four constants below. If present,
 * it's used automatically, no environment variables required, works the
 * same under `php -S` or Apache/XAMPP.
 *
 * db_config.local.php example:
 * <?php
 * putenv('DB_HOST=127.0.0.1');
 * putenv('DB_USER=root');
 * putenv('DB_PASS=');
 * putenv('DB_NAME=user_db');
 */
$local_config = __DIR__ . '/db_config.local.php';
if (file_exists($local_config)) {
    require_once $local_config;
}

$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

if ($db_host === false || $db_user === false || $db_pass === false || $db_name === false) {
    die(
        'Database configuration missing: set DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables, ' .
        'or create db_config.local.php next to db.php (see comment at the top of db.php for the format).'
    );
}

// Make mysqli throw exceptions on error instead of silently returning false.
// This surfaces real bugs during development instead of letting a query
// fail quietly and continue with an empty/undefined result.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Never leak raw DB error details (host, credentials hints, schema) to the browser.
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Sorry, something went wrong. Please try again later.');
}