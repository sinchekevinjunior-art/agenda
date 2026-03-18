<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
setHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    // GET /api/tareas/
    // GET /api/tareas/?id=1
    // GET /api/tareas/?completada=false
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM tarea WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Tarea no encontrada"], 404);
        } elseif (isset($_GET['completada'])) {
            // ✅ CORREGIDO: la BD usa campo "estado" (no "completada")
            $estado = $_GET['completada'] === 'true' ? 'completada' : 'pendiente';
            $stmt = $db->prepare("SELECT * FROM tarea WHERE estado = :estado ORDER BY fecha_vencimiento ASC");
            $stmt->execute([':estado' => $estado]);
            response($stmt->fetchAll());
        } else {
            // ✅ CORREGIDO: "fecha_limite" no existe → usar "fecha_vencimiento"
            $stmt = $db->query("SELECT * FROM tarea ORDER BY fecha_vencimiento ASC");
            response($stmt->fetchAll());
        }
        break;

    // POST /api/tareas/
    // Body: { "titulo":"Pagar luz", "fecha_vencimiento":"2026-03-25", "prioridad":"alta" }
    case 'POST':
        $body = getBody();
        if (empty($body['titulo']))
            response(["error" => "El titulo es obligatorio"], 400);

        $prioridad = $body['prioridad'] ?? 'media';
        if (!in_array($prioridad, ['baja', 'media', 'alta', 'urgente']))
            response(["error" => "Prioridad invalida (baja, media, alta, urgente)"], 400);

        // ✅ CORREGIDO: fecha_limite → fecha_vencimiento, completada → estado
        $stmt = $db->prepare(
            "INSERT INTO tarea (titulo, descripcion, fecha_vencimiento, prioridad, estado)
             VALUES (:titulo, :descripcion, :fecha_vencimiento, :prioridad, 'pendiente')
             RETURNING *"
        );
        $stmt->execute([
            ':titulo'            => $body['titulo'],
            ':descripcion'       => $body['descripcion']       ?? null,
            ':fecha_vencimiento' => $body['fecha_vencimiento'] ?? null,
            ':prioridad'         => $prioridad,
        ]);
        response($stmt->fetch(), 201);
        break;

    // PUT /api/tareas/?id=1
    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        // ✅ CORREGIDO: si mandan "completada: true" → traducir a estado='completada'
        $estado = null;
        if (isset($body['completada'])) {
            $estado = $body['completada'] ? 'completada' : 'pendiente';
        } elseif (isset($body['estado'])) {
            $estado = $body['estado'];
        }

        // ✅ CORREGIDO: columnas reales de la BD
        $stmt = $db->prepare(
            "UPDATE tarea
             SET titulo            = COALESCE(:titulo,                        titulo),
                 descripcion       = COALESCE(:descripcion,                   descripcion),
                 fecha_vencimiento = COALESCE(:fecha_vencimiento::date,       fecha_vencimiento),
                 prioridad         = COALESCE(:prioridad,                     prioridad),
                 estado            = COALESCE(:estado,                        estado)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'                => $id,
            ':titulo'            => $body['titulo']            ?? null,
            ':descripcion'       => $body['descripcion']       ?? null,
            ':fecha_vencimiento' => $body['fecha_vencimiento'] ?? null,
            ':prioridad'         => $body['prioridad']         ?? null,
            ':estado'            => $estado,
        ]);
        $data = $stmt->fetch();
        $data ? response($data) : response(["error" => "Tarea no encontrada"], 404);
        break;

    // DELETE /api/tareas/?id=1
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