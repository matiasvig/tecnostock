<?php
$usuario = usuario_actual();
$activo = $activo ?? '';
$titulo = $titulo ?? APP_NOMBRE;
$flashes = obtener_flashes();

$iconosFlash = [
    'success' => 'bi-check-circle-fill',
    'danger' => 'bi-exclamation-octagon-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'info' => 'bi-info-circle-fill',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="description" content="Sistema de inventario para TecnoStock">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0b1324">
    <meta name="color-scheme" content="light">
    <title><?= e($titulo) ?> · <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/estilos.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= url('dashboard.php') ?>">
            <span class="brand-mark"><i class="bi bi-box-seam"></i></span>
            <span class="brand-texto">
                <strong>TecnoStock</strong>
                <small>Gestión de inventario</small>
            </span>
        </a>

        <nav class="sidebar-nav" aria-label="Menú principal">
            <span class="nav-etiqueta">Operación</span>
            <a class="nav-item <?= $activo === 'panel' ? 'activo' : '' ?>" href="<?= url('dashboard.php') ?>">
                <i class="bi bi-grid-1x2"></i><span>Panel</span>
            </a>
            <a class="nav-item <?= $activo === 'productos' ? 'activo' : '' ?>" href="<?= url('productos/index.php') ?>">
                <i class="bi bi-box-seam"></i><span>Productos</span>
            </a>
            <a class="nav-item <?= $activo === 'movimientos' ? 'activo' : '' ?>" href="<?= url('movimientos/index.php') ?>">
                <i class="bi bi-arrow-left-right"></i><span>Movimientos</span>
            </a>
            <a class="nav-item <?= $activo === 'categorias' ? 'activo' : '' ?>" href="<?= url('categorias/index.php') ?>">
                <i class="bi bi-tags"></i><span>Categorías</span>
            </a>
        </nav>

        <div class="sidebar-pie">
            <div class="sidebar-nota">
                <i class="bi bi-shield-check"></i>
                <div>
                    <strong>Datos protegidos</strong>
                    <span>Sesiones y consultas seguras</span>
                </div>
            </div>
        </div>
    </aside>

    <div class="sidebar-fondo" id="sidebarFondo"></div>

    <div class="app-main">
        <header class="topbar">
            <button class="btn-icono d-lg-none" type="button" id="btnMenu" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>

            <div class="topbar-titulo">
                <span><?= e($titulo) ?></span>
            </div>

            <div class="dropdown">
                <button class="usuario-chip" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar"><?= e(iniciales($usuario['nombre'] ?? 'Usuario')) ?></span>
                    <span class="usuario-datos d-none d-sm-flex">
                        <strong><?= e($usuario['nombre'] ?? 'Usuario') ?></strong>
                        <small><?= e($usuario['correo'] ?? '') ?></small>
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">Sesión iniciada</h6></li>
                    <li><span class="dropdown-item-text small text-muted"><?= e($usuario['correo'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= url('logout.php') ?>">
                            <?= campo_csrf() ?>
                            <button class="dropdown-item texto-peligro" type="submit">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="app-contenido">
            <?php foreach ($flashes as $flash): ?>
                <?php $tipo = isset($iconosFlash[$flash['tipo']]) ? $flash['tipo'] : 'info'; ?>
                <div class="alert alert-<?= e($tipo) ?> alert-dismissible fade show alert-sistema" role="alert">
                    <i class="bi <?= e($iconosFlash[$tipo]) ?>"></i>
                    <span><?= e($flash['mensaje']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>
