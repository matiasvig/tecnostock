<?php

function token_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(token_csrf()) . '">';
}

function validar_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(token_csrf(), $token)) {
        http_response_code(419);
        flash('danger', 'La sesión expiró o la solicitud no es válida. Intente nuevamente.');
        redirigir('dashboard.php');
    }
}
