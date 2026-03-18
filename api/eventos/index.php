<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
setHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    // GET /api/eventos/
    // GET /api/eventos/?id=1
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM evento WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Evento no encontrado"], 404);
        } else {
            // ✅ CORREGIDO: "fecha" no existe → usar "fecha_inicio"
            $stmt = $db->query("SELECT * FROM evento ORDER BY fecha_inicio ASC");
            response($stmt->fetchAll());
        }
        break;

    // POST /api/eventos/
    // Body: { "titulo":"Reunion", "fecha_inicio":"2026-03-20 10:00:00", "ubicacion":"Oficina" }
    case 'POST':
        $body = getBody();
        // ✅ CORREGIDO: campo obligatorio es "fecha_inicio" (no "fecha")
        if (empty($body['titulo']) || empty($body['fecha_inicio']))
            response(["error" => "titulo y fecha_inicio son obligatorios"], 400);

        // ✅ CORREGIDO: columnas reales de la BD (fecha_inicio, ubicacion, descripcion)
        $stmt = $db->prepare(
            "INSERT INTO evento (titulo, descripcion, fecha_inicio, fecha_fin, ubicacion)
             VALUES (:titulo, :descripcion, :fecha_inicio, :fecha_fin, :ubicacion)
             RETURNING *"
        );
        $stmt->execute([
            ':titulo'       => $body['titulo'],
            ':descripcion'  => $body['descripcion']  ?? null,
            ':fecha_inicio' => $body['fecha_inicio'],
            ':fecha_fin'    => $body['fecha_fin']     ?? null,
            ':ubicacion'    => $body['ubicacion']     ?? null,
        ]);
        response($stmt->fetch(), 201);
        break;

    // PUT /api/eventos/?id=1
    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        // ✅ CORREGIDO: columnas reales de la BD
        $stmt = $db->prepare(
            "UPDATE evento
             SET titulo       = COALESCE(:titulo,                    titulo),
                 descripcion  = COALESCE(:descripcion,               descripcion),
                 fecha_inicio = COALESCE(:fecha_inicio::timestamp,   fecha_inicio),
                 fecha_fin    = COALESCE(:fecha_fin::timestamp,      fecha_fin),
                 ubicacion    = COALESCE(:ubicacion,                 ubicacion)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'           => $id,
            ':titulo'       => $body['titulo']       ?? null,
            ':descripcion'  => $body['descripcion']  ?? null,
            ':fecha_inicio' => $body['fecha_inicio'] ?? null,
            ':fecha_fin'    => $body['fecha_fin']    ?? null,
            ':ubicacion'    => $body['ubicacion']    ?? null,
        ]);
        $data = $stmt->fetch();
        $data ? response($data) : response(["error" => "Evento no encontrado"], 404);
        break;

    // DELETE /api/eventos/?id=1
    case 'DELETE':
        if (!$id) response(["error" => "ID requerido"], 400);
        $stmt = $db->prepare("DELETE FROM evento WHERE id = :id RETURNING id");
        $stmt->execute([':id' => $id]);
        $stmt->fetch()
            ? response(["mensaje" => "Evento eliminado"])
            : response(["error"   => "Evento no encontrado"], 404);
        break;

    default:
        response(["error" => "Metodo no permitido"], 405);
}