<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if (empty($_SESSION['es_admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db   = getDB();
$stmt = $db->query(
    "SELECT v.id_vigilador, v.nombre, v.apellido, v.dni, v.telefono, v.email,
            v.usuario, v.activo, v.pendiente, v.objetivo_id,
            v.hora_entrada, v.hora_salida,
            o.nombre AS objetivo_nombre
     FROM vigiladores v
     LEFT JOIN objetivo o ON v.objetivo_id = o.id_objetivo
     WHERE v.es_admin = 0
     ORDER BY v.pendiente DESC, v.apellido, v.nombre"
);

echo json_encode($stmt->fetchAll());
