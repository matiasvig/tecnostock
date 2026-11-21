<?php

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function esta_autenticado(): bool
{
    return isset($_SESSION['usuario']['id_usuario']);
}

function requiere_login(): void
{
    if (!esta_autenticado()) {
        flash('warning', 'Debe iniciar sesión para acceder al sistema.');
        redirigir('login.php');
    }
}

function requiere_invitado(): void
{
    if (esta_autenticado()) {
        redirigir('dashboard.php');
    }
}
