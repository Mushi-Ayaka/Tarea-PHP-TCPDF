<?php

require_once __DIR__ . '/../db.php';

function datos_general(): array
{
    // Totales globales
    $total          = (int)scalar("SELECT COUNT(*) FROM correspondencia WHERE eliminado = 0");
    $entradas       = (int)scalar("SELECT COUNT(*) FROM correspondencia WHERE eliminado = 0 AND tipo = 'entrada'");
    $salidas        = (int)scalar("SELECT COUNT(*) FROM correspondencia WHERE eliminado = 0 AND tipo = 'salida'");
    $total_entregas = (int)scalar("SELECT COUNT(*) FROM entregas");

    // X estado
    $recibidos  = (int)scalar("SELECT COUNT(*) FROM correspondencia WHERE eliminado = 0 AND estado = 'recibido'");
    $en_proceso = (int)scalar("SELECT COUNT(*) FROM correspondencia WHERE eliminado = 0 AND estado = 'en_proceso'");

    // X oficina
    $por_oficina = rows(
        "SELECT oficina,
                COUNT(*) AS total,
                SUM(CASE WHEN tipo = 'entrada' THEN 1 ELSE 0 END) AS entradas,
                SUM(CASE WHEN tipo = 'salida'  THEN 1 ELSE 0 END) AS salidas
         FROM correspondencia
         WHERE eliminado = 0
         GROUP BY oficina
         ORDER BY total DESC"
    );

    // Últimas 15
    $recientes = rows(
        "SELECT folio, sufijo, nomenclatura, origen, tipo, estado, de_nombre, para_nombre, asunto, fecha_recepcion
         FROM correspondencia
         WHERE eliminado = 0
         ORDER BY fecha_recepcion DESC
         LIMIT 15"
    );

    return compact(
        'total', 'entradas', 'salidas', 'total_entregas',
        'recibidos', 'en_proceso',
        'por_oficina', 'recientes'
    );
}
