<?php

require_once __DIR__ . '/../db.php';

function datos_estadistico(): array
{
    // X año (últimos 5)
    $por_anio = rows(
        "SELECT anio,
                COUNT(*) AS total,
                SUM(CASE WHEN tipo = 'entrada'  THEN 1 ELSE 0 END) AS entradas,
                SUM(CASE WHEN tipo = 'salida'   THEN 1 ELSE 0 END) AS salidas
         FROM correspondencia
         WHERE eliminado = 0
         GROUP BY anio
         ORDER BY anio DESC
         LIMIT 5"
    );

    // X estado (todos)
    $por_estado = rows(
        "SELECT estado, COUNT(*) AS total
         FROM correspondencia
         WHERE eliminado = 0
         GROUP BY estado
         ORDER BY total DESC"
    );

    // X tipo
    $por_tipo = rows(
        "SELECT tipo, COUNT(*) AS total
         FROM correspondencia
         WHERE eliminado = 0
         GROUP BY tipo"
    );

    // X oficina (top 8)
    $por_oficina = rows(
        "SELECT oficina, COUNT(*) AS total
         FROM correspondencia
         WHERE eliminado = 0
         GROUP BY oficina
         ORDER BY total DESC
         LIMIT 8"
    );

    // Totales derivados
    $total    = array_sum(array_column($por_anio, 'total'));
    $n_anios  = count($por_anio);
    $promedio = $n_anios > 0 ? $total / $n_anios : 0;

    $totales_anio = array_column($por_anio, 'total');
    $maximo   = $totales_anio ? max($totales_anio) : 0;
    $pct_culm = $total > 0
        ? array_sum(array_column($por_anio, 'culminados')) / $total * 100
        : 0;

    return compact(
        'por_anio', 'por_estado', 'por_tipo', 'por_oficina',
        'total', 'promedio', 'maximo', 'pct_culm'
    );
}
