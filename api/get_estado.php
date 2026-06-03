<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['vigilador_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare(
    "SELECT n.id_novedad, n.fecha, n.hora, t.nombre AS tipo_nombre,
            n.observaciones, n.ip_dispositivo
     FROM novedades n
     JOIN tipo_novedad t ON n.tipo = t.id_tipo
     WHERE n.vigilador_id = ? AND n.fecha = CURDATE()
     ORDER BY n.hora ASC"
);
$stmt->execute([$_SESSION['vigilador_id']]);
echo json_encode($stmt->fetchAll());
