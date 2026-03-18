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
            $stmt = $db->prepare("SELECT * FROM categoria WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Categoria no encontrada"], 404);
        } else {
            $stmt = $db->query("SELECT * FROM categoria ORDER BY nombre ASC");
            response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $body = getBody();
        if (empty($body['nombre']))
            response(["error" => "El nombre es obligatorio"], 400);

        $stmt = $db->prepare(
            "INSERT INTO categoria (nombre, color, usuario_id)
             VALUES (:nombre, :color, 1)
             RETURNING *"
        );
        $stmt->execute([
            ':nombre' => $body['nombre'],
            ':color'  => $body['color'] ?? '#3498DB',
        ]);
        response($stmt->fetch(), 201);
        break;

    default:
        response(["error" => "Metodo no permitido"], 405);
}