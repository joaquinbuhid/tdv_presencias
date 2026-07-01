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
    "SELECT s.id_supervisor, s.nombre, s.apellido, s.dni, s.telefono, s.email,
            s.usuario, s.estado,
            COUNT(o.id_objetivo) AS objetivos_asignados
     FROM supervisores s
     LEFT JOIN objetivo o ON o.supervisor_id = s.id_supervisor
     GROUP BY s.id_supervisor
     ORDER BY s.apellido, s.nombre"
);
echo json_encode($stmt->fetchAll());
