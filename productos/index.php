<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();

$buscar = trim((string) ($_GET['q'] ?? ''));
$idCategoria = (int) ($_GET['categoria'] ?? 0);
$estadoFiltro = (string) ($_GET['estado'] ?? 'activos');
$soloBajo = ($_GET['bajo_stock'] ?? '') === '1';
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

if (!in_array($estadoFiltro, ['activos', 'inactivos', 'todos'], true)) {
    $estadoFiltro = 'activos';
}

$condiciones = [];
$parametros = [];

if ($buscar !== '') {
    $condiciones[] = '(p.nombre LIKE :busqueda_nombre OR p.codigo LIKE :busqueda_codigo OR c.nombre LIKE :busqueda_categoria)';
    $parametros['busqueda_nombre'] = patron_busqueda($buscar);
    $parametros['busqueda_codigo'] = patron_busqueda($buscar);
    $parametros['busqueda_categoria'] = patron_busqueda($buscar);
}

if ($idCategoria > 0) {
    $condiciones[] = 'p.id_categoria = :categoria';
    $parametros['categoria'] = $idCategoria;
}

if ($estadoFiltro === 'activos') {
    $condiciones[] = 'p.estado = 1';
} elseif ($estadoFiltro === 'inactivos') {
    $condiciones[] = 'p.estado = 0';
}

if ($soloBajo) {
    $condiciones[] = 'p.stock_actual < p.stock_minimo';
}

$where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

$conteo = $pdo->prepare(
    'SELECT COUNT(*) FROM producto p INNER JOIN categoria c ON c.id_categoria = p.id_categoria' . $where
);
$conteo->execute($parametros);
$totalRegistros = (int) $conteo->fetchColumn();
$paginacion = datos_paginacion($totalRegistros, $pagina, PRODUCTOS_POR_PAGINA);

$consulta = $pdo->prepare(
    'SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio, p.stock_actual, p.stock_minimo, p.estado, c.nombre AS categoria
     FROM producto p
     INNER JOIN categoria c ON c.id_categoria = p.id_categoria' . $where . '
     ORDER BY (p.estado = 0) ASC, (p.stock_actual < p.stock_minimo) DESC, p.nombre ASC
     LIMIT :limite OFFSET :offset'
);

foreach ($parametros as $clave => $valor) {
    $consulta->bindValue(':' . $clave, $valor);
}
$consulta->bindValue(':limite', $paginacion['por_pagina'], PDO::PARAM_INT);
$consulta->bindValue(':offset', $paginacion['offset'], PDO::PARAM_INT);
$consulta->execute();
$productos = $consulta->fetchAll();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categoria ORDER BY nombre ASC')->fetchAll();
$totalActivos = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1')->fetchColumn();
$totalBajo = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo')->fetchColumn();

$titulo = 'Productos';
$activo = 'productos';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Productos</h1>
        <p class="pagina-subtitulo">
            <?= number_format($totalActivos, 0, ',', '.') ?> productos activos ·
            <?= number_format($totalBajo, 0, ',', '.') ?> con stock bajo
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= url('movimientos/registrar.php') ?>">
            <i class="bi bi-arrow-left-right me-1"></i> Movimiento
        </a>
        <a class="btn btn-primary" href="<?= url('productos/crear.php') ?>">
            <i class="bi bi-plus-lg me-1"></i> Nuevo producto
        </a>
    </div>
</div>

