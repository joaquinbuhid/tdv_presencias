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

$data       = json_decode(file_get_contents('php://input'), true);
$id         = isset($data['id'])        ? (int)$data['id']              : 0;
$nombre     = trim($data['nombre']      ?? '');
$apellido   = trim($data['apellido']    ?? '');
$dni        = trim($data['dni']         ?? '');
$telefono   = trim($data['telefono']    ?? '');
$email      = trim($data['email']       ?? '');
$usuario    = trim($data['usuario']     ?? '');
$contrasena = $data['contrasena']       ?? '';
$obj_id     = isset($data['objetivo_id']) && $data['objetivo_id'] !== ''
              ? (int)$data['objetivo_id'] : null;

if (!$nombre || !$apellido || !$dni || !$usuario) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos obligatorios']);
    exit;
}

if (!$id && !$contrasena) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña es requerida para nuevos vigiladores']);
    exit;
}

if ($contrasena && strlen($contrasena) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

$db = getDB();

// Verificar unicidad (excluir el registro actual en edición)
$stmt = $db->prepare("SELECT id_vigilador FROM vigiladores WHERE usuario = ? AND id_vigilador != ?");
$stmt->execute([$usuario, $id]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => "El usuario \"$usuario\" ya está en uso"]);
    exit;
}

$stmt = $db->prepare("SELECT id_vigilador FROM vigiladores WHERE dni = ? AND id_vigilador != ?");
$stmt->execute([$dni, $id]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => "El DNI $dni ya está registrado"]);
    exit;
}

if ($id === 0) {
    // CREAR
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    $stmt = $db->prepare(
        "INSERT INTO vigiladores (nombre, apellido, dni, telefono, email, usuario, contrasena, objetivo_id, activo, pendiente)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0)"
    );
    $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $usuario, $hash, $obj_id]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'accion' => 'creado']);
} else {
    // EDITAR
    if ($contrasena) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "UPDATE vigiladores
             SET nombre=?, apellido=?, dni=?, telefono=?, email=?, usuario=?, contrasena=?, objetivo_id=?
             WHERE id_vigilador=?"
        );
        $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $usuario, $hash, $obj_id, $id]);
    } else {
        $stmt = $db->prepare(
            "UPDATE vigiladores
             SET nombre=?, apellido=?, dni=?, telefono=?, email=?, usuario=?, objetivo_id=?
             WHERE id_vigilador=?"
        );
        $stmt->execute([$nombre, $apellido, $dni, $telefono ?: null, $email ?: null, $usuario, $obj_id, $id]);
    }
    echo json_encode(['success' => true, 'id' => $id, 'accion' => 'actualizado']);
}
