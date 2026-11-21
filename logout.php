<?php

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('dashboard.php');
}

validar_csrf();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}

session_destroy();
session_start();
session_regenerate_id(true);
flash('success', 'Su sesión se cerró correctamente.');
redirigir('login.php');
