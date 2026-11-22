<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ob_start();

require_once __DIR__ . '/pdf/base.php';
require_once __DIR__ . '/pdf/helpers.php';
require_once __DIR__ . '/data/general.php';

$d = datos_general();

function fmt_fecha(string $f): string {
    return $f ? date('d/m/Y', strtotime($f)) : '—';
}
function label_estado(string $e): string {
    return match($e) { 'recibido' => 'Recibido', 'en_proceso' => 'En proceso', 'culminado' => 'Culminado', default => $e };
}
function label_tipo(string $t): string {
    return match($t) { 'entrada' => 'Entrada', 'salida' => 'Salida', default => $t };
}

// PDF 
$pdf = new PdfBase('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->titulo    = 'Reporte General de Correspondencia';
$pdf->subtitulo = ORG_NOMBRE;
$pdf->SetTitle($pdf->titulo);
$pdf->SetMargins(15, 36, 15);
$pdf->SetAutoPageBreak(true, 22);
$pdf->AddPage();

// KPIs fila 1: volumen
$y = 36;
fila_kpis($pdf, $y, 17, [
    ['TOTAL DOCUMENTOS', $d['total']],
    ['ENTRADAS',         $d['entradas']],
    ['SALIDAS',          $d['salidas']],
    ['ENTREGAS',         $d['total_entregas']],
]);

// KPIs fila 2: estados
fila_kpis($pdf, $y + 20, 17, [
    ['RECIBIDOS',  $d['recibidos']],
    ['EN PROCESO', $d['en_proceso']],
]);

// Tabla x oficina 
seccion($pdf, 'Distribución por Oficina', 10);

$aw = [80, 35, 35, 35];
cabecera_tabla($pdf, $aw, ['Oficina', 'Total', 'Entradas', 'Salidas']);

if ($d['por_oficina']) {
    $pdf->SetFont('helvetica', '', 8.5);
    foreach ($d['por_oficina'] as $fila) {
        if ($pdf->GetY() > 235) {
            $pdf->AddPage();
            cabecera_tabla($pdf, $aw, ['Oficina', 'Total', 'Entradas', 'Salidas']);
            $pdf->SetFont('helvetica', '', 8.5);
        }
        $oficina_nom = truncar($pdf, $fila['oficina'], $aw[0]);
        $pdf->Cell($aw[0], 6.5, $oficina_nom,              1, 0, 'L', false, '', 1);
        $pdf->Cell($aw[1], 6.5, (string)$fila['total'],    1, 0, 'C', false, '', 1);
        $pdf->Cell($aw[2], 6.5, (string)$fila['entradas'], 1, 0, 'C', false, '', 1);
        $pdf->Cell($aw[3], 6.5, (string)$fila['salidas'],  1, 1, 'C', false, '', 1);
    }
} else {
    $pdf->Cell(185, 7, 'Sin registros aún.', 1, 1, 'C');
}

// Últimas correspondencias 
seccion($pdf, 'Últimas Correspondencias Registradas');

$rw = [32, 16, 22, 18, 57, 40];
cabecera_tabla($pdf, $rw, ['Correlativo', 'Tipo', 'Estado', 'Fecha', 'Asunto', 'Remitente']);

if ($d['recientes']) {
    $pdf->SetFont('helvetica', '', 8);
    foreach ($d['recientes'] as $r) {
        if ($pdf->GetY() > 235) {
            $pdf->AddPage();
            cabecera_tabla($pdf, $rw, ['Correlativo', 'Tipo', 'Estado', 'Fecha', 'Asunto', 'Remitente']);
            $pdf->SetFont('helvetica', '', 8);
        }

        // Prioridad: nomenclatura manual si es externo, sufijo si existe, o folio
        $doc = !empty($r['nomenclatura'])
            ? $r['nomenclatura']
            : (!empty($r['sufijo']) ? $r['sufijo'] : $r['folio']);

        $txt_doc    = truncar($pdf, $doc, $rw[0]);
        $txt_asunto = truncar($pdf, $r['asunto'], $rw[4]);
        $txt_de     = truncar($pdf, $r['de_nombre'], $rw[5]);

        $pdf->Cell($rw[0], 6.5, $txt_doc,                        1, 0, 'L', false, '', 1);
        $pdf->Cell($rw[1], 6.5, label_tipo($r['tipo']),          1, 0, 'C', false, '', 1);
        $pdf->Cell($rw[2], 6.5, label_estado($r['estado']),      1, 0, 'C', false, '', 1);
        $pdf->Cell($rw[3], 6.5, fmt_fecha($r['fecha_recepcion']),1, 0, 'C', false, '', 1);
        $pdf->Cell($rw[4], 6.5, $txt_asunto,                     1, 0, 'L', false, '', 1);
        $pdf->Cell($rw[5], 6.5, $txt_de,                         1, 1, 'L', false, '', 1);
    }
} else {
    $pdf->Cell(185, 7, 'Sin registros aún.', 1, 1, 'C');
}

ob_end_clean();
$pdf->Output('reporte_general.pdf', 'I');
