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

$data        = json_decode(file_get_contents('php://input'), true);
$id          = isset($data['id'])           ? (int)$data['id']           : 0;
$nombre      = trim($data['nombre']         ?? '');
$descripcion = trim($data['descripcion']    ?? '');
$coord_lat   = isset($data['coord_lat'])    ? (float)$data['coord_lat']  : null;
$coord_long  = isset($data['coord_long'])   ? (float)$data['coord_long'] : null;
$radio       = isset($data['radio_metros']) ? (int)$data['radio_metros'] : 200;

if (!$nombre) {
    http_response_code(400);
    echo json_encode(['error' => 'El nombre es requerido']);
    exit;
}
if ($coord_lat === null || $coord_long === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Las coordenadas son requeridas']);
    exit;
}
if ($coord_lat < -90 || $coord_lat > 90) {
    http_response_code(400);
    echo json_encode(['error' => 'Latitud inválida (debe estar entre -90 y 90)']);
    exit;
}
if ($coord_long < -180 || $coord_long > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Longitud inválida (debe estar entre -180 y 180)']);
    exit;
}
if ($radio < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'El radio debe ser mayor a 0']);
    exit;
}

$db = getDB();

if ($id === 0) {
    $stmt = $db->prepare(
        "INSERT INTO objetivo (nombre, descripcion, coord_lat, coord_long, radio_metros)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nombre, $descripcion ?: null, $coord_lat, $coord_long, $radio]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'accion' => 'creado']);
} else {
    $stmt = $db->prepare(
        "UPDATE objetivo
         SET nombre=?, descripcion=?, coord_lat=?, coord_long=?, radio_metros=?
         WHERE id_objetivo=?"
    );
    $stmt->execute([$nombre, $descripcion ?: null, $coord_lat, $coord_long, $radio, $id]);
    echo json_encode(['success' => true, 'id' => $id, 'accion' => 'actualizado']);
}
