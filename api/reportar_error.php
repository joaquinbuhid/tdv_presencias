<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['vigilador_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data          = json_decode(file_get_contents('php://input'), true);
$accion        = trim($data['accion']        ?? '');
$mensaje_error = trim($data['mensaje_error'] ?? '');
$descripcion   = trim($data['descripcion']   ?? '');
$user_agent    = substr(trim($data['user_agent'] ?? ''), 0, 350);

if (!$descripcion) {
    http_response_code(400);
    echo json_encode(['error' => 'La descripción del problema es requerida']);
    exit;
}

$db = getDB();
$stmt = $db->prepare(
    "INSERT INTO reportes_error
        (vigilador_id, fecha, hora, accion, mensaje_error, descripcion, ip_dispositivo, user_agent)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([
    (int)$_SESSION['vigilador_id'],
    date('Y-m-d'),
    date('H:i:s'),
    $accion        ?: null,
    $mensaje_error ?: null,
    $descripcion,
    getClientIP(),
    $user_agent    ?: null,
]);

echo json_encode(['success' => true, 'mensaje' => 'Reporte enviado. El equipo técnico lo revisará a la brevedad.']);
