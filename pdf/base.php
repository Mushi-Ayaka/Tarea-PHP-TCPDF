<?php

require_once __DIR__ . '/../tcpdf/tcpdf.php';
require_once __DIR__ . '/../config.php';

class PdfBase extends TCPDF
{
    public string $titulo    = '';
    public string $subtitulo = '';
    public string $logo      = LOGO_PATH;

    public function Header(): void
    {
        $this->SetFillColor(0);
        $this->Rect(0, 0, $this->getPageWidth(), 30, 'F');

        if ($this->logo && file_exists($this->logo)) {
            $this->Image($this->logo, 15, 5, 20, 20);
        }

        $ox = $this->logo && file_exists($this->logo) ? 40 : 15;

        $this->SetTextColor(255);
        $this->SetFont('helvetica', 'B', 15);
        $this->SetXY($ox, 7);
        $this->Cell(0, 8, $this->titulo, 0, 1, 'L');

        $this->SetFont('helvetica', '', 9);
        $this->SetX($ox);
        $this->Cell(120, 5, $this->subtitulo, 0, 0, 'L');
        $this->Cell(0,   5, 'Generado: ' . date('d/m/Y H:i'), 0, 0, 'R');
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetDrawColor(0);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->SetTextColor(110);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(130, 6, 'Documento generado electrónicamente — ' . ORG_NOMBRE, 0, 0, 'L');
        //$this->Cell(0,   6, 'Pág. ' . $this->PageNo() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}
