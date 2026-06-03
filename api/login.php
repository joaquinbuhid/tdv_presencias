<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$usuario  = trim($data['usuario']   ?? '');
$clave    = $data['contrasena'] ?? '';

if ($usuario === '' || $clave === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare(
    "SELECT v.id_vigilador, v.nombre, v.apellido, v.contrasena, v.activo,
            o.id_objetivo, o.nombre AS objetivo_nombre,
            o.coord_lat, o.coord_long, o.radio_metros,
            o.hora_entrada, o.hora_salida
     FROM vigiladores v
     LEFT JOIN objetivo o ON v.objetivo_id = o.id_objetivo
     WHERE v.usuario = ?"
);
$stmt->execute([$usuario]);
$row = $stmt->fetch();

if (!$row || !password_verify($clave, $row['contrasena'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
    exit;
}

if (!$row['activo']) {
    http_response_code(403);
    echo json_encode(['error' => 'Cuenta desactivada. Contacte al administrador']);
    exit;
}

if (!$row['id_objetivo']) {
    http_response_code(403);
    echo json_encode(['error' => 'No tiene un objetivo asignado. Contacte al administrador']);
    exit;
}

// Guardar sesión
$_SESSION['vigilador_id']     = $row['id_vigilador'];
$_SESSION['nombre_completo']  = $row['nombre'] . ' ' . $row['apellido'];
$_SESSION['objetivo_nombre']  = $row['objetivo_nombre'];

echo json_encode([
    'success'         => true,
    'nombre'          => $_SESSION['nombre_completo'],
    'objetivo'        => $row['objetivo_nombre'],
]);
