<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if (empty($_SESSION['es_admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$id     = isset($data['id'])     ? (int)$data['id']    : 0;
$accion = trim($data['accion']   ?? '');

if (!$id || !in_array($accion, ['activar', 'desactivar'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

$db     = getDB();
$estado = $accion === 'activar' ? 1 : 0;

$stmt = $db->prepare("UPDATE supervisores SET estado=? WHERE id_supervisor=?");
$stmt->execute([$estado, $id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Supervisor no encontrado']);
    exit;
}

echo json_encode(['success' => true, 'mensaje' => 'Supervisor ' . ($estado ? 'activado' : 'desactivado')]);
