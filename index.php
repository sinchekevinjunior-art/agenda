<?php
require_once 'config/helpers.php';
setHeaders();

response([
    "api"     => "Agenda API",
    "version" => "1.0",
    "endpoints" => [
        "contactos" => "/api/contactos/",
        "eventos"   => "/api/eventos/",
        "tareas"    => "/api/tareas/",
    ]
]);
