<?php
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$nombre    = trim($data['nombre']    ?? '');
$apellido  = trim($data['apellido']  ?? '');
$dni       = trim($data['dni']       ?? '');
$telefono  = trim($data['telefono']  ?? '');
$email     = trim($data['email']     ?? '');
$usuario   = trim($data['usuario']   ?? '');
$contrasena = $data['contrasena']    ?? '';

if (!$nombre || !$apellido || !$dni || !$usuario || !$contrasena) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos obligatorios']);
    exit;
}

if (strlen($contrasena) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

$db = getDB();

// Verificar unicidad de usuario y DNI
$stmt = $db->prepare("SELECT id_vigilador FROM vigiladores WHERE usuario = ?");
$stmt->execute([$usuario]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => "El usuario \"$usuario\" ya está en uso"]);
    exit;
}

$stmt = $db->prepare("SELECT id_vigilador FROM vigiladores WHERE dni = ?");
$stmt->execute([$dni]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => "El DNI $dni ya está registrado"]);
    exit;
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);

$stmt = $db->prepare(
    "INSERT INTO vigiladores (nombre, apellido, dni, telefono, email, usuario, contrasena, activo, pendiente)
     VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1)"
);
$stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $usuario, $hash]);

echo json_encode(['success' => true]);
