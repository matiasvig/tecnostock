<?php

function detectar_base_url(): string
{
    $raiz = str_replace('\\', '/', dirname(__DIR__));
    $documento = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

    if ($documento !== '' && str_starts_with($raiz, $documento)) {
        $base = substr($raiz, strlen($documento));
    } else {
        $base = '';
    }

    return rtrim($base, '/');
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $ruta = ''): string
{
    $ruta = ltrim($ruta, '/');

    return BASE_URL . ($ruta === '' ? '/' : '/' . $ruta);
}

function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flashes'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function obtener_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $flashes;
}

function formatear_precio(mixed $valor): string
{
    return '$ ' . number_format((float) $valor, 0, ',', '.');
}

function formatear_fecha(?string $fecha, bool $conHora = true): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }

    $objeto = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);

    if ($objeto === false) {
        return '—';
    }

    return $objeto->format($conHora ? 'd-m-Y H:i' : 'd-m-Y');
}

function fecha_hora_actual(): string
{
    return date('Y-m-d H:i:s');
}

function estado_stock(int $actual, int $minimo): array
{
    if ($actual <= 0) {
        return ['clase' => 'agotado', 'texto' => 'Sin stock', 'icono' => 'bi-x-octagon'];
    }

    if ($actual < $minimo) {
        return ['clase' => 'bajo', 'texto' => 'Stock bajo', 'icono' => 'bi-exclamation-triangle'];
    }

    return ['clase' => 'ok', 'texto' => 'Disponible', 'icono' => 'bi-check-circle'];
}

function iniciales(string $nombre): string
{
    $partes = preg_split('/\s+/u', trim($nombre)) ?: [];
    $iniciales = '';

    foreach (array_slice($partes, 0, 2) as $parte) {
        $iniciales .= mb_substr($parte, 0, 1);
    }

    return mb_strtoupper($iniciales !== '' ? $iniciales : 'U');
}

function query_actual(array $cambios = []): string
{
    $parametros = array_merge($_GET, $cambios);

    foreach ($parametros as $clave => $valor) {
        if ($valor === null || $valor === '' || $valor === false) {
            unset($parametros[$clave]);
        }
    }

    return http_build_query($parametros);
}

function enlace_pagina(string $ruta, int $pagina): string
{
    $query = query_actual(['pagina' => $pagina]);

    return url($ruta) . ($query !== '' ? '?' . $query : '');
}

function datos_paginacion(int $total, int $pagina, int $porPagina): array
{
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, $pagina), $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    return [
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $porPagina,
        'total_paginas' => $totalPaginas,
        'offset' => $offset,
        'desde' => $total === 0 ? 0 : $offset + 1,
        'hasta' => min($offset + $porPagina, $total),
    ];
}

function fecha_iso_valida(string $fecha): bool
{
    $objeto = DateTime::createFromFormat('Y-m-d', $fecha);

    return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
}

function patron_busqueda(string $texto): string
{
    $escapado = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);

    return '%' . $escapado . '%';
}
