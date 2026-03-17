<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
setHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    // GET /api/tareas/            → todas las tareas
    // GET /api/tareas/?id=1       → una tarea por ID
    // GET /api/tareas/?completada=false → filtrar por estado
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM tarea WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Tarea no encontrada"], 404);
        } elseif (isset($_GET['completada'])) {
            $val  = $_GET['completada'] === 'true' ? 'true' : 'false';
            $stmt = $db->query("SELECT * FROM tarea WHERE completada = $val ORDER BY fecha_limite");
            response($stmt->fetchAll());
        } else {
            $stmt = $db->query("SELECT * FROM tarea ORDER BY fecha_limite ASC");
            response($stmt->fetchAll());
        }
        break;

    // POST /api/tareas/
    // Body: { "titulo":"Pagar luz", "fecha_limite":"2026-03-25", "prioridad":"alta" }
    case 'POST':
        $body = getBody();
        if (empty($body['titulo']))
            response(["error" => "El titulo es obligatorio"], 400);

        $prioridad = $body['prioridad'] ?? 'media';
        if (!in_array($prioridad, ['baja', 'media', 'alta']))
            response(["error" => "Prioridad invalida (baja, media, alta)"], 400);

        $stmt = $db->prepare(
            "INSERT INTO tarea (titulo, fecha_limite, prioridad, completada)
             VALUES (:titulo, :fecha_limite, :prioridad, false)
             RETURNING *"
        );
        $stmt->execute([
            ':titulo'       => $body['titulo'],
            ':fecha_limite' => $body['fecha_limite'] ?? null,
            ':prioridad'    => $prioridad,
        ]);
        response($stmt->fetch(), 201);
        break;

    // PUT /api/tareas/?id=1
    // Body: campos a actualizar (incluido completada: true/false)
    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        $completada = isset($body['completada'])
            ? ($body['completada'] ? 'true' : 'false')
            : null;

        $stmt = $db->prepare(
            "UPDATE tarea
             SET titulo       = COALESCE(:titulo,       titulo),
                 fecha_limite = COALESCE(:fecha_limite::date, fecha_limite),
                 prioridad    = COALESCE(:prioridad,    prioridad),
                 completada   = COALESCE(:completada::boolean, completada)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'           => $id,
            ':titulo'       => $body['titulo']       ?? null,
            ':fecha_limite' => $body['fecha_limite'] ?? null,
            ':prioridad'    => $body['prioridad']    ?? null,
            ':completada'   => $completada,
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
