<?php
/**
 * CORS Configuration for API requests
 *
 * Allowed origins are read from the CORS_ALLOWED_ORIGINS environment
 * variable (comma-separated). Falls back to a localhost allowlist for
 * local development.
 */

$allowed_origins_env = getenv('CORS_ALLOWED_ORIGINS');
$allowed_origins = $allowed_origins_env
    ? array_map('trim', explode(',', $allowed_origins_env))
    : [
        'http://localhost',
        'http://localhost:8080',
        'http://127.0.0.1',
    ];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

    exit(0);
}

header('Content-Type: application/json');
?>
