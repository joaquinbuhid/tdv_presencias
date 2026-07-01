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

$data      = json_decode(file_get_contents('php://input'), true);
$id        = isset($data['id'])        ? (int)$data['id']        : 0;
$nombre    = trim($data['nombre']      ?? '');
$apellido  = trim($data['apellido']    ?? '');
$dni       = trim($data['dni']         ?? '');
$telefono  = trim($data['telefono']    ?? '');
$email     = trim($data['email']       ?? '');
$usuario   = trim($data['usuario']     ?? '');
$contrasena = $data['contrasena']      ?? '';

if (!$nombre || !$apellido || !$dni || !$usuario) {
    http_response_code(400);
    echo json_encode(['error' => 'Nombre, apellido, DNI y usuario son requeridos']);
    exit;
}

// En creación, la contraseña es obligatoria
if ($id === 0 && !$contrasena) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña es requerida al crear un supervisor']);
    exit;
}

$db = getDB();

// Verificar DNI único
$stmt = $db->prepare("SELECT id_supervisor FROM supervisores WHERE dni = ? AND id_supervisor != ?");
$stmt->execute([$dni, $id]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => "El DNI $dni ya está registrado"]);
    exit;
}

// Verificar usuario único
$stmt = $db->prepare("SELECT id_supervisor FROM supervisores WHERE usuario = ? AND id_supervisor != ?");
$stmt->execute([$usuario, $id]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => "El usuario '$usuario' ya está en uso"]);
    exit;
}

if ($id === 0) {
    $hash = password_hash($contrasena, PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        "INSERT INTO supervisores (nombre, apellido, dni, telefono, email, usuario, contrasena, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $usuario, $hash]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'accion' => 'creado']);
} else {
    if ($contrasena) {
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            "UPDATE supervisores SET nombre=?, apellido=?, dni=?, telefono=?, email=?, usuario=?, contrasena=?
             WHERE id_supervisor=?"
        );
        $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $usuario, $hash, $id]);
    } else {
        $stmt = $db->prepare(
            "UPDATE supervisores SET nombre=?, apellido=?, dni=?, telefono=?, email=?, usuario=?
             WHERE id_supervisor=?"
        );
        $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $usuario, $id]);
    }
    echo json_encode(['success' => true, 'id' => $id, 'accion' => 'actualizado']);
}
