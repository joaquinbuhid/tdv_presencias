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

$data     = json_decode(file_get_contents('php://input'), true);
$id       = isset($data['id'])       ? (int)$data['id']       : 0;
$nombre   = trim($data['nombre']     ?? '');
$apellido = trim($data['apellido']   ?? '');
$dni      = trim($data['dni']        ?? '');
$telefono = trim($data['telefono']   ?? '');
$email    = trim($data['email']      ?? '');

if (!$nombre || !$apellido || !$dni) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre, apellido y DNI son requeridos']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("SELECT id_supervisor FROM supervisores WHERE dni = ? AND id_supervisor != ?");
$stmt->execute([$dni, $id]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => "El DNI $dni ya está registrado"]);
    exit;
}

if ($id === 0) {
    $stmt = $db->prepare(
        "INSERT INTO supervisores (nombre, apellido, dni, telefono, email, estado)
         VALUES (?, ?, ?, ?, ?, 1)"
    );
    $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'accion' => 'creado']);
} else {
    $stmt = $db->prepare(
        "UPDATE supervisores SET nombre=?, apellido=?, dni=?, telefono=?, email=?
         WHERE id_supervisor=?"
    );
    $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $id]);
    echo json_encode(['success' => true, 'id' => $id, 'accion' => 'actualizado']);
}
