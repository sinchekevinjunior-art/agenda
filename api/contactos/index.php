<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
setHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    // GET /api/contactos/
    // GET /api/contactos/?id=1
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM contacto WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Contacto no encontrado"], 404);
        } else {
            $stmt = $db->query("SELECT * FROM contacto ORDER BY nombre ASC");
            response($stmt->fetchAll());
        }
        break;

    // POST /api/contactos/
    // Body: { "nombre":"Ana", "apellido":"López", "telefono":"999", "email":"ana@mail.com", "notas":"..." }
    case 'POST':
        $body = getBody();
        if (empty($body['nombre']))
            response(["error" => "El nombre es obligatorio"], 400);

        // ✅ CORREGIDO: la BD tiene columna "apellido" y requiere usuario_id
        // Usamos usuario_id=1 por defecto (usuario de ejemplo del script SQL)
        $stmt = $db->prepare(
            "INSERT INTO contacto (usuario_id, nombre, apellido, telefono, email, notas)
             VALUES (1, :nombre, :apellido, :telefono, :email, :notas)
             RETURNING *"
        );
        $stmt->execute([
            ':nombre'   => $body['nombre'],
            ':apellido' => $body['apellido'] ?? null,
            ':telefono' => $body['telefono'] ?? null,
            ':email'    => $body['email']    ?? null,
            ':notas'    => $body['notas']    ?? null,
        ]);
        response($stmt->fetch(), 201);
        break;

    // PUT /api/contactos/?id=1
    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        $stmt = $db->prepare(
            "UPDATE contacto
             SET nombre   = COALESCE(:nombre,   nombre),
                 apellido = COALESCE(:apellido, apellido),
                 telefono = COALESCE(:telefono, telefono),
                 email    = COALESCE(:email,    email),
                 notas    = COALESCE(:notas,    notas)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'       => $id,
            ':nombre'   => $body['nombre']   ?? null,
            ':apellido' => $body['apellido'] ?? null,
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
        response(["error" => "Metodo no permitido"], 405);
}