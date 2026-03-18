<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
setHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM evento WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Evento no encontrado"], 404);
        } else {
            $stmt = $db->query("SELECT * FROM evento ORDER BY fecha_inicio ASC");
            response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $body = getBody();
        if (empty($body['titulo']) || empty($body['fecha_inicio']))
            response(["error" => "titulo y fecha_inicio son obligatorios"], 400);

        $stmt = $db->prepare(
            "INSERT INTO evento (usuario_id, titulo, descripcion, fecha_inicio, fecha_fin, ubicacion)
             VALUES (1, :titulo, :descripcion, :fecha_inicio, :fecha_fin, :ubicacion)
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

    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        $stmt = $db->prepare(
            "UPDATE evento
             SET titulo       = COALESCE(:titulo,                  titulo),
                 descripcion  = COALESCE(:descripcion,             descripcion),
                 fecha_inicio = COALESCE(:fecha_inicio::timestamp, fecha_inicio),
                 fecha_fin    = COALESCE(:fecha_fin::timestamp,    fecha_fin),
                 ubicacion    = COALESCE(:ubicacion,               ubicacion)
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