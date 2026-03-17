<?php
// ============================================================
//  CONFIGURACIÓN DE CONEXIÓN A SUPABASE (PostgreSQL)
//  Reemplaza los valores con los de tu proyecto en Supabase
//  Settings > Database > Connection parameters
// ============================================================

define('DB_HOST',     'db.ooxfyrlyjcjdqdmunktb.supabase.co');
define('DB_PORT',     '5432');
define('DB_NAME',     'postgres');
define('DB_USER',     'postgres');
define('DB_PASSWORD', 'agenda2026_sinche');

function getDB() {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
        exit();
    }
}
