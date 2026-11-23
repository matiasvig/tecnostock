<?php

require_once __DIR__ . '/includes/init.php';

requiere_login();

$pdo = conexion();

$totalProductos = (int) $pdo->query('SELECT COUNT(*) FROM producto')->fetchColumn();
$productosActivos = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1')->fetchColumn();
$unidades = (int) $pdo->query('SELECT COALESCE(SUM(stock_actual), 0) FROM producto WHERE estado = 1')->fetchColumn();
$valorizado = (float) $pdo->query('SELECT COALESCE(SUM(stock_actual * precio), 0) FROM producto WHERE estado = 1')->fetchColumn();
$bajoStock = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo')->fetchColumn();
$agotados = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual = 0 AND stock_actual < stock_minimo')->fetchColumn();
$productosOk = max(0, $productosActivos - $bajoStock);
$bajoSinAgotar = max(0, $bajoStock - $agotados);

$valorCategorias = $pdo->query(
    'SELECT c.nombre, COUNT(p.id_producto) AS productos, COALESCE(SUM(p.stock_actual * p.precio), 0) AS valor
     FROM categoria c
     LEFT JOIN producto p ON p.id_categoria = c.id_categoria AND p.estado = 1
     GROUP BY c.id_categoria, c.nombre
     ORDER BY valor DESC, c.nombre ASC
     LIMIT 5'
)->fetchAll();

$maxValorCategoria = 0.0;
foreach ($valorCategorias as $categoria) {
    $maxValorCategoria = max($maxValorCategoria, (float) $categoria['valor']);
}

$pctOk = $productosActivos > 0 ? $productosOk / $productosActivos * 100 : 0;
$pctBajo = $productosActivos > 0 ? $bajoSinAgotar / $productosActivos * 100 : 0;
$pctAgotado = $productosActivos > 0 ? $agotados / $productosActivos * 100 : 0;
$corteOk = $pctOk;
$corteBajo = $pctOk + $pctBajo;

$productosBajo = $pdo->query(
    'SELECT p.id_producto, p.codigo, p.nombre, p.stock_actual, p.stock_minimo, c.nombre AS categoria
     FROM producto p
     INNER JOIN categoria c ON c.id_categoria = p.id_categoria
     WHERE p.estado = 1 AND p.stock_actual < p.stock_minimo
     ORDER BY (p.stock_actual = 0) DESC, (p.stock_minimo - p.stock_actual) DESC, p.nombre ASC
     LIMIT 6'
)->fetchAll();

$ultimosMovimientos = $pdo->query(
    'SELECT m.tipo, m.cantidad, m.fecha_hora, m.stock_resultante, p.codigo, p.nombre AS producto, u.nombre AS usuario
     FROM movimiento m
     INNER JOIN producto p ON p.id_producto = m.id_producto
     INNER JOIN usuario u ON u.id_usuario = m.id_usuario
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
     LIMIT 5'
)->fetchAll();

$usuario = usuario_actual();
$primerNombre = explode(' ', trim($usuario['nombre']))[0];
$saludo = (int) date('G') < 12 ? 'Buenos días' : ((int) date('G') < 20 ? 'Buenas tardes' : 'Buenas noches');

$titulo = 'Panel';
$activo = 'panel';

require __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1><?= e($saludo) ?>, <?= e($primerNombre) ?></h1>
        <p class="pagina-subtitulo">Estado del inventario al <?= e(date('d-m-Y')) ?>.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= url('movimientos/registrar.php') ?>">
            <i class="bi bi-arrow-left-right me-1"></i> Registrar movimiento
        </a>
        <a class="btn btn-primary" href="<?= url('productos/crear.php') ?>">
            <i class="bi bi-plus-lg me-1"></i> Nuevo producto
        </a>
    </div>
</div>

