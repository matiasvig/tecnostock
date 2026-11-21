<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();
$errores = [];

$valores = [
    'nombre' => '',
    'descripcion' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $valores['nombre'] = trim((string) ($_POST['nombre'] ?? ''));
    $valores['descripcion'] = trim((string) ($_POST['descripcion'] ?? ''));

    if (mb_strlen($valores['nombre']) < 3 || mb_strlen($valores['nombre']) > 60) {
        $errores['nombre'] = 'El nombre debe tener entre 3 y 60 caracteres.';
    }

    if (mb_strlen($valores['descripcion']) > 180) {
        $errores['descripcion'] = 'La descripción no puede superar los 180 caracteres.';
    }

    if (!isset($errores['nombre'])) {
        $existe = $pdo->prepare('SELECT COUNT(*) FROM categoria WHERE nombre = :nombre');
        $existe->execute(['nombre' => $valores['nombre']]);

        if ((int) $existe->fetchColumn() > 0) {
            $errores['nombre'] = 'Ya existe una categoría con este nombre.';
        }
    }

    if ($errores === []) {
        try {
            $insertar = $pdo->prepare(
                'INSERT INTO categoria (nombre, descripcion, estado, creado_en)
                 VALUES (:nombre, :descripcion, 1, :creado)'
            );
            $insertar->execute([
                'nombre' => $valores['nombre'],
                'descripcion' => $valores['descripcion'] !== '' ? $valores['descripcion'] : null,
                'creado' => fecha_hora_actual(),
            ]);

            flash('success', 'Categoría creada correctamente.');
            redirigir('categorias/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores['nombre'] = 'Ya existe una categoría con este nombre.';
            } else {
                error_log('Error al crear categoría: ' . $e->getMessage());
                $errores['general'] = 'No fue posible crear la categoría. Intente nuevamente.';
            }
        }
    }
}

$titulo = 'Nueva categoría';
$activo = 'categorias';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Nueva categoría</h1>
        <p class="pagina-subtitulo">Agrupe productos por tipo para facilitar la búsqueda.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url('categorias/index.php') ?>">
        <i class="bi bi-arrow-left me-1"></i> Volver al listado
    </a>
</div>

<?php if (isset($errores['general'])): ?>
    <div class="alert alert-danger alert-sistema mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <span><?= e($errores['general']) ?></span>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('categorias/crear.php') ?>" class="form-validado" novalidate>
    <?= campo_csrf() ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h2>Datos de la categoría</h2>
                        <p>El nombre debe ser único</p>
                    </div>
                </div>
                <div class="tarjeta-cuerpo">
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre *</label>
                        <input
                            type="text"
                            class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                            id="nombre"
                            name="nombre"
                            value="<?= e($valores['nombre']) ?>"
                            placeholder="Ej: Smartwatch"
                            minlength="3"
                            maxlength="60"
                            required
                        >
                        <div class="invalid-feedback"><?= e($errores['nombre'] ?? 'Ingrese un nombre de entre 3 y 60 caracteres.') ?></div>
                    </div>

                    <div>
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea
                            class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                            maxlength="180"
                            placeholder="Describa brevemente qué productos agrupa."
                        ><?= e($valores['descripcion']) ?></textarea>
                        <div class="invalid-feedback"><?= e($errores['descripcion'] ?? 'La descripción no puede superar los 180 caracteres.') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="tarjeta mb-4">
                <div class="tarjeta-cabecera">
                    <div>
                        <h2>Consideraciones</h2>
                        <p>Integridad del catálogo</p>
                    </div>
                </div>
                <div class="tarjeta-cuerpo">
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> La categoría se crea activa.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> Solo las categorías activas aparecen al registrar productos.</li>
                        <li><i class="bi bi-check2 text-success me-1"></i> No se puede desactivar una categoría con productos activos.</li>
                    </ul>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-lg me-1"></i> Guardar categoría
                </button>
                <a class="btn btn-light" href="<?= url('categorias/index.php') ?>">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
