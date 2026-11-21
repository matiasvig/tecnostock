<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

$pdo = conexion();

$categorias = $pdo->query(
    'SELECT c.id_categoria, c.nombre, c.descripcion, c.estado, c.creado_en,
            COUNT(p.id_producto) AS total_productos,
            COALESCE(SUM(CASE WHEN p.estado = 1 THEN 1 ELSE 0 END), 0) AS productos_activos
     FROM categoria c
     LEFT JOIN producto p ON p.id_categoria = c.id_categoria
     GROUP BY c.id_categoria, c.nombre, c.descripcion, c.estado, c.creado_en
     ORDER BY c.estado DESC, c.nombre ASC'
)->fetchAll();

$titulo = 'Categorías';
$activo = 'categorias';

require __DIR__ . '/../includes/header.php';
?>

<div class="pagina-cabecera">
    <div>
        <h1>Categorías</h1>
        <p class="pagina-subtitulo">Organice el catálogo y controle qué líneas están disponibles.</p>
    </div>
    <a class="btn btn-primary" href="<?= url('categorias/crear.php') ?>">
        <i class="bi bi-plus-lg me-1"></i> Nueva categoría
    </a>
</div>

<div class="tarjeta">
    <?php if ($categorias === []): ?>
        <div class="vacio">
            <i class="bi bi-tags"></i>
            <strong>Aún no hay categorías</strong>
            <span>Cree una categoría para poder registrar productos.</span>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table tabla">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Productos activos</th>
                        <th>Total productos</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <?php
                        $estaActiva = (int) $categoria['estado'] === 1;
                        $activos = (int) $categoria['productos_activos'];
                        ?>
                        <tr>
                            <td><strong><?= e($categoria['nombre']) ?></strong></td>
                            <td class="text-muted"><?= e($categoria['descripcion'] ?? '—') ?></td>
                            <td><span class="stock-numero"><?= $activos ?></span></td>
                            <td><span class="stock-minimo"><?= (int) $categoria['total_productos'] ?></span></td>
                            <td>
                                <span class="badge-estado <?= $estaActiva ? 'activo' : 'inactivo' ?>">
                                    <?= $estaActiva ? 'Activa' : 'Desactivada' ?>
                                </span>
                            </td>
                            <td>
                                <div class="acciones-tabla">
                                    <a class="btn-accion" href="<?= url('categorias/editar.php?id=' . (int) $categoria['id_categoria']) ?>" title="Editar categoría">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($estaActiva): ?>
                                        <form method="post" action="<?= url('categorias/estado.php') ?>" data-confirmar="La categoría dejará de estar disponible para nuevos productos. Solo es posible desactivarla si no tiene productos activos." data-confirmar-titulo="Desactivar categoría" data-confirmar-boton="Desactivar">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="id_categoria" value="<?= (int) $categoria['id_categoria'] ?>">
                                            <input type="hidden" name="accion" value="desactivar">
                                            <button class="btn-accion peligro" type="submit" title="<?= $activos > 0 ? 'No se puede desactivar: tiene productos activos' : 'Desactivar categoría' ?>" <?= $activos > 0 ? 'disabled' : '' ?>>
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('categorias/estado.php') ?>">
                                            <?= campo_csrf() ?>
                                            <input type="hidden" name="id_categoria" value="<?= (int) $categoria['id_categoria'] ?>">
                                            <input type="hidden" name="accion" value="activar">
                                            <button class="btn-accion exito" type="submit" title="Reactivar categoría">
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
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
