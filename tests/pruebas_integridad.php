<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = conexion();
$total = 0;
$fallas = 0;

function resultado(string $descripcion, bool $correcto, string $detalle = ''): void
{
    global $total, $fallas;

    $total++;

    if (!$correcto) {
        $fallas++;
    }

    echo ($correcto ? '[OK]    ' : '[FALLA] ') . $descripcion . ($detalle !== '' ? ' -> ' . $detalle : '') . PHP_EOL;
}

function esperarError(callable $accion, string $descripcion): void
{
    try {
        $accion();
        resultado($descripcion, false, 'la operación fue aceptada');
    } catch (PDOException $e) {
        resultado($descripcion, $e->getCode() === '23000', 'SQLSTATE ' . $e->getCode());
    }
}

echo 'PRUEBAS DE INTEGRIDAD - TECNOSTOCK' . PHP_EOL;
echo str_repeat('=', 62) . PHP_EOL;

$pdo->beginTransaction();

$admin = $pdo->query("SELECT clave_hash FROM usuario WHERE correo = 'admin@tecnostock.cl'")->fetchColumn();
resultado('La clave del usuario administrador es válida', password_verify('Admin123*', (string) $admin));
resultado('Una clave incorrecta es rechazada', !password_verify('ClaveEquivocada1', (string) $admin));

$descuadres = $pdo->query(
    "SELECT COUNT(*) FROM (
        SELECT p.id_producto, p.stock_actual,
               COALESCE(SUM(CASE WHEN m.tipo = 'entrada' THEN m.cantidad ELSE -m.cantidad END), 0) AS calculado
        FROM producto p
        LEFT JOIN movimiento m ON m.id_producto = p.id_producto
        GROUP BY p.id_producto, p.stock_actual
        HAVING p.stock_actual <> calculado
    ) AS diferencias"
)->fetchColumn();
resultado('El stock coincide con la suma de sus movimientos', (int) $descuadres === 0, 'diferencias: ' . (int) $descuadres);

$ultimos = $pdo->query(
    'SELECT COUNT(*) FROM producto p
     INNER JOIN movimiento m ON m.id_movimiento = (
         SELECT MAX(m2.id_movimiento) FROM movimiento m2 WHERE m2.id_producto = p.id_producto
     )
     WHERE m.stock_resultante <> p.stock_actual'
)->fetchColumn();
resultado('El último stock resultante coincide con el stock actual', (int) $ultimos === 0, 'diferencias: ' . (int) $ultimos);

esperarError(function () use ($pdo) {
    $stmt = $pdo->prepare(
        "INSERT INTO producto (codigo, nombre, precio, stock_actual, stock_minimo, estado, id_categoria, creado_en, actualizado_en)
         VALUES ('TEC-001', 'Producto duplicado', 1000, 0, 0, 1, 1, NOW(), NOW())"
    );
    $stmt->execute();
}, 'El código de producto duplicado se rechaza');

esperarError(function () use ($pdo) {
    $stmt = $pdo->prepare(
        "INSERT INTO producto (codigo, nombre, precio, stock_actual, stock_minimo, estado, id_categoria, creado_en, actualizado_en)
         VALUES ('TEST-PRECIO', 'Precio negativo', -1000, 0, 0, 1, 1, NOW(), NOW())"
    );
    $stmt->execute();
}, 'Un precio negativo se rechaza');

esperarError(function () use ($pdo) {
    $stmt = $pdo->prepare(
        "INSERT INTO producto (codigo, nombre, precio, stock_actual, stock_minimo, estado, id_categoria, creado_en, actualizado_en)
         VALUES ('TEST-STOCK', 'Stock negativo', 1000, -1, 0, 1, 1, NOW(), NOW())"
    );
    $stmt->execute();
}, 'Un stock negativo se rechaza');

esperarError(function () use ($pdo) {
    $stmt = $pdo->prepare(
        "INSERT INTO producto (codigo, nombre, precio, stock_actual, stock_minimo, estado, id_categoria, creado_en, actualizado_en)
         VALUES ('TEST-CAT', 'Categoría inexistente', 1000, 0, 0, 1, 9999, NOW(), NOW())"
    );
    $stmt->execute();
}, 'Una categoría inexistente es rechazada por la clave foránea');

