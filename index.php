<?php

require_once __DIR__ . '/includes/init.php';

if (esta_autenticado()) {
    redirigir('dashboard.php');
}

redirigir('login.php');
