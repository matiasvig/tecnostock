<?php

function conexion(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PUERTO,
        DB_NOMBRE,
        DB_CHARSET
    );

    $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USUARIO, DB_CLAVE, $opciones);
    } catch (PDOException $e) {
        error_log('Error de conexión a MySQL: ' . $e->getMessage());
        http_response_code(500);
        exit('No fue posible conectar con la base de datos. Verifique que MySQL esté en ejecución y los datos de config/config.php.');
    }

    return $pdo;
}
