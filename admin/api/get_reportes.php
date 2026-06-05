<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if (empty($_SESSION['es_admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db     = getDB();
$estado = $_GET['estado'] ?? '';
$validos = ['pendiente', 'revisado', 'resuelto'];

if ($estado && in_array($estado, $validos, true)) {
    $stmt = $db->prepare(
        "SELECT r.*, CONCAT(v.nombre,' ',v.apellido) AS vigilador_nombre, v.dni
         FROM reportes_error r
         JOIN vigiladores v ON r.vigilador_id = v.id_vigilador
         WHERE r.estado = ?
         ORDER BY r.fecha DESC, r.hora DESC"
    );
    $stmt->execute([$estado]);
} else {
    $stmt = $db->query(
        "SELECT r.*, CONCAT(v.nombre,' ',v.apellido) AS vigilador_nombre, v.dni
         FROM reportes_error r
         JOIN vigiladores v ON r.vigilador_id = v.id_vigilador
         ORDER BY FIELD(r.estado,'pendiente','revisado','resuelto'), r.fecha DESC, r.hora DESC"
    );
}

echo json_encode($stmt->fetchAll());
