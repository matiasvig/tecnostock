<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();
$errores = [];

$valores = [
    'codigo' => '',
    'nombre' => '',
    'id_categoria' => 0,
    'descripcion' => '',
    'precio' => '',
    'stock_inicial' => '0',
    'stock_minimo' => '0',
];

$categorias = $pdo->query(
    'SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre ASC'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $valores['codigo'] = mb_strtoupper(trim((string) ($_POST['codigo'] ?? '')));
    $valores['nombre'] = trim((string) ($_POST['nombre'] ?? ''));
    $valores['id_categoria'] = (int) ($_POST['id_categoria'] ?? 0);
    $valores['descripcion'] = trim((string) ($_POST['descripcion'] ?? ''));
    $valores['precio'] = trim((string) ($_POST['precio'] ?? ''));
    $valores['stock_inicial'] = trim((string) ($_POST['stock_inicial'] ?? '0'));
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
            $categoriaValida = true;
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

    if (!preg_match('/^\d{1,7}$/', $valores['stock_inicial'])) {
        $errores['stock_inicial'] = 'Ingrese una cantidad entera entre 0 y 9.999.999.';
    }

    if (!preg_match('/^\d{1,7}$/', $valores['stock_minimo'])) {
        $errores['stock_minimo'] = 'Ingrese una cantidad entera entre 0 y 9.999.999.';
    }

    if (!isset($errores['codigo'])) {
        $existe = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE codigo = :codigo');
        $existe->execute(['codigo' => $valores['codigo']]);

        if ((int) $existe->fetchColumn() > 0) {
            $errores['codigo'] = 'Ya existe un producto registrado con este código.';
        }
    }

    if ($errores === []) {
        $precio = round((float) $precioNormalizado, 2);
        $stockInicial = (int) $valores['stock_inicial'];
        $stockMinimo = (int) $valores['stock_minimo'];
        $ahora = fecha_hora_actual();

        try {
            $pdo->beginTransaction();

            $insertar = $pdo->prepare(
                'INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria, creado_en, actualizado_en)
                 VALUES (:codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, 1, :categoria, :creado, :actualizado)'
            );
            $insertar->execute([
                'codigo' => $valores['codigo'],
                'nombre' => $valores['nombre'],
                'descripcion' => $valores['descripcion'] !== '' ? $valores['descripcion'] : null,
                'precio' => $precio,
                'stock_actual' => $stockInicial,
                'stock_minimo' => $stockMinimo,
                'categoria' => $valores['id_categoria'],
                'creado' => $ahora,
                'actualizado' => $ahora,
            ]);

            $idProducto = (int) $pdo->lastInsertId();

            if ($stockInicial > 0) {
                $movimiento = $pdo->prepare(
                    'INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, stock_resultante, id_producto, id_usuario)
                     VALUES (:fecha, :tipo, :cantidad, :observacion, :stock_resultante, :producto, :usuario)'
                );
                $movimiento->execute([
                    'fecha' => $ahora,
                    'tipo' => 'entrada',
                    'cantidad' => $stockInicial,
                    'observacion' => 'Stock inicial del producto',
                    'stock_resultante' => $stockInicial,
                    'producto' => $idProducto,
                    'usuario' => (int) usuario_actual()['id_usuario'],
                ]);
            }

            $pdo->commit();

            flash('success', 'Producto registrado correctamente.' . ($stockInicial > 0 ? ' Se cargaron ' . $stockInicial . ' unidades como stock inicial.' : ''));
            redirigir('productos/ver.php?id=' . $idProducto);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() === '23000') {
                $errores['codigo'] = 'Ya existe un producto registrado con este código.';
            } else {
                error_log('Error al registrar producto: ' . $e->getMessage());
                $errores['general'] = 'No fue posible registrar el producto. Intente nuevamente.';
            }
        }
    }
}

