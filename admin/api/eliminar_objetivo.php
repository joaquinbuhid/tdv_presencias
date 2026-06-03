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

$data = json_decode(file_get_contents('php://input'), true);
$id   = isset($data['id']) ? (int)$data['id'] : 0;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$db = getDB();

// Verificar que no tenga vigiladores asignados
$stmt = $db->prepare("SELECT COUNT(*) FROM vigiladores WHERE objetivo_id = ?");
$stmt->execute([$id]);
$asignados = (int)$stmt->fetchColumn();

if ($asignados > 0) {
    http_response_code(409);
    echo json_encode([
        'error' => "No se puede eliminar: hay $asignados vigilador(es) asignado(s) a este objetivo. Reasignelos primero."
    ]);
    exit;
}

$stmt = $db->prepare("DELETE FROM objetivo WHERE id_objetivo = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Objetivo no encontrado']);
    exit;
}

echo json_encode(['success' => true, 'mensaje' => 'Objetivo eliminado correctamente']);
