<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();
$errores = [];

$idProducto = (int) ($_GET['id'] ?? $_POST['id_producto'] ?? 0);

if ($idProducto <= 0) {
    flash('danger', 'No se indicó un producto válido.');
    redirigir('productos/index.php');
}

$consulta = $pdo->prepare(
    'SELECT p.*, c.nombre AS categoria
     FROM producto p
     INNER JOIN categoria c ON c.id_categoria = p.id_categoria
     WHERE p.id_producto = :id'
);
$consulta->execute(['id' => $idProducto]);
$producto = $consulta->fetch();

if (!$producto) {
    flash('danger', 'El producto solicitado no existe.');
    redirigir('productos/index.php');
}

$categorias = $pdo->query('SELECT id_categoria, nombre, estado FROM categoria ORDER BY nombre ASC')->fetchAll();

$valores = [
    'codigo' => $producto['codigo'],
    'nombre' => $producto['nombre'],
    'id_categoria' => (int) $producto['id_categoria'],
    'descripcion' => $producto['descripcion'] ?? '',
    'precio' => $producto['precio'],
    'stock_minimo' => $producto['stock_minimo'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $valores['codigo'] = mb_strtoupper(trim((string) ($_POST['codigo'] ?? '')));
    $valores['nombre'] = trim((string) ($_POST['nombre'] ?? ''));
    $valores['id_categoria'] = (int) ($_POST['id_categoria'] ?? 0);
    $valores['descripcion'] = trim((string) ($_POST['descripcion'] ?? ''));
    $valores['precio'] = trim((string) ($_POST['precio'] ?? ''));
    $valores['stock_minimo'] = trim((string) ($_POST['stock_minimo'] ?? '0'));

    if (!preg_match('/^[A-Z0-9][A-Z0-9\-]{2,19}$/', $valores['codigo'])) {
        $errores['codigo'] = 'Use entre 3 y 20 caracteres: letras, números o guiones.';
    }

    if (mb_strlen($valores['nombre']) < 3 || mb_strlen($valores['nombre']) > 100) {
        $errores['nombre'] = 'El nombre debe tener entre 3 y 100 caracteres.';
    }

    $categoriaValida = false;
    foreach ($categorias as $categoria) {
        if ((int) $categoria['id_categoria'] === $valores['id_categoria']) {
            $categoriaValida = (int) $categoria['estado'] === 1
                || $valores['id_categoria'] === (int) $producto['id_categoria'];
            break;
        }
    }

    if (!$categoriaValida) {
        $errores['id_categoria'] = 'Seleccione una categoría activa.';
    }

    if (mb_strlen($valores['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    $precioNormalizado = str_replace(',', '.', $valores['precio']);
    if ($precioNormalizado === '' || !is_numeric($precioNormalizado)) {
        $errores['precio'] = 'Ingrese un precio válido.';
    } elseif ((float) $precioNormalizado < 0 || (float) $precioNormalizado > 99999999.99) {
        $errores['precio'] = 'El precio debe estar entre 0 y 99.999.999,99.';
    }

    if (!preg_match('/^\d{1,7}$/', $valores['stock_minimo'])) {
        $errores['stock_minimo'] = 'Ingrese una cantidad entera entre 0 y 9.999.999.';
    }

    if (!isset($errores['codigo'])) {
        $existe = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE codigo = :codigo AND id_producto <> :id');
        $existe->execute([
            'codigo' => $valores['codigo'],
            'id' => $idProducto,
        ]);

        if ((int) $existe->fetchColumn() > 0) {
            $errores['codigo'] = 'Ya existe otro producto registrado con este código.';
        }
    }

    if ($errores === []) {
        try {
            $actualizar = $pdo->prepare(
                'UPDATE producto
                 SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion, precio = :precio,
                     stock_minimo = :stock_minimo, id_categoria = :categoria, actualizado_en = :actualizado
                 WHERE id_producto = :id'
            );
            $actualizar->execute([
                'codigo' => $valores['codigo'],
                'nombre' => $valores['nombre'],
                'descripcion' => $valores['descripcion'] !== '' ? $valores['descripcion'] : null,
                'precio' => round((float) $precioNormalizado, 2),
                'stock_minimo' => (int) $valores['stock_minimo'],
                'categoria' => $valores['id_categoria'],
                'actualizado' => fecha_hora_actual(),
                'id' => $idProducto,
            ]);

            flash('success', 'Los datos del producto se actualizaron correctamente.');
            redirigir('productos/ver.php?id=' . $idProducto);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores['codigo'] = 'Ya existe otro producto registrado con este código.';
            } else {
                error_log('Error al actualizar producto: ' . $e->getMessage());
                $errores['general'] = 'No fue posible actualizar el producto. Intente nuevamente.';
            }
        }
    }
}

$estadoStock = estado_stock((int) $producto['stock_actual'], (int) $producto['stock_minimo']);
$estaActivo = (int) $producto['estado'] === 1;

$titulo = 'Editar producto';
$activo = 'productos';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Editar producto</h1>
        <p class="pagina-subtitulo"><?= e($producto['codigo']) ?> · <?= e($producto['nombre']) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= url('productos/ver.php?id=' . $idProducto) ?>">
            <i class="bi bi-eye me-1"></i> Ver detalle
        </a>
        <a class="btn btn-light" href="<?= url('productos/index.php') ?>">Volver</a>
    </div>
</div>

<?php if (isset($errores['general'])): ?>
    <div class="alert alert-danger alert-sistema mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <span><?= e($errores['general']) ?></span>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('productos/editar.php?id=' . $idProducto) ?>" class="form-validado" novalidate>
    <?= campo_csrf() ?>
    <input type="hidden" name="id_producto" value="<?= $idProducto ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h2>Datos del producto</h2>
                        <p>Actualice la información comercial y el punto de reposición</p>
                    </div>
                </div>
                <div class="tarjeta-cuerpo">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="codigo">Código *</label>
                            <input
                                type="text"
                                class="form-control <?= isset($errores['codigo']) ? 'is-invalid' : '' ?>"
                                id="codigo"
                                name="codigo"
                                value="<?= e($valores['codigo']) ?>"
                                pattern="[A-Za-z0-9][A-Za-z0-9-]{2,19}"
                                maxlength="20"
                                data-mayusculas
                                required
                            >
                            <div class="invalid-feedback"><?= e($errores['codigo'] ?? 'Use entre 3 y 20 caracteres: letras, números o guiones.') ?></div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="nombre">Nombre *</label>
                            <input
                                type="text"
                                class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                                id="nombre"
                                name="nombre"
                                value="<?= e($valores['nombre']) ?>"
                                minlength="3"
                                maxlength="100"
                                required
                            >
                            <div class="invalid-feedback"><?= e($errores['nombre'] ?? 'Ingrese un nombre de entre 3 y 100 caracteres.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="id_categoria">Categoría *</label>
                            <select class="form-select <?= isset($errores['id_categoria']) ? 'is-invalid' : '' ?>" id="id_categoria" name="id_categoria" required>
                                <?php foreach ($categorias as $categoria): ?>
                                    <?php
                                    $esActual = (int) $categoria['id_categoria'] === $valores['id_categoria'];
                                    $inactiva = (int) $categoria['estado'] === 0;
                                    ?>
                                    <option
                                        value="<?= (int) $categoria['id_categoria'] ?>"
                                        <?= $esActual ? 'selected' : '' ?>
                                        <?= $inactiva && !$esActual ? 'disabled' : '' ?>
                                    >
                                        <?= e($categoria['nombre']) ?><?= $inactiva ? ' (desactivada)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errores['id_categoria'] ?? 'Seleccione una categoría.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="precio">Precio unitario *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input
                                    type="number"
                                    class="form-control <?= isset($errores['precio']) ? 'is-invalid' : '' ?>"
                                    id="precio"
                                    name="precio"
                                    value="<?= e($valores['precio']) ?>"
                                    min="0"
                                    max="99999999"
                                    step="1"
                                    data-enteros
                                    required
                                >
                                <div class="invalid-feedback"><?= e($errores['precio'] ?? 'Ingrese un precio válido.') ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="descripcion">Descripción</label>
                            <textarea
                                class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>"
                                id="descripcion"
                                name="descripcion"
                                rows="3"
                                maxlength="255"
                            ><?= e($valores['descripcion']) ?></textarea>
                            <div class="invalid-feedback"><?= e($errores['descripcion'] ?? 'La descripción no puede superar los 255 caracteres.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="stock_minimo">Stock mínimo *</label>
                            <input
                                type="number"
                                class="form-control <?= isset($errores['stock_minimo']) ? 'is-invalid' : '' ?>"
                                id="stock_minimo"
                                name="stock_minimo"
                                value="<?= e($valores['stock_minimo']) ?>"
                                min="0"
                                max="9999999"
                                step="1"
                                data-enteros
                                required
                            >
                            <div class="invalid-feedback"><?= e($errores['stock_minimo'] ?? 'Ingrese una cantidad entera no negativa.') ?></div>
                            <div class="form-text">Valor de referencia para activar la alerta de reposición.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="tarjeta mb-4">
                <div class="tarjeta-cabecera">
                    <div>
                        <h2>Stock actual</h2>
                        <p>Se modifica solo con movimientos</p>
                    </div>
                </div>
                <div class="tarjeta-cuerpo">
                    <div class="d-flex align-items-end justify-content-between mb-3">
                        <div>
                            <div class="kpi-valor mb-0"><?= (int) $producto['stock_actual'] ?></div>
                            <span class="stock-minimo">unidades disponibles</span>
                        </div>
                        <span class="badge-stock <?= e($estadoStock['clase']) ?>">
                            <i class="bi <?= e($estadoStock['icono']) ?>"></i><?= e($estadoStock['texto']) ?>
                        </span>
                    </div>

                    <div class="nota-stock mb-3">
                        <i class="bi bi-info-circle"></i>
                        El stock no se edita directamente: se actualiza al registrar entradas o salidas, para conservar el historial.
                    </div>

                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('movimientos/registrar.php?producto=' . $idProducto . '&tipo=entrada') ?>">
                            <i class="bi bi-arrow-down-left me-1"></i> Registrar entrada
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('movimientos/registrar.php?producto=' . $idProducto . '&tipo=salida') ?>">
                            <i class="bi bi-arrow-up-right me-1"></i> Registrar salida
                        </a>
                    </div>
                </div>
            </div>

            <div class="tarjeta mb-4">
                <div class="tarjeta-cabecera">
                    <div>
                        <h2>Estado del producto</h2>
                        <p>Los productos no se eliminan</p>
                    </div>
                </div>
                <div class="tarjeta-cuerpo">
                    <p class="small text-muted mb-3">
                        <?= $estaActivo ? 'El producto está disponible para registrar movimientos.' : 'El producto está desactivado y no admite movimientos nuevos.' ?>
                    </p>
                    <form method="post" action="<?= url('productos/estado.php') ?>" <?= $estaActivo ? 'data-confirmar="El producto dejará de admitir movimientos nuevos, pero conservará su historial y podrá reactivarse." data-confirmar-titulo="Desactivar producto" data-confirmar-boton="Desactivar"' : '' ?>>
                        <?= campo_csrf() ?>
                        <input type="hidden" name="id_producto" value="<?= $idProducto ?>">
                        <input type="hidden" name="accion" value="<?= $estaActivo ? 'desactivar' : 'activar' ?>">
                        <button class="btn <?= $estaActivo ? 'btn-outline-danger' : 'btn-outline-success' ?> btn-sm w-100" type="submit">
                            <i class="bi <?= $estaActivo ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?> me-1"></i>
                            <?= $estaActivo ? 'Desactivar producto' : 'Reactivar producto' ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-lg me-1"></i> Guardar cambios
                </button>
                <a class="btn btn-light" href="<?= url('productos/ver.php?id=' . $idProducto) ?>">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>


