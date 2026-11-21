<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();
$errores = [];

$idCategoria = (int) ($_GET['id'] ?? $_POST['id_categoria'] ?? 0);

if ($idCategoria <= 0) {
    flash('danger', 'No se indicó una categoría válida.');
    redirigir('categorias/index.php');
}

$consulta = $pdo->prepare('SELECT id_categoria, nombre, descripcion, estado, creado_en FROM categoria WHERE id_categoria = :id');
$consulta->execute(['id' => $idCategoria]);
$categoria = $consulta->fetch();

if (!$categoria) {
    flash('danger', 'La categoría solicitada no existe.');
    redirigir('categorias/index.php');
}

$conteo = $pdo->prepare(
    'SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END), 0) AS activos
     FROM producto WHERE id_categoria = :id'
);
$conteo->execute(['id' => $idCategoria]);
$resumen = $conteo->fetch();

$valores = [
    'nombre' => $categoria['nombre'],
    'descripcion' => $categoria['descripcion'] ?? '',
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
        $existe = $pdo->prepare('SELECT COUNT(*) FROM categoria WHERE nombre = :nombre AND id_categoria <> :id');
        $existe->execute([
            'nombre' => $valores['nombre'],
            'id' => $idCategoria,
        ]);

        if ((int) $existe->fetchColumn() > 0) {
            $errores['nombre'] = 'Ya existe otra categoría con este nombre.';
        }
    }

    if ($errores === []) {
        try {
            $actualizar = $pdo->prepare(
                'UPDATE categoria SET nombre = :nombre, descripcion = :descripcion WHERE id_categoria = :id'
            );
            $actualizar->execute([
                'nombre' => $valores['nombre'],
                'descripcion' => $valores['descripcion'] !== '' ? $valores['descripcion'] : null,
                'id' => $idCategoria,
            ]);

            flash('success', 'La categoría se actualizó correctamente.');
            redirigir('categorias/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores['nombre'] = 'Ya existe otra categoría con este nombre.';
            } else {
                error_log('Error al actualizar categoría: ' . $e->getMessage());
                $errores['general'] = 'No fue posible actualizar la categoría. Intente nuevamente.';
            }
        }
    }
}

$estaActiva = (int) $categoria['estado'] === 1;
$activos = (int) $resumen['activos'];

$titulo = 'Editar categoría';
$activo = 'categorias';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Editar categoría</h1>
        <p class="pagina-subtitulo"><?= e($categoria['nombre']) ?></p>
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

<form method="post" action="<?= url('categorias/editar.php?id=' . $idCategoria) ?>" class="form-validado" novalidate>
    <?= campo_csrf() ?>
    <input type="hidden" name="id_categoria" value="<?= $idCategoria ?>">

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
                        <h2>Uso de la categoría</h2>
                        <p>Productos asociados</p>
                    </div>
                </div>
                <div class="tarjeta-cuerpo">
                    <div class="resumen-stock mb-3">
                        <div class="fila">
                            <span>Productos activos</span>
                            <strong><?= $activos ?></strong>
                        </div>
                        <div class="fila">
                            <span>Total de productos</span>
                            <strong><?= (int) $resumen['total'] ?></strong>
                        </div>
                        <div class="resultado d-flex align-items-center justify-content-between">
                            <span>Estado</span>
                            <strong><?= $estaActiva ? 'Activa' : 'Desactivada' ?></strong>
                        </div>
                    </div>

                    <?php if ($estaActiva): ?>
                        <form method="post" action="<?= url('categorias/estado.php') ?>" data-confirmar="La categoría dejará de estar disponible para nuevos productos. Solo es posible desactivarla si no tiene productos activos." data-confirmar-titulo="Desactivar categoría" data-confirmar-boton="Desactivar">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="id_categoria" value="<?= $idCategoria ?>">
                            <input type="hidden" name="accion" value="desactivar">
                            <button class="btn btn-outline-danger btn-sm w-100" type="submit" <?= $activos > 0 ? 'disabled' : '' ?>>
                                <i class="bi bi-slash-circle me-1"></i> Desactivar categoría
                            </button>
                        </form>
                        <?php if ($activos > 0): ?>
                            <div class="form-text mt-2">Desactive o reasigne los <?= $activos ?> productos activos antes de desactivar la categoría.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post" action="<?= url('categorias/estado.php') ?>">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="id_categoria" value="<?= $idCategoria ?>">
                            <input type="hidden" name="accion" value="activar">
                            <button class="btn btn-outline-success btn-sm w-100" type="submit">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivar categoría
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-lg me-1"></i> Guardar cambios
                </button>
                <a class="btn btn-light" href="<?= url('categorias/index.php') ?>">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
