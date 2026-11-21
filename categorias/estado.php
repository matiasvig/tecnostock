<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('categorias/index.php');
}

validar_csrf();

$idCategoria = (int) ($_POST['id_categoria'] ?? 0);
$accion = (string) ($_POST['accion'] ?? '');

if ($idCategoria <= 0 || !in_array($accion, ['activar', 'desactivar'], true)) {
    flash('danger', 'La solicitud de cambio de estado no es válida.');
    redirigir('categorias/index.php');
}

$pdo = conexion();
$consulta = $pdo->prepare('SELECT id_categoria, nombre, estado FROM categoria WHERE id_categoria = :id');
$consulta->execute(['id' => $idCategoria]);
$categoria = $consulta->fetch();

if (!$categoria) {
    flash('danger', 'La categoría solicitada no existe.');
    redirigir('categorias/index.php');
}

$nuevoEstado = $accion === 'activar' ? 1 : 0;

if ((int) $categoria['estado'] === $nuevoEstado) {
    flash('info', 'La categoría ya se encontraba en el estado solicitado.');
    redirigir('categorias/index.php');
}

if ($nuevoEstado === 0) {
    $productos = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE id_categoria = :id AND estado = 1');
    $productos->execute(['id' => $idCategoria]);
    $activos = (int) $productos->fetchColumn();

    if ($activos > 0) {
        flash('danger', 'No es posible desactivar la categoría: mantiene ' . $activos . ' productos activos. Reasígnelos o desactívelos primero.');
        redirigir('categorias/index.php');
    }
}

$actualizar = $pdo->prepare('UPDATE categoria SET estado = :estado WHERE id_categoria = :id');
$actualizar->execute([
    'estado' => $nuevoEstado,
    'id' => $idCategoria,
]);

if ($nuevoEstado === 1) {
    flash('success', 'La categoría ' . $categoria['nombre'] . ' fue reactivada.');
} else {
    flash('success', 'La categoría ' . $categoria['nombre'] . ' fue desactivada.');
}

redirigir('categorias/index.php');
