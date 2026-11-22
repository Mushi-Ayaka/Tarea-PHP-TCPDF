<?php

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (!is_dir(DB_DIR)) mkdir(DB_DIR, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    _crear_esquema($pdo);
    return $pdo;
}

function _crear_esquema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS entidades_externas (
            id          INTEGER PRIMARY KEY,
            codigo      TEXT    NOT NULL UNIQUE,
            nombre      TEXT    NOT NULL,
            fecha       TEXT    NOT NULL,
            inhabilitado INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS correspondencia (
            id                  INTEGER PRIMARY KEY,
            anio                INTEGER NOT NULL,
            oficina             TEXT    NOT NULL DEFAULT 'MEM',
            origen              TEXT    NOT NULL DEFAULT 'interno'
                                    CHECK (origen IN ('interno','externo')),
            entidad_externa_id  INTEGER NULL REFERENCES entidades_externas(id),
            folio               TEXT    UNIQUE NOT NULL,
            sufijo              TEXT,
            nomenclatura        TEXT,
            tipo                TEXT    NOT NULL DEFAULT 'entrada'
                                    CHECK (tipo IN ('entrada','salida')),
            fecha_emision       TEXT    NOT NULL,
            fecha_recepcion     TEXT    NOT NULL,
            de_nombre           TEXT    NOT NULL,
            para_nombre         TEXT    NOT NULL,
            para_cargo          TEXT,
            atencion_nombre     TEXT,
            atencion_cargo      TEXT,
            asunto              TEXT    NOT NULL,
            observaciones       TEXT,
            estado              TEXT    NOT NULL DEFAULT 'recibido'
                                    CHECK (estado IN ('recibido','en_proceso','culminado')),
            eliminado           INTEGER NOT NULL DEFAULT 0
        );

        CREATE INDEX IF NOT EXISTS idx_corr_fecha   ON correspondencia (fecha_recepcion DESC);
        CREATE INDEX IF NOT EXISTS idx_corr_estado  ON correspondencia (estado);
        CREATE INDEX IF NOT EXISTS idx_corr_oficina ON correspondencia (oficina);

        CREATE TABLE IF NOT EXISTS entregas (
            id                  INTEGER PRIMARY KEY,
            correspondencia_id  INTEGER NOT NULL UNIQUE REFERENCES correspondencia(id),
            fecha_entrega       TEXT    NOT NULL,
            entregado_a         TEXT    NOT NULL,
            entregado_cargo     TEXT,
            entregado_oficina   TEXT,
            instruccion         TEXT,
            fecha_registro      TEXT    NOT NULL
        );

        CREATE TABLE IF NOT EXISTS config (
            clave TEXT PRIMARY KEY,
            valor TEXT NOT NULL
        );
    ");

    // Valor por defecto del nombre del organismo en config
    $pdo->exec("
        INSERT OR IGNORE INTO config (clave, valor)
        VALUES ('nombre_organismo', '" . ORG_NOMBRE . "')
    ");
}

// Helpers de consulta

/** Un único valor escalar; devuelve $defecto si no hay fila. */
function scalar(string $sql, array $params = [], $defecto = 0) {
    $st = db()->prepare($sql);
    $st->execute($params);
    $v  = $st->fetchColumn();
    return ($v === false) ? $defecto : $v;
}

/** Tds las filas como array asociativo. */
function rows(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Primera fila o null. */
function row(string $sql, array $params = []): ?array {
    $st = db()->prepare($sql);
    $st->execute($params);
    $r  = $st->fetch();
    return $r ?: null;
}