<?php if ($bajoStock > 0): ?>
    <div class="banner-alerta mb-4">
        <span class="banner-icono"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div class="flex-grow-1">
            <strong><?= $bajoStock ?> <?= $bajoStock === 1 ? 'producto requiere' : 'productos requieren' ?> reposición</strong>
            <span>El stock actual de algunos productos está por debajo del mínimo definido<?= $agotados > 0 ? ', entre ellos ' . $agotados . ($agotados === 1 ? ' producto sin unidades disponibles' : ' productos sin unidades disponibles') : '' ?>.</span>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('productos/index.php?bajo_stock=1') ?>">Revisar</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="tarjeta kpi">
            <div class="kpi-icono"><i class="bi bi-box-seam"></i></div>
            <div class="kpi-etiqueta">Productos activos</div>
            <div class="kpi-valor"><?= number_format($productosActivos, 0, ',', '.') ?></div>
            <div class="kpi-nota"><?= number_format($totalProductos - $productosActivos, 0, ',', '.') ?> desactivados en el catálogo</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta kpi kpi-cian">
            <div class="kpi-icono"><i class="bi bi-boxes"></i></div>
            <div class="kpi-etiqueta">Unidades en stock</div>
            <div class="kpi-valor"><?= number_format($unidades, 0, ',', '.') ?></div>
            <div class="kpi-nota">Suma de existencias disponibles</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta kpi kpi-verde">
            <div class="kpi-icono"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-etiqueta">Inventario valorizado</div>
            <div class="kpi-valor kpi-valor-dinero"><?= formatear_precio($valorizado) ?></div>
            <div class="kpi-nota">Stock por precio de venta</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="tarjeta kpi <?= $bajoStock > 0 ? 'kpi-ambar' : 'kpi-verde' ?>">
            <div class="kpi-icono"><i class="bi bi-bell"></i></div>
            <div class="kpi-etiqueta">Alertas de stock</div>
            <div class="kpi-valor"><?= number_format($bajoStock, 0, ',', '.') ?></div>
            <div class="kpi-nota"><?= $bajoStock > 0 ? 'Productos bajo el mínimo' : 'Sin alertas pendientes' ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="tarjeta tarjeta-salud h-100">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Salud del inventario</h2>
                    <p>Distribución de los productos activos</p>
                </div>
                <span class="chip-suave"><?= number_format($pctOk, 0, ',', '.') ?>% óptimo</span>
            </div>
            <div class="tarjeta-cuerpo">
                <?php if ($productosActivos === 0): ?>
                    <div class="vacio">
                        <i class="bi bi-box"></i>
                        <strong>No hay productos activos</strong>
                        <span>Registre un producto para comenzar a medir el inventario.</span>
                    </div>
                <?php else: ?>
                    <div class="salud">
                        <div
                            class="dona"
                            role="img"
                            aria-label="<?= $productosOk ?> productos óptimos, <?= $bajoSinAgotar ?> bajo el mínimo y <?= $agotados ?> agotados"
                            style="background: conic-gradient(#12b76a 0 <?= number_format($corteOk, 2, '.', '') ?>%, #f79009 <?= number_format($corteOk, 2, '.', '') ?>% <?= number_format($corteBajo, 2, '.', '') ?>%, #f04438 <?= number_format($corteBajo, 2, '.', '') ?>% 100%);"
                        >
                            <div class="dona-centro">
                                <strong><?= number_format($pctOk, 0, ',', '.') ?>%</strong>
                                <span>óptimo</span>
                            </div>
                        </div>
                        <div class="medidor-leyenda">
                            <div class="ok">
                                <strong><?= number_format($productosOk, 0, ',', '.') ?></strong>
                                <span>Óptimo</span>
                            </div>
                            <div class="bajo">
                                <strong><?= number_format($bajoSinAgotar, 0, ',', '.') ?></strong>
                                <span>Bajo el mínimo</span>
                            </div>
                            <div class="agotado">
                                <strong><?= number_format($agotados, 0, ',', '.') ?></strong>
                                <span>Agotados</span>
                            </div>
                        </div>
                    </div>
                    <div class="salud-pie">
                        <i class="bi bi-info-circle"></i>
                        <span>
                            <?php if ($bajoStock > 0): ?>
                                <?= $bajoStock ?> <?= $bajoStock === 1 ? 'producto requiere' : 'productos requieren' ?> reposición.
                            <?php else: ?>
                                Todos los productos activos están sobre el mínimo.
                            <?php endif; ?>
                        </span>
                        <?php if ($bajoStock > 0): ?>
                            <a href="<?= url('productos/index.php?bajo_stock=1') ?>">Revisar <i class="bi bi-arrow-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Valor por categoría</h2>
                    <p>Stock activo valorizado a precio de venta</p>
                </div>
            </div>
            <div class="tarjeta-cuerpo">
                <?php foreach ($valorCategorias as $categoria): ?>
                    <?php $pctCategoria = $maxValorCategoria > 0 ? (float) $categoria['valor'] / $maxValorCategoria * 100 : 0; ?>
                    <div class="barra-categoria">
                        <div class="barra-categoria-cabecera">
                            <span title="<?= e($categoria['nombre']) ?>"><?= e($categoria['nombre']) ?></span>
                            <strong><?= formatear_precio((float) $categoria['valor']) ?></strong>
                        </div>
                        <div class="barra-categoria-pista">
                            <span style="width: <?= number_format($pctCategoria, 2, '.', '') ?>%"></span>
                        </div>
                        <small><?= (int) $categoria['productos'] ?> <?= (int) $categoria['productos'] === 1 ? 'producto activo' : 'productos activos' ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="tarjeta h-100">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Reposición prioritaria</h2>
                    <p>Productos activos con stock bajo o agotado</p>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= url('productos/index.php?bajo_stock=1') ?>">Ver todos</a>
            </div>

            <?php if ($productosBajo === []): ?>
                <div class="vacio">
                    <i class="bi bi-check-circle"></i>
                    <strong>Todo el inventario está sobre el mínimo</strong>
                    <span>No hay productos que requieran reposición en este momento.</span>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table tabla tabla-compacta">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="col-stock">Stock</th>
                                <th class="d-none d-sm-table-cell col-minimo">Mínimo</th>
                                <th class="text-end col-acciones">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productosBajo as $producto): ?>
                                <?php $estado = estado_stock((int) $producto['stock_actual'], (int) $producto['stock_minimo']); ?>
                                <tr>
                                    <td class="celda-producto">
                                        <strong><?= e($producto['nombre']) ?></strong>
                                        <small><span class="codigo"><?= e($producto['codigo']) ?></span> · <?= e($producto['categoria']) ?></small>
                                    </td>
                                    <td>
                                        <span class="stock-numero <?= e($estado['clase']) ?>"><?= (int) $producto['stock_actual'] ?></span>
                                    </td>
                                    <td class="d-none d-sm-table-cell"><span class="stock-minimo"><?= (int) $producto['stock_minimo'] ?></span></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('movimientos/registrar.php?producto=' . (int) $producto['id_producto'] . '&tipo=entrada') ?>">
                                            Reponer
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="tarjeta h-100">
            <div class="tarjeta-cabecera">
                <div>
                    <h2>Últimos movimientos</h2>
                    <p>Entradas y salidas registradas</p>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= url('movimientos/index.php') ?>">Ver todos</a>
            </div>

            <?php if ($ultimosMovimientos === []): ?>
                <div class="vacio">
                    <i class="bi bi-arrow-left-right"></i>
                    <strong>Aún no hay movimientos</strong>
                    <span>Registre una entrada para comenzar a operar el inventario.</span>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush lista-mov">
                    <?php foreach ($ultimosMovimientos as $movimiento): ?>
                        <div class="list-group-item d-flex align-items-center gap-3">
                            <span class="mov-icono <?= e($movimiento['tipo']) ?>">
                                <i class="bi <?= $movimiento['tipo'] === 'entrada' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                            </span>
                            <div class="flex-grow-1 overflow-hidden">
                                <strong><?= e($movimiento['producto']) ?></strong>
                                <small><?= e($movimiento['codigo']) ?> · <?= e(ucfirst($movimiento['tipo'])) ?> · <?= e($movimiento['usuario']) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="cantidad-mov <?= e($movimiento['tipo']) ?>">
                                    <?= $movimiento['tipo'] === 'entrada' ? '+' : '−' ?><?= (int) $movimiento['cantidad'] ?>
                                </span>
                                <small class="d-block"><?= e(formatear_fecha($movimiento['fecha_hora'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
