<?php

require_once __DIR__ . '/../includes/init.php';

requiere_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('productos/index.php');
}

validar_csrf();

$idProducto = (int) ($_POST['id_producto'] ?? 0);
$accion = (string) ($_POST['accion'] ?? '');

if ($idProducto <= 0 || !in_array($accion, ['activar', 'desactivar'], true)) {
    flash('danger', 'La solicitud de cambio de estado no es válida.');
    redirigir('productos/index.php');
}

$pdo = conexion();
$consulta = $pdo->prepare('SELECT id_producto, codigo, nombre, estado FROM producto WHERE id_producto = :id');
$consulta->execute(['id' => $idProducto]);
$producto = $consulta->fetch();

if (!$producto) {
    flash('danger', 'El producto solicitado no existe.');
    redirigir('productos/index.php');
}

$nuevoEstado = $accion === 'activar' ? 1 : 0;

if ((int) $producto['estado'] === $nuevoEstado) {
    flash('info', 'El producto ya se encontraba en el estado solicitado.');
    redirigir('productos/index.php');
}

$actualizar = $pdo->prepare('UPDATE producto SET estado = :estado, actualizado_en = :fecha WHERE id_producto = :id');
$actualizar->execute([
    'estado' => $nuevoEstado,
    'fecha' => fecha_hora_actual(),
    'id' => $idProducto,
]);

if ($nuevoEstado === 1) {
    flash('success', 'El producto ' . $producto['codigo'] . ' fue reactivado correctamente.');
} else {
    flash('success', 'El producto ' . $producto['codigo'] . ' fue desactivado. Su historial de movimientos se mantiene intacto.');
}

redirigir('productos/index.php');
