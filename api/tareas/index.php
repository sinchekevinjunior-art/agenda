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
            $stmt = $db->prepare("SELECT * FROM tarea WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Tarea no encontrada"], 404);
        } elseif (isset($_GET['completada'])) {
            $estado = $_GET['completada'] === 'true' ? 'completada' : 'pendiente';
            $stmt = $db->prepare("SELECT * FROM tarea WHERE estado = :estado ORDER BY fecha_vencimiento ASC");
            $stmt->execute([':estado' => $estado]);
            response($stmt->fetchAll());
        } else {
            $stmt = $db->query("SELECT * FROM tarea ORDER BY fecha_vencimiento ASC");
            response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $body = getBody();
        if (empty($body['titulo']))
            response(["error" => "El titulo es obligatorio"], 400);

        $prioridad = $body['prioridad'] ?? 'media';
        if (!in_array($prioridad, ['baja', 'media', 'alta', 'urgente']))
            $prioridad = 'media';

        $stmt = $db->prepare(
            "INSERT INTO tarea (usuario_id, categoria_id, titulo, descripcion, fecha_vencimiento, prioridad, estado)
             VALUES (1, :categoria_id, :titulo, :descripcion, :fecha_vencimiento, :prioridad, :estado)
             RETURNING *"
        );
        $stmt->execute([
            ':categoria_id'    => $body['categoria_id']    ?? null,
            ':titulo'          => $body['titulo'],
            ':descripcion'     => $body['descripcion']     ?? null,
            ':fecha_vencimiento'=> $body['fecha_vencimiento'] ?? null,
            ':prioridad'       => $prioridad,
            ':estado'          => $body['estado']          ?? 'pendiente',
        ]);
        response($stmt->fetch(), 201);
        break;

    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        $estado = null;
        if (isset($body['completada'])) {
            $estado = $body['completada'] ? 'completada' : 'pendiente';
        } elseif (isset($body['estado'])) {
            $estado = $body['estado'];
        }

        $stmt = $db->prepare(
            "UPDATE tarea
             SET categoria_id     = COALESCE(:categoria_id,                 categoria_id),
                 titulo           = COALESCE(:titulo,                       titulo),
                 descripcion      = COALESCE(:descripcion,                  descripcion),
                 fecha_vencimiento= COALESCE(:fecha_vencimiento::date,      fecha_vencimiento),
                 prioridad        = COALESCE(:prioridad,                    prioridad),
                 estado           = COALESCE(:estado,                       estado)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'               => $id,
            ':categoria_id'     => $body['categoria_id']     ?? null,
            ':titulo'           => $body['titulo']           ?? null,
            ':descripcion'      => $body['descripcion']      ?? null,
            ':fecha_vencimiento'=> $body['fecha_vencimiento']?? null,
            ':prioridad'        => $body['prioridad']        ?? null,
            ':estado'           => $estado,
        ]);
        $data = $stmt->fetch();
        $data ? response($data) : response(["error" => "Tarea no encontrada"], 404);
        break;

    case 'DELETE':
        if (!$id) response(["error" => "ID requerido"], 400);
        $stmt = $db->prepare("DELETE FROM tarea WHERE id = :id RETURNING id");
        $stmt->execute([':id' => $id]);
        $stmt->fetch()
            ? response(["mensaje" => "Tarea eliminada"])
            : response(["error"   => "Tarea no encontrada"], 404);
        break;

    default:
        response(["error" => "Metodo no permitido"], 405);
}