<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ob_start();
require_once __DIR__ . '/tcpdf/tcpdf.php';

// DATOS
$empresa = [
    'nombre'    => 'Servicios Tecnológicos Demo, C.A.',
    'rif'       => 'J-12345678-9',
    'direccion' => 'Av. Principal, Edif. Centro, Piso 2',
    'telefono'  => '0212-555-0000',
];
$factura = [
    'numero'    => '000125',
    'control'   => '00-000125',
    'fecha'     => date('d/m/Y'),
    'condicion' => 'Contado',
];
$cliente = [
    'nombre'    => 'Juan Pérez',
    'rif'       => 'V-12.345.678',
    'direccion' => 'Caracas, Venezuela',
];
$items = [
    ['desc' => 'Mantenimiento de computadora',     'cant' => 2, 'precio' => 25.00],
    ['desc' => 'Instalación de sistema operativo', 'cant' => 1, 'precio' => 15.50],
    ['desc' => 'Memoria RAM 8GB',                  'cant' => 2, 'precio' => 32.75],
    ['desc' => 'Disco SSD 480GB',                  'cant' => 1, 'precio' => 48.00],
    ['desc' => 'Cable HDMI',                       'cant' => 3, 'precio' => 4.20],
];

$MONEDA = 'Bs.';
$IVA = 16;
$DESCUENTO = 0;
$MARCA_AGUA = 'PAGADO';
$MOSTRAR_QR = true;
$LOGO = __DIR__ . '/logo.jpg';

function fmt($n) { return number_format($n, 2, ',', '.'); }

// CLASE PDF
class FacturaPDF extends TCPDF
{
    public $empresa = [], $factura = [], $marcaAgua = '', $logo = '';

    public function Header()
    {
        if ($this->marcaAgua) {
            $this->SetAlpha(0.10);
            $this->SetFont('helvetica', 'B', 80);
            $this->StartTransform();
            $this->Rotate(45, 108, 150);
            $this->Text(45, 160, $this->marcaAgua);
            $this->StopTransform();
            $this->SetAlpha(1);
        }


        $this->SetFillColor(0);
        $this->Rect(0, 0, $this->getPageWidth(), 32, 'F');


        $x = 15;
        if ($this->logo && file_exists($this->logo)) {
            $this->Image($this->logo, 15, 6, 20, 20);
            $x = 38;
        }


        $this->SetTextColor(255);
        $this->SetFont('helvetica', 'B', 15);
        $this->SetXY($x, 7);
        $this->Cell(100, 8, $this->empresa['nombre'], 0, 1, 'L');

        
        $this->SetFont('helvetica', '', 9);
        foreach (['RIF: '.$this->empresa['rif'], $this->empresa['direccion'], 'Telf.: '.$this->empresa['telefono']] as $linea) {
            $this->SetX($x);
            $this->Cell(100, 5, $linea, 0, 1, 'L');
        }

        $this->SetFillColor(255);
        $this->Rect(145, 7, 56, 18, 'F');
        $this->SetTextColor(0);
        $this->SetFont('helvetica', 'B', 13);
        $this->SetXY(145, 9);
        $this->Cell(56, 7, 'FACTURA', 0, 1, 'C');
        $this->SetFont('helvetica', 'B', 11);
        $this->SetX(145);
        $this->Cell(56, 6, 'N° '.$this->factura['numero'], 0, 0, 'C');
    }

    public function Footer()
    {
        $this->SetY(-18);
        $this->SetDrawColor(0);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->SetTextColor(100);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(130, 6, 'Documento generado electrónicamente', 0, 0, 'L');
        $this->Cell(0, 6, 'Página '.$this->PageNo().' de '.$this->getAliasNbPages(), 0, 0, 'R');
    }
}

