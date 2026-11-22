<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ob_start();

require_once __DIR__ . '/pdf/base.php';
require_once __DIR__ . '/pdf/helpers.php';
require_once __DIR__ . '/data/estadistico.php';

$d = datos_estadistico();

function n0($n): string { return number_format((float)$n, 0, ',', '.'); }
function n1($n): string { return number_format((float)$n, 1, ',', '.'); }

// PDF 
$pdf = new PdfBase('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->titulo    = 'Reporte Estadístico de Correspondencia';
$pdf->subtitulo = ORG_NOMBRE;
$pdf->SetTitle($pdf->titulo);
$pdf->SetMargins(15, 36, 15);
$pdf->SetAutoPageBreak(true, 22);
$pdf->AddPage();

// KPIs
$y = 32;
fila_kpis($pdf, $y, 18, [
    ['TOTAL DOCUMENTOS',    n0($d['total'])],
    ['PROMEDIO / AÑO',      n1($d['promedio'])],
    ['MÁXIMO EN UN AÑO',    n0($d['maximo'])],
]);

// Tabla x año
seccion($pdf, 'Correspondencia por Año', 10);

$aw = [30, 32, 32, 32, 32, 27];
cabecera_tabla($pdf, $aw, ['Año', 'Total', 'Entradas', 'Salidas']);

if ($d['por_anio']) {
    foreach ($d['por_anio'] as $fila) {
        $pct = $fila['total'] > 0 ? $fila['culminados'] / $fila['total'] * 100 : 0;
        $pdf->Cell($aw[0], 7, $fila['anio'],           1, 0, 'C');
        $pdf->Cell($aw[1], 7, n0($fila['total']),      1, 0, 'C');
        $pdf->Cell($aw[2], 7, n0($fila['entradas']),   1, 0, 'C');
        $pdf->Cell($aw[3], 7, n0($fila['salidas']),    1, 0, 'C');
        $pdf->Cell($aw[4], 7, n0($fila['culminados']), 1, 0, 'C');
        $pdf->Cell($aw[5], 7, n1($pct) . '%',          1, 1, 'C');
    }

    $pdf->SetFillColor(210);
    //$pdf->SetFillColor(232, 240, 249);
    $pdf->SetFont('helvetica', 'B', 9);
    $tot_e = array_sum(array_column($d['por_anio'], 'entradas'));
    $tot_s = array_sum(array_column($d['por_anio'], 'salidas'));
    $tot_c = array_sum(array_column($d['por_anio'], 'culminados'));
    $pct_t = $d['total'] > 0 ? $tot_c / $d['total'] * 100 : 0;
    $pdf->Cell($aw[0], 7, 'TOTAL',         1, 0, 'L', true);
    $pdf->Cell($aw[1], 7, n0($d['total']), 1, 0, 'C', true);
    $pdf->Cell($aw[2], 7, n0($tot_e),      1, 0, 'C', true);
    $pdf->Cell($aw[3], 7, n0($tot_s),      1, 0, 'C', true);
    $pdf->Cell($aw[4], 7, n0($tot_c),      1, 0, 'C', true);
    $pdf->Cell($aw[5], 7, n1($pct_t) . '%',1, 1, 'C', true);
    $pdf->SetFont('helvetica', '', 9);
} else {
    $pdf->Cell(185, 7, 'Sin registros aún.', 1, 1, 'C');
}

// Gráficos 
$yg = $pdf->GetY() + 8;

// Barras: total x año (en orden cronológico)
$anios_lab = array_reverse(array_column($d['por_anio'], 'anio'));
$anios_val = array_reverse(array_column($d['por_anio'], 'total'));

if ($anios_val) {
    grafico_barras($pdf, 15, $yg, 105, 90, 'Documentos por Año', $anios_lab, $anios_val);
}

// Torta: distribución x tipo
if ($d['por_tipo']) {
    $tipo_lab = array_map(fn($r) => ucfirst($r['tipo']), $d['por_tipo']);
    $tipo_val = array_map(fn($r) => (int)$r['total'],    $d['por_tipo']);

    $paleta_torta = [[60, 60, 60], [110, 110, 110], [160, 160, 160], [200, 200, 200], [230, 230, 230], [40, 40, 40], [130, 130, 130]];
    //$paleta_torta = [[11, 61, 107], [30, 126, 52], [192, 57, 43], [23, 107, 155], [211, 84, 0], [142, 68, 173], [91, 101, 112]];

    grafico_torta($pdf, 126, $yg, 74, 90, 'Distribución por Tipo', $tipo_lab, $tipo_val, $paleta_torta);
}

// Tabla x estado
$pdf->SetY($yg + 96);
seccion($pdf, 'Distribución por Estado', 2);

$sw = [70, 70, 45];
cabecera_tabla($pdf, $sw, ['Estado', 'Cantidad', '% del Total']);

if ($d['por_estado']) {
    foreach ($d['por_estado'] as $fila) {
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
            cabecera_tabla($pdf, $sw, ['Estado', 'Cantidad', '% del Total']);
        }
        $pct = $d['total'] > 0 ? $fila['total'] / $d['total'] * 100 : 0;
        $label = match($fila['estado']) {
            'recibido'   => 'Recibido',
            'en_proceso' => 'En Proceso',
            'culminado'  => 'Culminado',
            default      => $fila['estado'],
        };
        //$pdf->SetDrawColor(0);
        $pdf->Cell($sw[0], 6.5, $label,             1, 0, 'L', false, '', 1);
        $pdf->Cell($sw[1], 6.5, n0($fila['total']), 1, 0, 'C', false, '', 1);
        $pdf->Cell($sw[2], 6.5, n1($pct) . '%',     1, 1, 'C', false, '', 1);
    }
} else {
    $pdf->Cell(185, 7, 'Sin registros aún.', 1, 1, 'C');
}

// Tabla x oficina (top 8) 
if ($pdf->GetY() > 200) $pdf->AddPage();
seccion($pdf, 'Top Oficinas por Volumen');

$ow = [110, 75];
cabecera_tabla($pdf, $ow, ['Oficina', 'Documentos']);

if ($d['por_oficina']) {
    foreach ($d['por_oficina'] as $fila) {
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
            cabecera_tabla($pdf, $ow, ['Oficina', 'Documentos']);
        }
        $oficina_nom = truncar($pdf, $fila['oficina'], $ow[0]);
        $pdf->Cell($ow[0], 6.5, $oficina_nom,       1, 0, 'L', false, '', 1);
        $pdf->Cell($ow[1], 6.5, n0($fila['total']), 1, 1, 'C', false, '', 1);
    }
} else {
    $pdf->Cell(185, 7, 'Sin registros aún.', 1, 1, 'C');
}

// Conclusiones 
if ($pdf->GetY() > 210) $pdf->AddPage();
if ($d['total'] > 0) {
    $mayor_anio = $d['por_anio'][0];
    $html = '<b>Conclusiones</b><br>'
        . 'El año con mayor actividad registrada es <b>' . $mayor_anio['anio'] . '</b> con '
        . n0($mayor_anio['total']) . ' documentos. '
        . 'El promedio anual es de <b>' . n1($d['promedio']) . '</b> documentos ';
} else {
    $html = '<b>Conclusiones</b><br>No hay documentos registrados en el sistema aún.';
}

$pdf->Ln(6);
$pdf->writeHTMLCell(185, 0, 15, $pdf->GetY(), $html, 1, 1, false, true, 'L', true);

ob_end_clean();
$pdf->Output('reporte_estadistico.pdf', 'I');
