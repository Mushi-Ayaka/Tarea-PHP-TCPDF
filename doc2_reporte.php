<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ob_start();
require_once __DIR__ . '/tcpdf/tcpdf.php';

// DATOS 
$titulo  = 'Reporte Estadístico de Inscripciones';
$periodo = 'Trimestre 2 - Año 2026';
$datos = [
    ['carrera' => 'Informática',    'inscritos' => 120, 'aprobados' => 98],
    ['carrera' => 'Administración', 'inscritos' => 95,  'aprobados' => 80],
    ['carrera' => 'Contaduría',     'inscritos' => 70,  'aprobados' => 52],
    ['carrera' => 'Electrónica',    'inscritos' => 55,  'aprobados' => 41],
    ['carrera' => 'Turismo',        'inscritos' => 40,  'aprobados' => 36],
];

// Tonos de gris para distinguir sectores del pastel
$GRAYES = [[60,60,60],[100,100,100],[140,140,140],[180,180,180],[220,220,220],[40,40,40],[120,120,120]]; 
// Colores alternativos a escala de grises porsiacaso = [[41, 128, 185], [39,174,96], [243,156,18], [192,57,43], [142,68,173], [22,160,133], [211,84,0] ];

function n0($n) { return number_format($n, 0, ',', '.'); }
function n1($n) { return number_format($n, 1, ',', '.'); }

// HELPERS 
function tarjeta($pdf, $x, $y, $w, $h, $titulo, $valor) {
    $pdf->SetDrawColor(0);
    $pdf->Rect($x, $y, $w, $h);
    $pdf->SetTextColor(90);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY($x + 4, $y + 2);
    $pdf->Cell($w - 5, 5, $titulo, 0, 0, 'L');
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetXY($x + 4, $y + 8);
    $pdf->Cell($w - 5, 9, $valor, 0, 0, 'L');
}

function graficoBarras($pdf, $x, $y, $w, $h, $titulo, $etiquetas, $valores) {
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
    $tope  = ceil(max($valores) / 20) * 20;

    $pdf->SetFont('helvetica', '', 7);
    for ($i = 0; $i <= $l=5; $i++) {
        $v  = $tope * $i / $l;
        $yy = $base - ($v / $tope) * $areaH;
        $pdf->SetDrawColor(220);
        $pdf->Line($areaX, $yy, $areaX + $areaW, $yy);
        $pdf->SetTextColor(100);
        $pdf->SetXY($x, $yy - 2);
        $pdf->Cell(11, 4, round($v), 0, 0, 'R');
    }

    $n      = count($valores);
    $ranura = $areaW / $n;
    $barraW = $ranura * 0.6;
    foreach ($valores as $i => $v) {
        $alto = ($v / $tope) * $areaH;
        $bx   = $areaX + $i * $ranura + ($ranura - $barraW) / 2;
        $by   = $base - $alto;
        $pdf->SetFillColor(80);
        $pdf->Rect($bx, $by, $barraW, $alto, 'F');

        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($bx - 5, $by - 5);
        $pdf->Cell($barraW + 10, 4, $v, 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetXY($areaX + $i * $ranura, $base + 1.5);
        $pdf->Cell($ranura, 4, $etiquetas[$i], 0, 0, 'C');
    }

    $pdf->SetDrawColor(0);
    $pdf->SetLineWidth(0.4);
    $pdf->Line($areaX, $areaY, $areaX, $base);
    $pdf->Line($areaX, $base, $areaX + $areaW, $base);
}

function graficoTorta($pdf, $x, $y, $w, $h, $titulo, $etiquetas, $valores, $paleta) {
    $pdf->SetDrawColor(150);
    $pdf->Rect($x, $y, $w, $h);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($w, 6, $titulo, 0, 0, 'C');

    $total = array_sum($valores);
    $cx = $x + $w / 2;
    $cy = $y + 36;
    $r  = 22;
    $ang = 0;

    $pdf->SetDrawColor(255);
    $pdf->SetLineWidth(0.6);
    foreach ($valores as $i => $v) {
        $grados = $v / $total * 360;
        $pdf->SetFillColor(...$paleta[$i % count($paleta)]);
        $pdf->PieSector($cx, $cy, $r, $ang, $ang + $grados, 'FD');
        $ang += $grados;
    }

    $n = count($valores);
    $columnas = $n > 5 ? 2 : 1;
    $filasPorCol = (int)ceil($n / $columnas);
    $gapCol   = 8;
    $anchoCol = ($w - 10) / $columnas;

    $lyInicio = $y + 62;
    $espacio  = $h - 62;
    $alto     = min(5, $espacio / $filasPorCol);

    $pdf->SetFont('helvetica', '', 7);
    foreach ($valores as $i => $v) {
        $col  = intdiv($i, $filasPorCol);
        $fila = $i % $filasPorCol;
        $lx   = $x + 4 + $col * ($anchoCol + $gapCol);
        $ly   = $lyInicio + $fila * $alto;

        $pdf->SetFillColor(...$paleta[$i % count($paleta)]);
        $pdf->Rect($lx, $ly + 0.8, 3.5, 3.5, 'F');
        $pdf->SetTextColor(0);
        $pdf->SetXY($lx + 4, $ly);
        $pdf->Cell($anchoCol - 16, 5, $etiquetas[$i], 0, 0, 'L');
        $pdf->Cell(10, 5, n1($v / $total * 100).'%', 0, 0, 'R');
    }
}

// CLASE PDF 
class ReportePDF extends TCPDF
{
    public $titulo = '', $periodo = '';

    public function Header()
    {
        $this->SetFillColor(0);
        $this->Rect(0, 0, $this->getPageWidth(), 26, 'F');
        $this->SetTextColor(255);
        $this->SetFont('helvetica', 'B', 17);
        $this->SetXY(15, 6);
        $this->Cell(185, 8, $this->titulo, 0, 1, 'L');
        $this->SetFont('helvetica', '', 10);
        $this->SetX(15);
        $this->Cell(120, 6, $this->periodo, 0, 0, 'L');
        $this->Cell(65, 6, 'Generado: '.date('d/m/Y H:i'), 0, 0, 'R');
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetTextColor(110);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 6, 'Página '.$this->PageNo().' de '.$this->getAliasNbPages(), 0, 0, 'C');
    }
}

