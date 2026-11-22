<?php
require_once __DIR__ . '/db.php';

$ya_tiene = (int)scalar('SELECT COUNT(*) FROM correspondencia') > 0;
if ($ya_tiene) {
    echo '<p>La base de datos ya tiene datos. No se insertó nada.</p>';
    exit;
}

$pdo = db();

$entidades = [
    ['MIN-SAL',  'Ministerio del Poder Popular para la Salud'],
    ['MPPS-REG', 'MPPS Región Capital'],
    ['SENIAT',   'Servicio Nacional Integrado de Administración Aduanera y Tributaria'],
    ['BCV',      'Banco Central de Venezuela'],
];
$ins_ent = $pdo->prepare(
    'INSERT OR IGNORE INTO entidades_externas (codigo, nombre, fecha) VALUES (?, ?, ?)'
);
foreach ($entidades as [$cod, $nom]) {
    $ins_ent->execute([$cod, $nom, '2024-01-15 08:00:00']);
}

$oficinas = [
    'Oficina de Gestión Administrativa', 
    'Oficina de Gestión Humana', 
    'Oficina de RII y Gestión Comunicacional', 
    'Presidencia', 
    'Oficina de Planificación y Presupuesto',
    'Oficina de Tec. para Inf. y la Comunicación', 
    'Oficina de Atención al Ciudadano', 
    'Consultoría Jurídica', 
    'Auditoría Interna', 
    'Gerencia Gral de Ing. y Mantenimiento Clínico', 
    'Gerencia Gral. de Almacenamiento Dist. y Logística', 
    'Oficina de Comercialización', 
    'Oficina de Seguridad Integral', 
    'Oficina de Inv., Formación y Des.', 
    'Gerencia General de Electromedicina/Estado', 
    'Transporte',
];

$personas = [
    ['Ing. María Pulido',       'Presidencia'],
    ['Lcdo. Carlos Ramos',      'Administración'],
    ['Dra. Luisa Hernández',    'Consultoría Jurídica'],
    ['T.S.U. Pedro Álvarez',    'Recursos Humanos'],
    ['Ing. Ana Gómez',          'OFTIC'],
    ['Lcda. Rosa Martínez',     'Planificación'],
    ['Lic. Juan Torres',        'Auditoría Interna'],
    ['Ing. Sofía Medina',       'GGIMC'],
];

$asuntos = [
    'Solicitud de dotación de insumos médicos',
    'Informe mensual de actividades de mantenimiento',
    'Comunicación interna sobre ajuste de horarios',
    'Remisión de documentos para trámite administrativo',
    'Requerimiento de presupuesto para equipos de cómputo',
    'Notificación de auditoría programada',
    'Solicitud de permiso especial de personal',
    'Circular sobre nuevas disposiciones de seguridad',
    'Envío de acta de reunión del comité técnico',
    'Solicitud de transferencia de fondos',
    'Reporte de incidencias en el sistema de red',
    'Comunicación sobre vencimiento de contratos',
    'Oficio de respuesta a requerimiento ministerial',
    'Solicitud de información estadística',
    'Remisión de nómina para revisión',
    'Propuesta de plan de formación continua',
    'Informe de gestión trimestral',
    'Solicitud de servicio de transporte institucional',
    'Aviso de actualización de normativa interna',
    'Requerimiento de reparación de equipos electromédicos',
];

$estados  = ['recibido', 'recibido', 'en_proceso', 'en_proceso', 'culminado'];
$tipos    = ['entrada', 'entrada', 'salida'];

$ins = $pdo->prepare("
    INSERT INTO correspondencia
        (anio, oficina, origen, folio, sufijo, tipo,
         fecha_emision, fecha_recepcion, de_nombre, para_nombre,
         para_cargo, asunto, estado)
    VALUES
        (:anio, :oficina, 'interno', :folio, :sufijo, :tipo,
         :fem, :frec, :de, :para,
         :cargo, :asunto, :estado)
");

$contador = []; 
$total_id = 0;

srand(42);  

foreach ([2023, 2024, 2025, 2026] as $anio) {
    $n_docs = match($anio) { 2023 => 28, 2024 => 45, 2025 => 52, 2026 => 31 };

    for ($i = 0; $i < $n_docs; $i++) {
        $oficina = $oficinas[array_rand($oficinas)];
        $clave   = "$oficina-$anio";
        $contador[$clave] = ($contador[$clave] ?? 0) + 1;
        $nro     = str_pad((string)$contador[$clave], 4, '0', STR_PAD_LEFT);

        $mes  = rand(1, 12);
        $dia  = rand(1, 28);
        $fem  = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        $frec = sprintf('%04d-%02d-%02d', $anio, $mes, min(28, $dia + rand(1, 3)));
        $tipo   = $tipos[array_rand($tipos)];
        $estado = $estados[array_rand($estados)];
        $de     = $personas[array_rand($personas)];
        $para   = $personas[array_rand($personas)];
        $asunto = $asuntos[array_rand($asuntos)];
        $folio  = 'C' . str_pad((string)(++$total_id), 8, '0', STR_PAD_LEFT);
        $sufijo = $oficina . '-' . $anio . '-' . $nro;

        $ins->execute([
            ':anio'   => $anio,
            ':oficina'=> $oficina,
            ':folio'  => $folio,
            ':sufijo' => $sufijo,
            ':tipo'   => $tipo,
            ':fem'    => $fem,
            ':frec'   => $frec,
            ':de'     => $de[0],
            ':para'   => $para[0],
            ':cargo'  => $para[1],
            ':asunto' => $asunto,
            ':estado' => $estado,
        ]);

        if ($estado === 'culminado' && rand(0, 1)) {
            $pdo->prepare("
                INSERT INTO entregas
                    (correspondencia_id, fecha_entrega, entregado_a,
                     entregado_cargo, entregado_oficina, fecha_registro)
                VALUES (last_insert_rowid(), ?, ?, ?, ?, ?)
            ")->execute([$frec, $para[0], $para[1], $oficina, $frec . ' 10:00:00']);
        }
    }
}

$total_corr = scalar('SELECT COUNT(*) FROM correspondencia');
$total_ent  = scalar('SELECT COUNT(*) FROM entregas');
echo "<p>✓ Seed completado: <strong>$total_corr</strong> correspondencias, <strong>$total_ent</strong> entregas.</p>";
echo "<p><a href='index.php'>← Volver al índice</a></p>";
