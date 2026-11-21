<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();
$errores = [];

$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, stock_actual, stock_minimo
     FROM producto
     WHERE estado = 1
     ORDER BY nombre ASC'
)->fetchAll();

$valores = [
    'id_producto' => (int) ($_GET['producto'] ?? 0),
    'tipo' => (string) ($_GET['tipo'] ?? 'entrada'),
    'cantidad' => '1',
    'observacion' => '',
];

if (!in_array($valores['tipo'], ['entrada', 'salida'], true)) {
    $valores['tipo'] = 'entrada';
}

$idsActivos = array_map(static fn (array $producto): int => (int) $producto['id_producto'], $productos);

if (!in_array($valores['id_producto'], $idsActivos, true)) {
    $valores['id_producto'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $valores['id_producto'] = (int) ($_POST['id_producto'] ?? 0);
    $valores['tipo'] = (string) ($_POST['tipo'] ?? '');
    $valores['cantidad'] = trim((string) ($_POST['cantidad'] ?? ''));
    $valores['observacion'] = trim((string) ($_POST['observacion'] ?? ''));

    if (!in_array($valores['tipo'], ['entrada', 'salida'], true)) {
        $errores['tipo'] = 'Seleccione el tipo de movimiento.';
    }

    if ($valores['id_producto'] <= 0) {
        $errores['id_producto'] = 'Seleccione un producto.';
    }

    if (!preg_match('/^\d{1,7}$/', $valores['cantidad']) || (int) $valores['cantidad'] < 1) {
        $errores['cantidad'] = 'Ingrese una cantidad entera mayor que cero.';
    }

    if (mb_strlen($valores['observacion']) > 200) {
        $errores['observacion'] = 'La observación no puede superar los 200 caracteres.';
    }

    if ($errores === []) {
        $idProducto = (int) $valores['id_producto'];
        $tipo = $valores['tipo'];
        $cantidad = (int) $valores['cantidad'];
        $observacion = $valores['observacion'] !== '' ? $valores['observacion'] : null;
        $errorNegocio = null;

        try {
            $pdo->beginTransaction();

            $consulta = $pdo->prepare(
                'SELECT id_producto, codigo, nombre, stock_actual, stock_minimo, estado
                 FROM producto
                 WHERE id_producto = :id
                 FOR UPDATE'
            );
            $consulta->execute(['id' => $idProducto]);
            $producto = $consulta->fetch();

            if (!$producto) {
                $errorNegocio = 'producto';
            } elseif ((int) $producto['estado'] !== 1) {
                $errorNegocio = 'inactivo';
            } else {
                $stockActual = (int) $producto['stock_actual'];
                $stockMinimo = (int) $producto['stock_minimo'];

                if ($tipo === 'salida' && $cantidad > $stockActual) {
                    $errorNegocio = 'stock';
                }
            }

            if ($errorNegocio !== null) {
                $pdo->rollBack();

                if ($errorNegocio === 'stock') {
                    $errores['cantidad'] = 'No hay stock suficiente. Disponible: ' . $stockActual . ' unidades.';
                } elseif ($errorNegocio === 'inactivo') {
                    $errores['id_producto'] = 'El producto está desactivado y no admite movimientos.';
                } else {
                    $errores['id_producto'] = 'El producto seleccionado no existe.';
                }
            } else {
                $nuevoStock = $tipo === 'entrada' ? $stockActual + $cantidad : $stockActual - $cantidad;

                $actualizar = $pdo->prepare(
                    'UPDATE producto
                     SET stock_actual = :nuevo, actualizado_en = :fecha
                     WHERE id_producto = :id AND stock_actual = :anterior'
                );
                $actualizar->execute([
                    'nuevo' => $nuevoStock,
                    'fecha' => fecha_hora_actual(),
                    'id' => $idProducto,
                    'anterior' => $stockActual,
                ]);

                if ($actualizar->rowCount() !== 1) {
                    throw new RuntimeException('El stock cambió durante la operación.');
                }

                $registrar = $pdo->prepare(
                    'INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, stock_resultante, id_producto, id_usuario)
                     VALUES (:fecha, :tipo, :cantidad, :observacion, :stock_resultante, :producto, :usuario)'
                );
                $registrar->execute([
                    'fecha' => fecha_hora_actual(),
                    'tipo' => $tipo,
                    'cantidad' => $cantidad,
                    'observacion' => $observacion,
                    'stock_resultante' => $nuevoStock,
                    'producto' => $idProducto,
                    'usuario' => (int) usuario_actual()['id_usuario'],
                ]);

                $pdo->commit();

                flash(
                    'success',
                    ucfirst($tipo) . ' registrada correctamente: ' . ($tipo === 'entrada' ? '+' : '−') . $cantidad
                    . ' unidades. El stock actual es de ' . $nuevoStock . ' unidades.'
                );

                if ($nuevoStock === 0) {
                    flash('warning', 'Atención: el producto quedó sin unidades disponibles.');
                } elseif ($nuevoStock < $stockMinimo) {
                    flash('warning', 'Atención: el producto quedó bajo el stock mínimo definido (' . $stockMinimo . ' unidades).');
                }

                redirigir('productos/ver.php?id=' . $idProducto);
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Error al registrar movimiento: ' . $e->getMessage());
            $errores['general'] = 'No fue posible registrar el movimiento. Intente nuevamente.';
        }
    }
}

$titulo = 'Registrar movimiento';
$activo = 'movimientos';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Registrar movimiento</h1>
        <p class="pagina-subtitulo">Registre una entrada o salida y actualice el stock automáticamente.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= url('movimientos/index.php') ?>">
        <i class="bi bi-arrow-left me-1"></i> Volver al historial
    </a>
</div>

<?php if (isset($errores['general'])): ?>
    <div class="alert alert-danger alert-sistema mb-4" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <span><?= e($errores['general']) ?></span>
    </div>
<?php endif; ?>

<?php if ($productos === []): ?>
    <div class="tarjeta">
        <div class="vacio">
            <i class="bi bi-box"></i>
            <strong>No hay productos activos</strong>
            <span>Registre o reactive un producto antes de crear movimientos.</span>
            <a class="btn btn-primary btn-sm mt-3" href="<?= url('productos/crear.php') ?>">Nuevo producto</a>
        </div>
    </div>
<?php else: ?>
    <form method="post" action="<?= url('movimientos/registrar.php') ?>" class="form-validado" id="formMovimiento" novalidate>
        <?= campo_csrf() ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="tarjeta">
                    <div class="tarjeta-cabecera">
                        <div>
                            <h2>Datos del movimiento</h2>
                            <p>Todos los campos son obligatorios</p>
                        </div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <div class="mb-4">
                            <label class="form-label" for="producto_id">Producto *</label>
                            <select class="form-select <?= isset($errores['id_producto']) ? 'is-invalid' : '' ?>" id="producto_id" name="id_producto" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option
                                        value="<?= (int) $producto['id_producto'] ?>"
                                        data-stock="<?= (int) $producto['stock_actual'] ?>"
                                        data-minimo="<?= (int) $producto['stock_minimo'] ?>"
                                        data-codigo="<?= e($producto['codigo']) ?>"
                                        data-nombre="<?= e($producto['nombre']) ?>"
                                        <?= $valores['id_producto'] === (int) $producto['id_producto'] ? 'selected' : '' ?>
                                    >
                                        <?= e($producto['codigo']) ?> · <?= e($producto['nombre']) ?> (<?= (int) $producto['stock_actual'] ?> disponibles)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errores['id_producto'] ?? 'Seleccione un producto.') ?></div>
                        </div>

                        <div class="mb-4">
                            <span class="form-label d-block">Tipo de movimiento *</span>
                            <div class="segmento">
                                <input type="radio" id="tipo_entrada" name="tipo" value="entrada" <?= $valores['tipo'] === 'entrada' ? 'checked' : '' ?> required>
                                <label class="entrada" for="tipo_entrada"><i class="bi bi-arrow-down-left"></i> Entrada</label>

                                <input type="radio" id="tipo_salida" name="tipo" value="salida" <?= $valores['tipo'] === 'salida' ? 'checked' : '' ?> required>
                                <label class="salida" for="tipo_salida"><i class="bi bi-arrow-up-right"></i> Salida</label>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="cantidad">Cantidad *</label>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control <?= isset($errores['cantidad']) ? 'is-invalid' : '' ?>"
                                        id="cantidad"
                                        name="cantidad"
                                        value="<?= e($valores['cantidad']) ?>"
                                        min="1"
                                        max="9999999"
                                        step="1"
                                        data-enteros
                                        required
                                    >
                                    <span class="input-group-text">unidades</span>
                                    <div class="invalid-feedback"><?= e($errores['cantidad'] ?? 'Ingrese una cantidad mayor que cero.') ?></div>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <label class="form-label" for="observacion">Observación</label>
                                <input
                                    type="text"
                                    class="form-control <?= isset($errores['observacion']) ? 'is-invalid' : '' ?>"
                                    id="observacion"
                                    name="observacion"
                                    value="<?= e($valores['observacion']) ?>"
                                    maxlength="200"
                                    placeholder="Ej: Venta mostrador, reposición de bodega"
                                >
                                <div class="invalid-feedback"><?= e($errores['observacion'] ?? 'La observación no puede superar los 200 caracteres.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="tarjeta mb-4">
                    <div class="tarjeta-cabecera">
                        <div>
                            <h2>Resumen del movimiento</h2>
                            <p>Cálculo previo del stock resultante</p>
                        </div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <div class="resumen-stock">
                            <div class="fila">
                                <span>Producto</span>
                                <strong id="resumenProducto" class="text-end">Sin seleccionar</strong>
                            </div>
                            <div class="fila">
                                <span>Stock actual</span>
                                <strong id="resumenActual">—</strong>
                            </div>
                            <div class="fila">
                                <span>Stock mínimo</span>
                                <strong id="resumenMinimo">—</strong>
                            </div>
                            <div class="fila">
                                <span>Movimiento</span>
                                <strong id="resumenMovimiento">—</strong>
                            </div>
                            <div class="resultado d-flex align-items-center justify-content-between gap-2" id="resumenResultadoCaja">
                                <span>Stock resultante</span>
                                <strong id="resumenResultado">—</strong>
                            </div>
                            <div class="small mt-2 d-none" id="resumenAlerta"></div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta mb-4">
                    <div class="tarjeta-cabecera">
                        <div>
                            <h2>Reglas aplicadas</h2>
                            <p>Control automático</p>
                        </div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> Una salida no puede dejar el stock bajo cero.</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> La cantidad debe ser mayor que cero.</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> Se registra la fecha, el usuario y el stock resultante.</li>
                            <li><i class="bi bi-check2 text-success me-1"></i> La alerta de stock bajo se activa al confirmar.</li>
                        </ul>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check-lg me-1"></i> Registrar movimiento
                    </button>
                    <a class="btn btn-light" href="<?= url('movimientos/index.php') ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
