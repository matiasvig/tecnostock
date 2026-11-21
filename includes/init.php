<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

date_default_timezone_set(APP_ZONA_HORARIA);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('TECNOSTOCKSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

define('BASE_URL', detectar_base_url());

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}
