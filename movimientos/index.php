<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();

$buscar = trim((string) ($_GET['q'] ?? ''));
$tipoFiltro = (string) ($_GET['tipo'] ?? '');
$desde = trim((string) ($_GET['desde'] ?? ''));
$hasta = trim((string) ($_GET['hasta'] ?? ''));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

if (!in_array($tipoFiltro, ['entrada', 'salida'], true)) {
    $tipoFiltro = '';
}

if ($desde !== '' && !fecha_iso_valida($desde)) {
    $desde = '';
}

if ($hasta !== '' && !fecha_iso_valida($hasta)) {
    $hasta = '';
}

$condiciones = [];
$parametros = [];

if ($buscar !== '') {
    $condiciones[] = '(p.codigo LIKE :busqueda_codigo OR p.nombre LIKE :busqueda_nombre)';
    $parametros['busqueda_codigo'] = patron_busqueda($buscar);
    $parametros['busqueda_nombre'] = patron_busqueda($buscar);
}

if ($tipoFiltro !== '') {
    $condiciones[] = 'm.tipo = :tipo';
    $parametros['tipo'] = $tipoFiltro;
}

if ($desde !== '') {
    $condiciones[] = 'm.fecha_hora >= :desde';
    $parametros['desde'] = $desde . ' 00:00:00';
}

if ($hasta !== '') {
    $condiciones[] = 'm.fecha_hora <= :hasta';
    $parametros['hasta'] = $hasta . ' 23:59:59';
}

$where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

$conteo = $pdo->prepare(
    'SELECT COUNT(*)
     FROM movimiento m
     INNER JOIN producto p ON p.id_producto = m.id_producto' . $where
);
$conteo->execute($parametros);
$totalRegistros = (int) $conteo->fetchColumn();
$paginacion = datos_paginacion($totalRegistros, $pagina, MOVIMIENTOS_POR_PAGINA);

$consulta = $pdo->prepare(
    'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.observacion, m.stock_resultante,
            p.id_producto, p.codigo, p.nombre AS producto, u.nombre AS usuario
     FROM movimiento m
     INNER JOIN producto p ON p.id_producto = m.id_producto
     INNER JOIN usuario u ON u.id_usuario = m.id_usuario' . $where . '
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
     LIMIT :limite OFFSET :offset'
);

foreach ($parametros as $clave => $valor) {
    $consulta->bindValue(':' . $clave, $valor);
}
$consulta->bindValue(':limite', $paginacion['por_pagina'], PDO::PARAM_INT);
$consulta->bindValue(':offset', $paginacion['offset'], PDO::PARAM_INT);
$consulta->execute();
$movimientos = $consulta->fetchAll();

$titulo = 'Movimientos';
$activo = 'movimientos';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Movimientos</h1>
        <p class="pagina-subtitulo">Historial de entradas y salidas con trazabilidad por usuario.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('movimientos/registrar.php') ?>">
        <i class="bi bi-plus-lg me-1"></i> Registrar movimiento
    </a>
</div>

<div class="tarjeta">
    <form class="filtros" method="get" action="<?= url('movimientos/index.php') ?>">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label" for="q">Producto</label>
                <div class="buscador">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="<?= e($buscar) ?>" placeholder="Código o nombre" maxlength="100">
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label" for="tipo">Tipo</label>
                <select class="form-select" id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Entradas</option>
                    <option value="salida" <?= $tipoFiltro === 'salida' ? 'selected' : '' ?>>Salidas</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label" for="desde">Desde</label>
                <input type="date" class="form-control" id="desde" name="desde" value="<?= e($desde) ?>">
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label" for="hasta">Hasta</label>
                <input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($hasta) ?>">
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                    <a class="btn btn-light btn-sm" href="<?= url('movimientos/index.php') ?>">Limpiar</a>
                </div>
            </div>
        </div>
    </form>

    <?php if ($movimientos === []): ?>
        <div class="vacio">
            <i class="bi bi-arrow-left-right"></i>
            <strong>No se encontraron movimientos</strong>
            <span>Ajuste los filtros o registre una nueva entrada o salida.</span>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Stock resultante</th>
                        <th>Usuario</th>
                        <th>Observación</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td class="text-nowrap"><?= e(formatear_fecha($movimiento['fecha_hora'])) ?></td>
                            <td>
                                <span class="mov-tipo <?= e($movimiento['tipo']) ?>">
                                    <i class="bi <?= $movimiento['tipo'] === 'entrada' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?>"></i>
                                    <?= e(ucfirst($movimiento['tipo'])) ?>
                                </span>
                            </td>
                            <td class="celda-producto">
                                <strong><?= e($movimiento['producto']) ?></strong>
                                <small><span class="codigo"><?= e($movimiento['codigo']) ?></span></small>
                            </td>
                            <td class="text-end">
                                <span class="cantidad-mov <?= e($movimiento['tipo']) ?>">
                                    <?= $movimiento['tipo'] === 'entrada' ? '+' : '−' ?><?= (int) $movimiento['cantidad'] ?>
                                </span>
                            </td>
                            <td class="text-end"><strong><?= (int) $movimiento['stock_resultante'] ?></strong></td>
                            <td><?= e($movimiento['usuario']) ?></td>
                            <td class="text-muted"><?= e($movimiento['observacion'] ?? '—') ?></td>
                            <td class="text-end">
                                <a class="btn-accion" href="<?= url('productos/ver.php?id=' . (int) $movimiento['id_producto']) ?>" title="Ver producto">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="paginacion">
            <span>Mostrando <?= $paginacion['desde'] ?>–<?= $paginacion['hasta'] ?> de <?= number_format($totalRegistros, 0, ',', '.') ?> movimientos</span>
            <?php if ($paginacion['total_paginas'] > 1): ?>
                <?php
                $inicio = max(1, $paginacion['pagina'] - 2);
                $fin = min($paginacion['total_paginas'], $paginacion['pagina'] + 2);
                ?>
                <nav aria-label="Paginación de movimientos">
                    <ul class="pagination pagination-sm">
                        <li class="page-item <?= $paginacion['pagina'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= enlace_pagina('movimientos/index.php', $paginacion['pagina'] - 1) ?>">Anterior</a>
                        </li>
                        <?php for ($numero = $inicio; $numero <= $fin; $numero++): ?>
                            <li class="page-item <?= $numero === $paginacion['pagina'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= enlace_pagina('movimientos/index.php', $numero) ?>"><?= $numero ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $paginacion['pagina'] >= $paginacion['total_paginas'] ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= enlace_pagina('movimientos/index.php', $paginacion['pagina'] + 1) ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