$titulo = 'Nuevo producto';
$activo = 'productos';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Nuevo producto</h1>
        <p class="pagina-subtitulo">Registre un accesorio en el catálogo y defina su stock mínimo.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url('productos/index.php') ?>">
        <i class="bi bi-arrow-left me-1"></i> Volver al listado
    </a>
</div>

<?php if (isset($errores['general'])): ?>
    <div class="alert alert-danger alert-sistema mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <span><?= e($errores['general']) ?></span>
    </div>
<?php endif; ?>

<form method="post" action="<?= url('productos/crear.php') ?>" class="form-validado" novalidate>
    <?= campo_csrf() ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="tarjeta">
                <div class="tarjeta-cabecera">
                    <div>
                        <h2>Datos del producto</h2>
                        <p>Los campos marcados con * son obligatorios</p>
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
                                placeholder="TEC-016"
                                pattern="[A-Za-z0-9][A-Za-z0-9-]{2,19}"
                                maxlength="20"
                                data-mayusculas
                                required
                            >
                            <div class="invalid-feedback"><?= e($errores['codigo'] ?? 'Use entre 3 y 20 caracteres: letras, números o guiones.') ?></div>
                            <div class="form-text">El código debe ser único en el catálogo.</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="nombre">Nombre *</label>
                            <input
                                type="text"
                                class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>"
                                id="nombre"
                                name="nombre"
                                value="<?= e($valores['nombre']) ?>"
                                placeholder="Ej: Cargador rápido 45W USB-C"
                                minlength="3"
                                maxlength="100"
                                required
                            >
                            <div class="invalid-feedback"><?= e($errores['nombre'] ?? 'Ingrese un nombre de entre 3 y 100 caracteres.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="id_categoria">Categoría *</label>
                            <select class="form-select <?= isset($errores['id_categoria']) ? 'is-invalid' : '' ?>" id="id_categoria" name="id_categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= (int) $categoria['id_categoria'] ?>" <?= $valores['id_categoria'] === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                                        <?= e($categoria['nombre']) ?>
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
                                    placeholder="0"
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
                                placeholder="Características, color, compatibilidad u otra información útil."
                            ><?= e($valores['descripcion']) ?></textarea>
                            <div class="invalid-feedback"><?= e($errores['descripcion'] ?? 'La descripción no puede superar los 255 caracteres.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="stock_inicial">Stock inicial *</label>
                            <input
                                type="number"
                                class="form-control <?= isset($errores['stock_inicial']) ? 'is-invalid' : '' ?>"
                                id="stock_inicial"
                                name="stock_inicial"
                                value="<?= e($valores['stock_inicial']) ?>"
                                min="0"
                                max="9999999"
                                step="1"
                                data-enteros
                                required
                            >
                            <div class="invalid-feedback"><?= e($errores['stock_inicial'] ?? 'Ingrese una cantidad entera no negativa.') ?></div>
                            <div class="form-text">Si es mayor que cero, se registrará una entrada de stock inicial.</div>
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
                            <div class="form-text">Se alertará cuando el stock actual quede por debajo de este valor.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="tarjeta mb-4">
                <div class="tarjeta-cabecera">
                    <div>
                        <h2>Control de existencias</h2>
                        <p>Trazabilidad desde el primer día</p>
                    </div>
                </div>
                <div class="tarjeta-cuerpo">
                    <div class="nota-stock mb-3">
                        <i class="bi bi-info-circle"></i>
                        Todo cambio de stock queda respaldado por un movimiento con fecha, usuario y cantidad.
                    </div>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> El código no puede repetirse.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> El precio y las cantidades no pueden ser negativos.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> El stock inicial genera una entrada automática.</li>
                        <li><i class="bi bi-check2 text-success me-1"></i> La alerta se activa si el stock queda bajo el mínimo.</li>
                    </ul>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-lg me-1"></i> Guardar producto
                </button>
                <a class="btn btn-light" href="<?= url('productos/index.php') ?>">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>


