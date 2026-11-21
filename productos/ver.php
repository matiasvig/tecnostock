<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();
$idProducto = (int) ($_GET['id'] ?? 0);

if ($idProducto <= 0) {
    flash('danger', 'No se indicó un producto válido.');
    redirigir('productos/index.php');
}

$consulta = $pdo->prepare(
    'SELECT p.*, c.nombre AS categoria, c.estado AS categoria_estado
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

$movimientos = $pdo->prepare(
    'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.observacion, m.stock_resultante, u.nombre AS usuario
     FROM movimiento m
     INNER JOIN usuario u ON u.id_usuario = m.id_usuario
     WHERE m.id_producto = :id
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC'
);
$movimientos->execute(['id' => $idProducto]);
$historial = $movimientos->fetchAll();

$totalEntradas = 0;
$totalSalidas = 0;

foreach ($historial as $movimiento) {
    if ($movimiento['tipo'] === 'entrada') {
        $totalEntradas += (int) $movimiento['cantidad'];
    } else {
        $totalSalidas += (int) $movimiento['cantidad'];
    }
}

$estadoStock = estado_stock((int) $producto['stock_actual'], (int) $producto['stock_minimo']);
$estaActivo = (int) $producto['estado'] === 1;
$valorizado = (float) $producto['precio'] * (int) $producto['stock_actual'];

$titulo = 'Detalle de producto';
$activo = 'productos';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="codigo"><?= e($producto['codigo']) ?></span>
            <span class="badge-estado <?= $estaActivo ? 'activo' : 'inactivo' ?>">
                <?= $estaActivo ? 'Activo' : 'Desactivado' ?>
            </span>
            <?php if ($estaActivo): ?>
                <span class="badge-stock <?= e($estadoStock['clase']) ?>">
                    <i class="bi <?= e($estadoStock['icono']) ?>"></i><?= e($estadoStock['texto']) ?>
                </span>
            <?php endif; ?>
        </div>
        <h1><?= e($producto['nombre']) ?></h1>
        <p class="pagina-subtitulo"><?= e($producto['categoria']) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= url('productos/editar.php?id=' . $idProducto) ?>">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
        <?php if ($estaActivo): ?>
            <a class="btn btn-primary" href="<?= url('movimientos/registrar.php?producto=' . $idProducto) ?>">
                <i class="bi bi-arrow-left-right me-1"></i> Movimiento
            </a>
            <form method="post" action="<?= url('productos/estado.php') ?>" data-confirmar="El producto dejará de admitir movimientos nuevos, pero conservará todo su historial." data-confirmar-titulo="Desactivar producto" data-confirmar-boton="Desactivar">
                <?= campo_csrf() ?>
                <input type="hidden" name="id_producto" value="<?= $idProducto ?>">
                <input type="hidden" name="accion" value="desactivar">
                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-slash-circle me-1"></i> Desactivar</button>
            </form>
        <?php else: ?>
            <form method="post" action="<?= url('productos/estado.php') ?>">
                <?= campo_csrf() ?>
                <input type="hidden" name="id_producto" value="<?= $idProducto ?>">
                <input type="hidden" name="accion" value="activar">
                <button class="btn btn-outline-success" type="submit"><i class="bi bi-arrow-counterclockwise me-1"></i> Reactivar</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($estaActivo && (int) $producto['stock_actual'] < (int) $producto['stock_minimo']): ?>
    <div class="banner-alerta mb-4">
        <span class="banner-icono"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div class="flex-grow-1">
            <strong>Este producto requiere reposición</strong>
            <span>
                El stock actual (<?= (int) $producto['stock_actual'] ?> unidades) está por debajo del mínimo definido
                (<?= (int) $producto['stock_minimo'] ?> unidades).
            </span>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('movimientos/registrar.php?producto=' . $idProducto . '&tipo=entrada') ?>">Reponer</a>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="tarjeta mb-4">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Información del producto</h2>
                    <p>Datos comerciales y de control</p>
                </div>
            </div>
            <div class="tarjeta-cuerpo">
                <div class="detalle-grid mb-4">
                    <div class="detalle-item">
                        <span>Categoría</span>
                        <strong><?= e($producto['categoria']) ?><?= (int) $producto['categoria_estado'] === 0 ? ' (desactivada)' : '' ?></strong>
                    </div>
                    <div class="detalle-item">
                        <span>Precio unitario</span>
                        <strong><?= formatear_precio($producto['precio']) ?></strong>
                    </div>
                    <div class="detalle-item">
                        <span>Stock actual</span>
                        <strong><?= (int) $producto['stock_actual'] ?> unidades</strong>
                    </div>
                    <div class="detalle-item">
                        <span>Stock mínimo</span>
                        <strong><?= (int) $producto['stock_minimo'] ?> unidades</strong>
                    </div>
                    <div class="detalle-item">
                        <span>Valorizado</span>
                        <strong><?= formatear_precio($valorizado) ?></strong>
                    </div>
                    <div class="detalle-item">
                        <span>Última actualización</span>
                        <strong><?= e(formatear_fecha($producto['actualizado_en'])) ?></strong>
                    </div>
                </div>

                <h2 class="fs-6 mb-2">Descripción</h2>
                <p class="descripcion-producto mb-0">
                    <?= e($producto['descripcion'] ?? 'Sin descripción registrada.') ?>
                </p>
            </div>
        </div>

        <div class="tarjeta">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Historial de movimientos</h2>
                    <p>Kardex del producto, del más reciente al más antiguo</p>
                </div>
                <?php if ($estaActivo): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('movimientos/registrar.php?producto=' . $idProducto) ?>">Registrar</a>
                <?php endif; ?>
            </div>

            <?php if ($historial === []): ?>
                <div class="vacio">
                    <i class="bi bi-arrow-left-right"></i>
                    <strong>Sin movimientos registrados</strong>
                    <span>Registre una entrada para comenzar el historial de este producto.</span>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Stock resultante</th>
                                <th>Usuario</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $movimiento): ?>
                                <tr>
                                    <td class="text-nowrap"><?= e(formatear_fecha($movimiento['fecha_hora'])) ?></td>
                                    <td>
                                        <span class="mov-tipo <?= e($movimiento['tipo']) ?>">
                                            <i class="bi <?= $movimiento['tipo'] === 'entrada' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                                            <?= e(ucfirst($movimiento['tipo'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="cantidad-mov <?= e($movimiento['tipo']) ?>">
                                            <?= $movimiento['tipo'] === 'entrada' ? '+' : '−' ?><?= (int) $movimiento['cantidad'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><strong><?= (int) $movimiento['stock_resultante'] ?></strong></td>
                                    <td><?= e($movimiento['usuario']) ?></td>
                                    <td class="text-muted"><?= e($movimiento['observacion'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="tarjeta mb-4">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Resumen de stock</h2>
                    <p>Movimientos acumulados</p>
                </div>
            </div>
            <div class="tarjeta-cuerpo">
                <div class="resumen-stock mb-3">
                    <div class="fila">
                        <span>Entradas acumuladas</span>
                        <strong class="text-success">+<?= number_format($totalEntradas, 0, ',', '.') ?></strong>
                    </div>
                    <div class="fila">
                        <span>Salidas acumuladas</span>
                        <strong class="text-danger">−<?= number_format($totalSalidas, 0, ',', '.') ?></strong>
                    </div>
                    <div class="fila">
                        <span>Movimientos registrados</span>
                        <strong><?= count($historial) ?></strong>
                    </div>
                    <div class="resultado">
                        <span>Stock actual</span>
                        <strong><?= (int) $producto['stock_actual'] ?> unidades</strong>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <?php if ($estaActivo): ?>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('movimientos/registrar.php?producto=' . $idProducto . '&tipo=entrada') ?>">
                            <i class="bi bi-arrow-down-left me-1"></i> Registrar entrada
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= url('movimientos/registrar.php?producto=' . $idProducto . '&tipo=salida') ?>">
                            <i class="bi bi-arrow-up-right me-1"></i> Registrar salida
                        </a>
                    <?php else: ?>
                        <div class="nota-stock">
                            <i class="bi bi-info-circle"></i>
                            Reactive el producto para registrar nuevos movimientos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tarjeta">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Trazabilidad</h2>
                    <p>Registro de auditoría</p>
                </div>
            </div>
            <div class="tarjeta-cuerpo">
                <div class="fila small d-flex justify-content-between mb-2">
                    <span class="text-muted">Creado</span>
                    <strong><?= e(formatear_fecha($producto['creado_en'])) ?></strong>
                </div>
                <div class="fila small d-flex justify-content-between">
                    <span class="text-muted">Actualizado</span>
                    <strong><?= e(formatear_fecha($producto['actualizado_en'])) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
