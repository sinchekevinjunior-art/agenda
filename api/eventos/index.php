<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
setHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    // GET /api/eventos/         → todos los eventos
    // GET /api/eventos/?id=1    → un evento por ID
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM evento WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Evento no encontrado"], 404);
        } else {
            $stmt = $db->query("SELECT * FROM evento ORDER BY fecha ASC");
            response($stmt->fetchAll());
        }
        break;

    // POST /api/eventos/
    // Body: { "titulo":"Reunion", "fecha":"2026-03-20 10:00:00", "lugar":"Oficina", "contacto_id":1 }
    case 'POST':
        $body = getBody();
        if (empty($body['titulo']) || empty($body['fecha']))
            response(["error" => "titulo y fecha son obligatorios"], 400);

        $stmt = $db->prepare(
            "INSERT INTO evento (titulo, fecha, lugar, contacto_id)
             VALUES (:titulo, :fecha, :lugar, :contacto_id)
             RETURNING *"
        );
        $stmt->execute([
            ':titulo'      => $body['titulo'],
            ':fecha'       => $body['fecha'],
            ':lugar'       => $body['lugar']       ?? null,
            ':contacto_id' => $body['contacto_id'] ?? null,
        ]);
        response($stmt->fetch(), 201);
        break;

    // PUT /api/eventos/?id=1
    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        $stmt = $db->prepare(
            "UPDATE evento
             SET titulo      = COALESCE(:titulo,      titulo),
                 fecha       = COALESCE(:fecha::timestamp, fecha),
                 lugar       = COALESCE(:lugar,       lugar),
                 contacto_id = COALESCE(:contacto_id, contacto_id)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'          => $id,
            ':titulo'      => $body['titulo']      ?? null,
            ':fecha'       => $body['fecha']       ?? null,
            ':lugar'       => $body['lugar']       ?? null,
            ':contacto_id' => $body['contacto_id'] ?? null,
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
