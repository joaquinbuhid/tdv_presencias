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

$data         = json_decode(file_get_contents('php://input'), true);
$tipo_id      = isset($data['tipo_id'])      ? (int)$data['tipo_id']      : 0;
$observaciones = isset($data['observaciones']) ? trim($data['observaciones']) : '';
$lat          = isset($data['lat'])          ? (float)$data['lat']        : null;
$lng          = isset($data['lng'])          ? (float)$data['lng']        : null;

if (!$tipo_id || $lat === null || $lng === null || $lat == 0 || $lng == 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos: se requiere tipo, latitud y longitud']);
    exit;
}

$ip          = getClientIP();
$vigilador_id = (int)$_SESSION['vigilador_id'];
$fecha       = date('Y-m-d');
$hora        = date('H:i:s');

$db = getDB();

// Verificar que el tipo de novedad existe
$stmt = $db->prepare("SELECT id_tipo, nombre FROM tipo_novedad WHERE id_tipo = ?");
$stmt->execute([$tipo_id]);
$tipo = $stmt->fetch();
if (!$tipo) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de novedad no válido']);
    exit;
}

// --- Verificación: el vigilador no haya registrado este tipo hoy ---
$stmt = $db->prepare(
    "SELECT id_novedad FROM novedades
     WHERE vigilador_id = ? AND tipo = ? AND fecha = ?"
);
$stmt->execute([$vigilador_id, $tipo_id, $fecha]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode([
        'error' => "Ya registró \"{$tipo['nombre']}\" el día de hoy."
    ]);
    exit;
}

// --- Verificación 2: ubicación dentro del radio del objetivo ---
$stmt = $db->prepare(
    "SELECT o.coord_lat, o.coord_long, o.radio_metros, o.nombre AS objetivo_nombre
     FROM vigiladores v
     JOIN objetivo o ON v.objetivo_id = o.id_objetivo
     WHERE v.id_vigilador = ?"
);
$stmt->execute([$vigilador_id]);
$objetivo = $stmt->fetch();

if (!$objetivo) {
    http_response_code(400);
    echo json_encode(['error' => 'No tiene un objetivo asignado. Contacte al administrador']);
    exit;
}

$distancia = haversineMetros($lat, $lng, (float)$objetivo['coord_lat'], (float)$objetivo['coord_long']);
$radio     = (int)$objetivo['radio_metros'];

if ($distancia > $radio) {
    http_response_code(403);
    echo json_encode([
        'error'     => "Fuera del área permitida. Se encuentra a " . round($distancia) . " m del objetivo \"{$objetivo['objetivo_nombre']}\" (radio máximo: {$radio} m).",
        'distancia' => round($distancia),
        'radio'     => $radio,
    ]);
    exit;
}

// --- Registrar la novedad ---
$stmt = $db->prepare(
    "INSERT INTO novedades (fecha, hora, tipo, observaciones, vigilador_id, ip_dispositivo, coord_lat, coord_long)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$fecha, $hora, $tipo_id, $observaciones, $vigilador_id, $ip, $lat, $lng]);

echo json_encode([
    'success'   => true,
    'mensaje'   => "{$tipo['nombre']} registrada correctamente.",
    'fecha'     => $fecha,
    'hora'      => substr($hora, 0, 5),
    'tipo'      => $tipo['nombre'],
    'distancia' => round($distancia),
]);