function campo($pdf, $x, $y, $etiqueta, $valor, $w = 24) {
    $pdf->SetXY($x, $y);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell($w, 6, $etiqueta, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(60, 6, $valor, 0, 0, 'L');
}

function lineaTotal($pdf, $y, $etiqueta, $valor, $moneda) {
    $pdf->SetXY(120, $y);
    $pdf->Cell(45, 7, $etiqueta, 1, 0, 'L');
    $pdf->Cell(35, 7, "$moneda $valor", 1, 1, 'R');
}

$pdf = new FacturaPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->empresa   = $empresa;
$pdf->factura   = $factura;
$pdf->marcaAgua = $MARCA_AGUA;
$pdf->logo      = $LOGO;
$pdf->SetCreator('PHP + TCPDF');
$pdf->SetTitle('Factura N° '.$factura['numero']);
$pdf->SetMargins(15, 38, 15);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

$y = 38;
$pdf->SetDrawColor(0);
$pdf->Rect(15, $y, 185, 26);
campo($pdf, 18,  $y + 3,  'Cliente:',    $cliente['nombre']);
campo($pdf, 18,  $y + 10, 'RIF/C.I.:',   $cliente['rif']);
campo($pdf, 18,  $y + 17, 'Dirección:',  $cliente['direccion']);
campo($pdf, 115, $y + 3,  'Fecha:',      $factura['fecha'], 28);
campo($pdf, 115, $y + 10, 'Nº Control:', $factura['control'], 28);
campo($pdf, 115, $y + 17, 'Condición:',  $factura['condicion'], 28);

$pdf->SetY($y + 32);
$anchos  = [90, 20, 35, 40];
$titulos = ['Descripción', 'Cant.', 'Precio unit.', 'Subtotal'];

$pdf->SetFillColor(0);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', 'B', 10);
foreach ($titulos as $i => $t) {
    $pdf->Cell($anchos[$i], 8, $t, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 10);
$subtotal = 0;
foreach ($items as $it) {
    $importe   = $it['cant'] * $it['precio'];
    $subtotal += $importe;
    $pdf->Cell($anchos[0], 8, $it['desc'],        1, 0, 'L');
    $pdf->Cell($anchos[1], 8, $it['cant'],        1, 0, 'C');
    $pdf->Cell($anchos[2], 8, fmt($it['precio']), 1, 0, 'R');
    $pdf->Cell($anchos[3], 8, fmt($importe),      1, 1, 'R');
    if ($pdf->GetY() > 220) $pdf->AddPage();
}

// TOTALES 
$descuento = $subtotal * $DESCUENTO / 100;
$base      = $subtotal - $descuento;
$montoIva  = $base * $IVA / 100;
$total     = $base + $montoIva;

$yT = $pdf->GetY() + 6;
if ($yT > 200) { $pdf->AddPage(); $yT = 40; }

$pdf->SetFont('helvetica', '', 10);
lineaTotal($pdf, $yT,      'Subtotal',                fmt($subtotal),  $MONEDA);
lineaTotal($pdf, $yT + 7,  "Descuento ($DESCUENTO%)", fmt($descuento), $MONEDA);
lineaTotal($pdf, $yT + 14, "IVA ($IVA%)",             fmt($montoIva),  $MONEDA);

$pdf->SetFillColor(0);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetXY(120, $yT + 21);
$pdf->Cell(45, 9, 'TOTAL', 1, 0, 'L', true);
$pdf->Cell(35, 9, "$MONEDA ".fmt($total), 1, 1, 'R', true);

// QR 
if ($MOSTRAR_QR) {
    $textoQR = "FACTURA {$factura['numero']} | {$empresa['rif']} | TOTAL $total";
    $estilo  = ['border' => 0, 'fgcolor' => [0,0,0], 'bgcolor' => false];
    $pdf->write2DBarcode($textoQR, 'QRCODE,M', 15, $yT, 32, 32, $estilo, 'N');
}

// OBSERVACIONES Y FIRMAS
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(185, 16,
    "Observaciones: Garantía de 30 días en equipos y repuestos. ".
    "No se aceptan devoluciones sin esta factura.",
    1, 'L', false, 1, 15, $yT + 38);

$yF = $pdf->GetY() + 18;
$pdf->Line(25, $yF, 85, $yF);
$pdf->Line(130, $yF, 190, $yF);
$pdf->SetXY(25,  $yF + 1); $pdf->Cell(60, 5, 'Recibí conforme',  0, 0, 'C');
$pdf->SetXY(130, $yF + 1); $pdf->Cell(60, 5, 'Firma autorizada', 0, 0, 'C');

ob_end_clean();
$pdf->Output('factura_'.$factura['numero'].'.pdf', 'I');