esperarError(function () use ($pdo) {
    $stmt = $pdo->prepare("INSERT INTO categoria (nombre, descripcion, estado, creado_en) VALUES ('Audífonos', 'Duplicada', 1, NOW())");
    $stmt->execute();
}, 'El nombre de categoría duplicado se rechaza');

$insertar = $pdo->prepare(
    "INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria, creado_en, actualizado_en)
     VALUES ('TEST-FLOW', 'Producto de prueba', 'Producto temporal para pruebas', 1500, 10, 5, 1, 1, NOW(), NOW())"
);
$insertar->execute();
$idPrueba = (int) $pdo->lastInsertId();
resultado('Se crea un producto temporal para las pruebas', $idPrueba > 0);

esperarError(function () use ($pdo, $idPrueba) {
    $stmt = $pdo->prepare(
        "INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, stock_resultante, id_producto, id_usuario)
         VALUES (NOW(), 'entrada', 0, 'Cantidad inválida', 10, :producto, 1)"
    );
    $stmt->execute(['producto' => $idPrueba]);
}, 'Un movimiento con cantidad cero se rechaza');

esperarError(function () use ($pdo, $idPrueba) {
    $stmt = $pdo->prepare(
        "INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, stock_resultante, id_producto, id_usuario)
         VALUES (NOW(), 'salida', 1, 'Usuario inexistente', 9, :producto, 9999)"
    );
    $stmt->execute(['producto' => $idPrueba]);
}, 'Un movimiento sin usuario válido se rechaza');

esperarError(function () use ($pdo, $idPrueba) {
    $stmt = $pdo->prepare('UPDATE producto SET stock_actual = -1 WHERE id_producto = :id');
    $stmt->execute(['id' => $idPrueba]);
}, 'Una actualización que deja stock negativo se rechaza');

$entrada = $pdo->prepare(
    "UPDATE producto SET stock_actual = stock_actual + 5 WHERE id_producto = :id AND stock_actual = 10"
);
$entrada->execute(['id' => $idPrueba]);
$movEntrada = $pdo->prepare(
    "INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, stock_resultante, id_producto, id_usuario)
     VALUES (NOW(), 'entrada', 5, 'Prueba de entrada', 15, :producto, 1)"
);
$movEntrada->execute(['producto' => $idPrueba]);
resultado('Una entrada actualiza el stock de forma atómica', $entrada->rowCount() === 1);

$salida = $pdo->prepare(
    "UPDATE producto SET stock_actual = stock_actual - 3 WHERE id_producto = :id AND stock_actual = 15"
);
$salida->execute(['id' => $idPrueba]);
$movSalida = $pdo->prepare(
    "INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, stock_resultante, id_producto, id_usuario)
     VALUES (NOW(), 'salida', 3, 'Prueba de salida', 12, :producto, 1)"
);
$movSalida->execute(['producto' => $idPrueba]);

$stockFinal = $pdo->prepare('SELECT stock_actual FROM producto WHERE id_producto = :id');
$stockFinal->execute(['id' => $idPrueba]);
resultado('El stock final es 12 tras una entrada de 5 y una salida de 3', (int) $stockFinal->fetchColumn() === 12);

$movimientos = $pdo->prepare('SELECT COUNT(*) FROM movimiento WHERE id_producto = :id');
$movimientos->execute(['id' => $idPrueba]);
resultado('Los dos movimientos quedan registrados', (int) $movimientos->fetchColumn() === 2);

$busqueda = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE nombre LIKE :texto');
$busqueda->execute(['texto' => "%' OR 1=1 --%"]);
resultado('Una cadena con intento de inyección no altera la consulta', (int) $busqueda->fetchColumn() === 0);

$pdo->rollBack();

$residuos = $pdo->query("SELECT COUNT(*) FROM producto WHERE codigo LIKE 'TEST-%'")->fetchColumn();
resultado('La transacción de pruebas no deja datos residuales', (int) $residuos === 0);

echo str_repeat('=', 62) . PHP_EOL;
echo 'Pruebas ejecutadas: ' . $total . ' | Fallas: ' . $fallas . PHP_EOL;

exit($fallas === 0 ? 0 : 1);


