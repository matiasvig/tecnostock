<?php

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

requiere_invitado();

$errores = [];
$correo = '';
$clave = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $correo = trim((string) ($_POST['correo'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Ingrese un correo electrónico válido.';
    }

    if ($clave === '') {
        $errores['clave'] = 'Ingrese su contraseña.';
    }

    if ($errores === []) {
        $consulta = conexion()->prepare(
            'SELECT id_usuario, nombre, correo, clave_hash, estado FROM usuario WHERE correo = :correo LIMIT 1'
        );
        $consulta->execute(['correo' => $correo]);
        $usuario = $consulta->fetch();

        if ($usuario && (int) $usuario['estado'] === 1 && password_verify($clave, $usuario['clave_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id_usuario' => (int) $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
            ];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $actualizar = conexion()->prepare('UPDATE usuario SET ultimo_acceso = :fecha WHERE id_usuario = :id');
            $actualizar->execute([
                'fecha' => fecha_hora_actual(),
                'id' => (int) $usuario['id_usuario'],
            ]);

            flash('success', 'Bienvenida/o, ' . $usuario['nombre'] . '.');
            redirigir('dashboard.php');
        }

        $errores['general'] = 'El correo o la contraseña no son correctos, o el usuario se encuentra inactivo.';
    }
}

$flashes = obtener_flashes();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0b1324">
    <meta name="color-scheme" content="light">
    <title>Iniciar sesión · <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/estilos.css') ?>">
</head>
<body>
<div class="login-page">
    <section class="login-marca">
        <a class="brand" href="<?= url('login.php') ?>">
            <span class="brand-mark"><i class="bi bi-box-seam"></i></span>
            <span class="brand-texto">
                <strong>TecnoStock</strong>
                <small>Gestión de inventario</small>
            </span>
        </a>

        <div>
            <h1>Todo su inventario, bajo control.</h1>
            <p>Centralice el catálogo de accesorios tecnológicos, registre cada entrada y salida y anticipe la reposición antes de que un producto se agote.</p>
            <ul class="login-lista">
                <li><i class="bi bi-check-circle"></i> Catálogo con búsqueda por código, nombre o categoría</li>
                <li><i class="bi bi-check-circle"></i> Movimientos con actualización automática del stock</li>
                <li><i class="bi bi-check-circle"></i> Alertas de stock bajo y trazabilidad por usuario</li>
            </ul>
        </div>

        <span class="login-pie">Sistema de inventario · TecnoStock <?= date('Y') ?></span>
    </section>

    <section class="login-panel">
        <div class="login-caja">
            <div class="marca-movil">
                <span class="brand-mark"><i class="bi bi-box-seam"></i></span>
                <span>TecnoStock</span>
            </div>

            <h2>Iniciar sesión</h2>
            <p class="login-intro">Ingrese con su cuenta de encargado de inventario.</p>

            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['tipo']) ?> alert-sistema mb-3" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= e($flash['mensaje']) ?></span>
                </div>
            <?php endforeach; ?>

            <?php if (isset($errores['general'])): ?>
                <div class="alert alert-danger alert-sistema mb-3" role="alert">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= e($errores['general']) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= url('login.php') ?>" class="form-validado" novalidate>
                <?= campo_csrf() ?>

                <div class="mb-3">
                    <label class="form-label" for="correo">Correo electrónico</label>
                    <input
                        type="email"
                        class="form-control <?= isset($errores['correo']) ? 'is-invalid' : '' ?>"
                        id="correo"
                        name="correo"
                        value="<?= e($correo) ?>"
                        placeholder="nombre@tecnostock.cl"
                        autocomplete="username"
                        maxlength="120"
                        required
                    >
                    <div class="invalid-feedback"><?= e($errores['correo'] ?? 'Ingrese un correo válido.') ?></div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="clave">Contraseña</label>
                    <div class="input-group">
                        <input
                            type="password"
                            class="form-control <?= isset($errores['clave']) ? 'is-invalid' : '' ?>"
                            id="clave"
                            name="clave"
                            placeholder="Ingrese su contraseña"
                            autocomplete="current-password"
                            maxlength="100"
                            required
                        >
                        <button class="btn ver-clave" type="button" data-ver-clave="#clave" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errores['clave'])): ?>
                        <div class="text-danger small mt-1"><?= e($errores['clave']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary w-100" type="submit">
                    Ingresar al sistema <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>

            <p class="login-intro mt-3 mb-0 small">
                Las credenciales de acceso se encuentran en el archivo README.md del proyecto.
            </p>
        </div>
    </section>
</div>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<script src="<?= url('assets/js/validaciones.js') ?>"></script>
</body>
</html>
