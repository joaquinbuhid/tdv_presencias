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

if (!$id || !in_array($accion, ['aprobar', 'activar', 'desactivar'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

$db = getDB();

// Verificar que el vigilador existe y no es admin
$stmt = $db->prepare("SELECT id_vigilador FROM vigiladores WHERE id_vigilador = ? AND es_admin = 0");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Vigilador no encontrado']);
    exit;
}

switch ($accion) {
    case 'aprobar':
    case 'activar':
        $db->prepare("UPDATE vigiladores SET activo=1, pendiente=0 WHERE id_vigilador=?")->execute([$id]);
        echo json_encode(['success' => true, 'mensaje' => 'Vigilador activado']);
        break;
    case 'desactivar':
        $db->prepare("UPDATE vigiladores SET activo=0, pendiente=0 WHERE id_vigilador=?")->execute([$id]);
        echo json_encode(['success' => true, 'mensaje' => 'Vigilador desactivado']);
        break;
}