// CÁLCULOS 
$inscritos  = array_column($datos, 'inscritos');
$etiquetas  = array_column($datos, 'carrera');
$total      = array_sum($inscritos);
$promedio   = $total / count($inscritos);
$maximo     = max($inscritos);
$minimo     = min($inscritos);
$totalAprob = array_sum(array_column($datos, 'aprobados'));
$pctAprob   = $totalAprob / $total * 100;
$mayor      = $datos[array_search($maximo, $inscritos)];

// GENERAR PDF 
$pdf = new ReportePDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->titulo  = $titulo;
$pdf->periodo = $periodo;
$pdf->SetTitle($titulo);
$pdf->SetMargins(15, 32, 15);
$pdf->SetAutoPageBreak(true, 22);
$pdf->AddPage();

// Tarjetas resumen
$yc = 32;
tarjeta($pdf, 15,    $yc, 42.5, 20, 'TOTAL INSCRITOS',    n0($total));
tarjeta($pdf, 62.5,  $yc, 42.5, 20, 'PROMEDIO / CARRERA', n1($promedio));
tarjeta($pdf, 110,   $yc, 42.5, 20, 'MÁXIMO',             n0($maximo));
tarjeta($pdf, 157.5, $yc, 42.5, 20, 'MÍNIMO',             n0($minimo));

// Tabla
$pdf->SetY($yc + 26);
$anchos  = [65, 40, 40, 40];
$titulos = ['Carrera', 'Inscritos', 'Aprobados', '% Aprobación'];

$pdf->SetFillColor(0);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', 'B', 10);
foreach ($titulos as $i => $t) $pdf->Cell($anchos[$i], 8, $t, 1, 0, 'C', true);
$pdf->Ln();

$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 10);
foreach ($datos as $f) {
    $pdf->Cell($anchos[0], 8, $f['carrera'], 1, 0, 'L');
    $pdf->Cell($anchos[1], 8, n0($f['inscritos']), 1, 0, 'C');
    $pdf->Cell($anchos[2], 8, n0($f['aprobados']), 1, 0, 'C');
    $pdf->Cell($anchos[3], 8, n1($f['aprobados'] / $f['inscritos'] * 100).'%', 1, 1, 'C');
}

// Fila de totales
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(210);
$pdf->Cell($anchos[0], 8, 'TOTAL',           1, 0, 'L', true);
$pdf->Cell($anchos[1], 8, n0($total),        1, 0, 'C', true);
$pdf->Cell($anchos[2], 8, n0($totalAprob),   1, 0, 'C', true);
$pdf->Cell($anchos[3], 8, n1($pctAprob).'%', 1, 1, 'C', true);

// Gráficos
$yg = $pdf->GetY() + 8;
graficoBarras($pdf, 15,  $yg, 105, 90, 'Inscritos por carrera', $etiquetas, $inscritos);
graficoTorta ($pdf, 126, $yg, 74,  90, 'Distribución (%)',      $etiquetas, $inscritos, $GRAYES);

// Conclusiones
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0);
$html = '<b>Conclusiones</b><br>'
      . 'La carrera con mayor número de inscritos es <b>'.$mayor['carrera'].'</b> con '
      . n0($mayor['inscritos']).' estudiantes ('.n1($mayor['inscritos'] / $total * 100).'% del total). '
      . 'El promedio por carrera es de <b>'.n1($promedio).'</b> inscritos y el porcentaje general de '
      . 'aprobación alcanza <b>'.n1($pctAprob).'%</b>.';
$pdf->writeHTMLCell(185, 0, 15, $yg + 96, $html, 1, 1, true, true, 'L', true);

ob_end_clean();
$pdf->Output('reporte_estadistico.pdf', 'I');