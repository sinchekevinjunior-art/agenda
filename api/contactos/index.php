<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
setHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    // GET /api/contactos/         → todos los contactos
    // GET /api/contactos/?id=1    → un contacto por ID
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM contacto WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Contacto no encontrado"], 404);
        } else {
            $stmt = $db->query("SELECT * FROM contacto ORDER BY nombre");
            response($stmt->fetchAll());
        }
        break;

    // POST /api/contactos/
    // Body: { "nombre":"Ana", "telefono":"999", "email":"ana@mail.com", "notas":"..." }
    case 'POST':
        $body = getBody();
        if (empty($body['nombre']))
            response(["error" => "El nombre es obligatorio"], 400);

        $stmt = $db->prepare(
            "INSERT INTO contacto (nombre, telefono, email, notas)
             VALUES (:nombre, :telefono, :email, :notas)
             RETURNING *"
        );
        $stmt->execute([
            ':nombre'   => $body['nombre'],
            ':telefono' => $body['telefono'] ?? null,
            ':email'    => $body['email']    ?? null,
            ':notas'    => $body['notas']    ?? null,
        ]);
        response($stmt->fetch(), 201);
        break;

    // PUT /api/contactos/?id=1
    // Body: campos a actualizar
    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        $stmt = $db->prepare(
            "UPDATE contacto
             SET nombre   = COALESCE(:nombre,   nombre),
                 telefono = COALESCE(:telefono, telefono),
                 email    = COALESCE(:email,    email),
                 notas    = COALESCE(:notas,    notas)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'       => $id,
            ':nombre'   => $body['nombre']   ?? null,
            ':telefono' => $body['telefono'] ?? null,
            ':email'    => $body['email']    ?? null,
            ':notas'    => $body['notas']    ?? null,
        ]);
        $data = $stmt->fetch();
        $data ? response($data) : response(["error" => "Contacto no encontrado"], 404);
        break;

    // DELETE /api/contactos/?id=1
    case 'DELETE':
        if (!$id) response(["error" => "ID requerido"], 400);
        $stmt = $db->prepare("DELETE FROM contacto WHERE id = :id RETURNING id");
        $stmt->execute([':id' => $id]);
        $stmt->fetch()
            ? response(["mensaje" => "Contacto eliminado"])
            : response(["error"   => "Contacto no encontrado"], 404);
        break;

    default:
        response(["error" => "Método no permitido"], 405);
}
