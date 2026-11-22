<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Correspondencia — Reportes PDF</title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        padding: 60px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        background: #f4f6f8;
        color: #1c2733;
    }
    h1 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 4px;
        text-align: center;
    }
    p.sub {
        font-size: 12px;
        color: #5b6570;
        margin: 0 0 32px;
        text-align: center;
    }
    .menu {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
        max-width: 360px;
    }
    .btn {
        display: block;
        width: 100%;
        padding: 14px 18px;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        text-decoration: none;
        color: #fff;
        background: #0b3d6b;
        border: 2px solid #0b3d6b;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn.btn-sec {
        background: #fff;
        color: #0b3d6b;
        border-color: #0b3d6b;
    }

</style>
</head>
<body>
    <h1>Registro de Correspondencia</h1>
    <p class="sub">Corporación Nacional de Insumos para la Salud</p>
    <div class="menu">
        <a class="btn" href="doc1_reporte_general.php" target="_blank">
            Reporte General
        </a>
        <a class="btn" href="doc2_reporte_estadistico.php" target="_blank">
            Reporte Estadístico
        </a>
        <a class="btn btn-sec" href="seed.php">
            Cargar datos demo
        </a>
    </div>
</body>
</html>