<div class="tarjeta">
    <form class="filtros" method="get" action="<?= url('productos/index.php') ?>">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label" for="q">Buscar</label>
                <div class="buscador">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="q" name="q" value="<?= e($buscar) ?>" placeholder="Nombre, código o categoría" maxlength="100">
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label" for="categoria">Categoría</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= (int) $categoria['id_categoria'] ?>" <?= $idCategoria === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="activos" <?= $estadoFiltro === 'activos' ? 'selected' : '' ?>>Activos</option>
                    <option value="inactivos" <?= $estadoFiltro === 'inactivos' ? 'selected' : '' ?>>Desactivados</option>
                    <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-8">
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check form-switch mb-0 me-auto">
                        <input class="form-check-input" type="checkbox" role="switch" id="bajo_stock" name="bajo_stock" value="1" <?= $soloBajo ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="bajo_stock">Solo stock bajo</label>
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                    <a class="btn btn-light btn-sm" href="<?= url('productos/index.php') ?>">Limpiar</a>
                </div>
            </div>
        </div>
    </form>

    <?php if ($productos === []): ?>
        <div class="vacio">
            <i class="bi bi-inbox"></i>
            <strong>No se encontraron productos</strong>
            <span>Pruebe con otros filtros o registre un producto nuevo.</span>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla tabla-compacta">
                <thead>
                    <tr>
                        <th class="d-none d-xl-table-cell">Código</th>
                        <th class="col-producto">Producto</th>
                        <th class="d-none d-lg-table-cell">Categoría</th>
                        <th class="d-none d-lg-table-cell text-end">Precio</th>
                        <th class="col-stock">Stock</th>
                        <th class="d-none d-md-table-cell col-minimo">Mínimo</th>
                        <th class="d-none d-sm-table-cell col-estado">Estado</th>
                        <th class="text-end col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <?php
                        $estadoStock = estado_stock((int) $producto['stock_actual'], (int) $producto['stock_minimo']);
                        $estaActivo = (int) $producto['estado'] === 1;
                        ?>
                        <tr>
                            <td class="d-none d-xl-table-cell"><span class="codigo"><?= e($producto['codigo']) ?></span></td>
                            <td class="celda-producto">
                                <strong title="<?= e($producto['nombre']) ?>"><?= e($producto['nombre']) ?></strong>
                                <small title="<?= e($producto['descripcion'] ?? '') ?>"><?= e($producto['descripcion'] ?? 'Sin descripción registrada') ?></small>
                            </td>
                            <td class="d-none d-lg-table-cell"><?= e($producto['categoria']) ?></td>
                            <td class="d-none d-lg-table-cell text-end"><?= formatear_precio($producto['precio']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="stock-numero <?= e($estadoStock['clase']) ?>"><?= (int) $producto['stock_actual'] ?></span>
                                    <span class="badge-stock <?= e($estadoStock['clase']) ?> d-none d-md-inline-flex">
                                        <?= e($estadoStock['texto']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell"><span class="stock-minimo"><?= (int) $producto['stock_minimo'] ?></span></td>
                            <td class="d-none d-sm-table-cell">
                                <span class="badge-estado <?= $estaActivo ? 'activo' : 'inactivo' ?>">
                                    <?= $estaActivo ? 'Activo' : 'Desactivado' ?>
                                </span>
                            </td>
                            <td>
                                <div class="acciones-tabla">
                                    <a class="btn-accion" href="<?= url('productos/ver.php?id=' . (int) $producto['id_producto']) ?>" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a class="btn-accion" href="<?= url('productos/editar.php?id=' . (int) $producto['id_producto']) ?>" title="Editar producto">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($estaActivo): ?>
                                        <form method="post" action="<?= url('productos/estado.php') ?>" data-confirmar="El producto «<?= e($producto['nombre']) ?>» dejará de aparecer para nuevos movimientos, pero conservará todo su historial." data-confirmar-titulo="Desactivar producto" data-confirmar-boton="Desactivar">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
                                            <input type="hidden" name="accion" value="desactivar">
                                            <button class="btn-accion peligro" type="submit" title="Desactivar producto">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('productos/estado.php') ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
                                            <input type="hidden" name="accion" value="activar">
                                            <button class="btn-accion exito" type="submit" title="Reactivar producto">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="paginacion">
            <span>Mostrando <?= $paginacion['desde'] ?>–<?= $paginacion['hasta'] ?> de <?= number_format($totalRegistros, 0, ',', '.') ?> productos</span>
            <?php if ($paginacion['total_paginas'] > 1): ?>
                <?php
                $inicio = max(1, $paginacion['pagina'] - 2);
                $fin = min($paginacion['total_paginas'], $paginacion['pagina'] + 2);
                ?>
                <nav aria-label="Paginación de productos">
                    <ul class="pagination pagination-sm">
                        <li class="page-item <?= $paginacion['pagina'] <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= enlace_pagina('productos/index.php', $paginacion['pagina'] - 1) ?>">Anterior</a>
                        </li>
                        <?php for ($numero = $inicio; $numero <= $fin; $numero++): ?>
                            <li class="page-item <?= $numero === $paginacion['pagina'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= enlace_pagina('productos/index.php', $numero) ?>"><?= $numero ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $paginacion['pagina'] >= $paginacion['total_paginas'] ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= enlace_pagina('productos/index.php', $paginacion['pagina'] + 1) ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
