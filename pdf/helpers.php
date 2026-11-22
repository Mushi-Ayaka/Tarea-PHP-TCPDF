<?php

/** Caja KPI: etiqueta pequeña arriba, valor grande abajo. */
function kpi(TCPDF $pdf, float $x, float $y, float $w, float $h, string $etiqueta, string $valor): void
{
    $pdf->SetDrawColor(0);
    $pdf->Rect($x, $y, $w, $h);
    $pdf->SetTextColor(90);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY($x + 3, $y + 2);
    $pdf->Cell($w - 4, 5, $etiqueta, 0, 0, 'L');
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetXY($x + 3, $y + 8);
    $pdf->Cell($w - 4, 10, $valor, 0, 0, 'L');
}

/** Fila de N cajas KPI distribuidas uniformemente a lo ancho. */
function fila_kpis(TCPDF $pdf, float $y, float $h, array $items, float $margen = 15, float $ancho = 185): void
{
    $n    = count($items);
    $gap  = 3;
    $w    = ($ancho - ($n - 1) * $gap) / $n;
    foreach ($items as $i => [$etiqueta, $valor]) {
        kpi($pdf, $margen + $i * ($w + $gap), $y, $w, $h, $etiqueta, (string)$valor);
    }
}

/** Encabezado negro para tablas. Deja el cursor listo para las filas. */
function cabecera_tabla(TCPDF $pdf, array $anchos, array $titulos, int $alto = 8): void
{
    // Fondo de cabecera (escala de grises)
    $pdf->SetFillColor(0);
    // Variante con color representativo de registro-memorandos (Azul institucional #0b3d6b):
    // $pdf->SetFillColor(11, 61, 107);
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 9);
    foreach ($titulos as $i => $t) {
        $pdf->Cell($anchos[$i], $alto, $t, 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 9);
}

/** Título de sección con separador. */
function seccion(TCPDF $pdf, string $texto, float $espacio_arriba = 6): void
{
    $pdf->Ln($espacio_arriba);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(0);
    $pdf->Cell(185, 7, $texto, 0, 1, 'L');
}

//Trunca un texto con elipsis '…' según el ancho real en milímetros.

function truncar(TCPDF $pdf, string $txt, float $ancho_mm, float $pad = 2.0): string
{
    $limite = $ancho_mm - $pad;
    if ($limite <= 0) return '';
    if ($pdf->GetStringWidth($txt) <= $limite) {
        return $txt;
    }
    while ($txt !== '' && $pdf->GetStringWidth($txt . '…') > $limite) {
        $txt = mb_substr($txt, 0, -1, 'UTF-8');
    }
    return $txt . '…';
}

/** Gráfico de barras verticales. */
function grafico_barras(
    TCPDF $pdf, float $x, float $y, float $w, float $h,
    string $titulo, array $etiquetas, array $valores
): void {
    $pdf->SetDrawColor(150);
    $pdf->Rect($x, $y, $w, $h);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($w, 6, $titulo, 0, 0, 'C');

    $areaX = $x + 12;
    $areaY = $y + 14;
    $areaW = $w - 16;
    $areaH = $h - 26;
    $base  = $areaY + $areaH;
    $tope  = max(1, (int)ceil(max($valores) / 20) * 20);

    $pdf->SetFont('helvetica', '', 7);
    for ($i = 0; $i <= 5; $i++) {
        $v  = $tope * $i / 5;
        $yy = $base - ($v / $tope) * $areaH;
        $pdf->SetDrawColor(220);
        $pdf->Line($areaX, $yy, $areaX + $areaW, $yy);
        $pdf->SetTextColor(100);
        $pdf->SetXY($x, $yy - 2);
        $pdf->Cell(11, 4, (string)round($v), 0, 0, 'R');
    }

    $n      = count($valores);
    $ranura = $areaW / max(1, $n);
    $barraW = $ranura * 0.6;
    foreach ($valores as $i => $v) {
        $alto = ($v / $tope) * $areaH;
        $bx   = $areaX + $i * $ranura + ($ranura - $barraW) / 2;
        $by   = $base - $alto;
        // Color de barras (escala de grises)
        $pdf->SetFillColor(80);
        $pdf->SetDrawColor(80);
        // Variante con color representativo de registro-memorandos (Azul institucional #0b3d6b):
        // $pdf->SetFillColor(11, 61, 107);
        // $pdf->SetDrawColor(11, 61, 107);
        $pdf->Rect($bx, $by, $barraW, $alto, 'F');
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($bx - 5, $by - 5);
        $pdf->Cell($barraW + 10, 4, (string)$v, 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetXY($areaX + $i * $ranura, $base + 1.5);
        $pdf->Cell($ranura, 4, $etiquetas[$i], 0, 0, 'C');
    }

    $pdf->SetDrawColor(0);
    $pdf->SetLineWidth(0.4);
    $pdf->Line($areaX, $areaY, $areaX, $base);
    $pdf->Line($areaX, $base,  $areaX + $areaW, $base);
}

/** Gráfico de torta con leyenda (escala de grises con opción de paleta). */
function grafico_torta(
    TCPDF $pdf, float $x, float $y, float $w, float $h,
    string $titulo, array $etiquetas, array $valores,
    ?array $paleta = null
): void {
    // Paleta en escala de grises
    $grises = $paleta ?? [[60,60,60],[110,110,110],[160,160,160],[200,200,200],[230,230,230],[40,40,40],[130,130,130]];
    // Variante de colores vivos representativos de registro-memorandos (Azul institucional, Verde, Rojo, Azul medio, Naranja, Púrpura, Gris azulado):
    // $grises = $paleta ?? [[11, 61, 107], [30, 126, 52], [192, 57, 43], [23, 107, 155], [211, 84, 0], [142, 68, 173], [91, 101, 112]];

    $pdf->SetDrawColor(150);
    $pdf->Rect($x, $y, $w, $h);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($w, 6, $titulo, 0, 0, 'C');

    $total = array_sum($valores);
    if ($total <= 0) return;

    $cx  = $x + $w / 2;
    $cy  = $y + 36;
    $r   = 22;
    $ang = 0;

    $pdf->SetDrawColor(255);
    $pdf->SetLineWidth(0.6);
    foreach ($valores as $i => $v) {
        $grados = $v / $total * 360;
        $pdf->SetFillColor(...$grises[$i % count($grises)]);
        $pdf->PieSector($cx, $cy, $r, $ang, $ang + $grados, 'FD');
        $ang += $grados;
    }

    $n          = count($valores);
    $cols       = $n > 5 ? 2 : 1;
    $filas_col  = (int)ceil($n / $cols);
    $ancho_col  = ($w - 10) / $cols;
    $ly_inicio  = $y + 62;
    $alto_fila  = min(5, ($h - 62) / max(1, $filas_col));

    $pdf->SetFont('helvetica', '', 7);
    foreach ($valores as $i => $v) {
        $col  = intdiv($i, $filas_col);
        $fila = $i % $filas_col;
        $lx   = $x + 4 + $col * ($ancho_col + 8);
        $ly   = $ly_inicio + $fila * $alto_fila;
        $pdf->SetFillColor(...$grises[$i % count($grises)]);
        $pdf->Rect($lx, $ly + 0.8, 3.5, 3.5, 'F');
        $pdf->SetTextColor(0);
        $pdf->SetXY($lx + 4, $ly);
        $pdf->Cell($ancho_col - 16, 5, $etiquetas[$i], 0, 0, 'L');
        $pdf->Cell(10, 5, number_format($v / $total * 100, 1, ',', '.') . '%', 0, 0, 'R');
    }
}
