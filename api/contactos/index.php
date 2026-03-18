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
            $stmt = $db->prepare("SELECT * FROM contacto WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch();
            $data ? response($data) : response(["error" => "Contacto no encontrado"], 404);
        } else {
            $stmt = $db->query("SELECT * FROM contacto ORDER BY nombre ASC");
            response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $body = getBody();
        if (empty($body['nombre']))
            response(["error" => "El nombre es obligatorio"], 400);

        $stmt = $db->prepare(
            "INSERT INTO contacto (usuario_id, categoria_id, nombre, apellido, telefono, telefono_alt, email, empresa, cargo, direccion, fecha_nacimiento, notas)
             VALUES (1, :categoria_id, :nombre, :apellido, :telefono, :telefono_alt, :email, :empresa, :cargo, :direccion, :fecha_nacimiento, :notas)
             RETURNING *"
        );
        $stmt->execute([
            ':categoria_id'    => $body['categoria_id']    ?? null,
            ':nombre'          => $body['nombre'],
            ':apellido'        => $body['apellido']        ?? null,
            ':telefono'        => $body['telefono']        ?? null,
            ':telefono_alt'    => $body['telefono_alt']    ?? null,
            ':email'           => $body['email']           ?? null,
            ':empresa'         => $body['empresa']         ?? null,
            ':cargo'           => $body['cargo']           ?? null,
            ':direccion'       => $body['direccion']       ?? null,
            ':fecha_nacimiento'=> $body['fecha_nacimiento']?? null,
            ':notas'           => $body['notas']           ?? null,
        ]);
        response($stmt->fetch(), 201);
        break;

    case 'PUT':
        if (!$id) response(["error" => "ID requerido"], 400);
        $body = getBody();

        $stmt = $db->prepare(
            "UPDATE contacto
             SET categoria_id    = COALESCE(:categoria_id,              categoria_id),
                 nombre          = COALESCE(:nombre,                    nombre),
                 apellido        = COALESCE(:apellido,                  apellido),
                 telefono        = COALESCE(:telefono,                  telefono),
                 telefono_alt    = COALESCE(:telefono_alt,              telefono_alt),
                 email           = COALESCE(:email,                     email),
                 empresa         = COALESCE(:empresa,                   empresa),
                 cargo           = COALESCE(:cargo,                     cargo),
                 direccion       = COALESCE(:direccion,                 direccion),
                 fecha_nacimiento= COALESCE(:fecha_nacimiento::date,    fecha_nacimiento),
                 notas           = COALESCE(:notas,                     notas)
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            ':id'              => $id,
            ':categoria_id'    => $body['categoria_id']    ?? null,
            ':nombre'          => $body['nombre']          ?? null,
            ':apellido'        => $body['apellido']        ?? null,
            ':telefono'        => $body['telefono']        ?? null,
            ':telefono_alt'    => $body['telefono_alt']    ?? null,
            ':email'           => $body['email']           ?? null,
            ':empresa'         => $body['empresa']         ?? null,
            ':cargo'           => $body['cargo']           ?? null,
            ':direccion'       => $body['direccion']       ?? null,
            ':fecha_nacimiento'=> $body['fecha_nacimiento']?? null,
            ':notas'           => $body['notas']           ?? null,
        ]);
        $data = $stmt->fetch();
        $data ? response($data) : response(["error" => "Contacto no encontrado"], 404);
        break;

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