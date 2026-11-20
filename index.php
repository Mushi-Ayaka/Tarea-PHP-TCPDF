<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tarea de PHP con TCPDF - INDICE</title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        margin: 0;
        padding: 60px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .menu {
        display: flex;
        flex-direction: column;
        gap: 16px;
        width: 100%;
        max-width: 360px;
    }
    .btn {
        display: block;
        width: 100%;
        padding: 16px 20px;
        font-size: 15px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        color: #000;
        background: #fff;
        border: 2px solid #000;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .btn small {
        display: block;
        font-size: 11px;
        font-weight: normal;
        margin-top: 4px;
        opacity: 0.7;
    }
</style>
</head>
<body>
    <div class="menu">
        <a class="btn" href="doc1_factura.php" target="_blank">
            Factura
            <small>Servicios Tecnológicos Demo, C.A.</small>
        </a>
        <a class="btn" href="doc2_reporte.php" target="_blank">
            Reporte Estadístico
            <small>Inscripciones — Trimestre 2, 2026</small>
        </a>
    </div>
</body>
</html>