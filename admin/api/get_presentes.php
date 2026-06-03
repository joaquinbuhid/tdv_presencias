<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if (empty($_SESSION['es_admin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db   = getDB();
$stmt = $db->query(
    "SELECT
        v.id_vigilador, v.nombre, v.apellido,
        o.id_objetivo, o.nombre  AS objetivo_nombre,
        v.hora_entrada           AS turno_entrada,
        v.hora_salida            AS turno_salida,
        MAX(CASE WHEN tn.nombre = 'Entrada' THEN n.hora END) AS hora_entrada_hoy,
        MAX(CASE WHEN tn.nombre = 'Salida'  THEN n.hora END) AS hora_salida_hoy,
        MAX(n.hora)              AS ultima_actividad,
        COUNT(n.id_novedad)      AS total_novedades
     FROM vigiladores v
     LEFT JOIN objetivo       o  ON v.objetivo_id   = o.id_objetivo
     LEFT JOIN novedades      n  ON v.id_vigilador  = n.vigilador_id AND n.fecha = CURDATE()
     LEFT JOIN tipo_novedad   tn ON n.tipo           = tn.id_tipo
     WHERE v.activo = 1 AND v.es_admin = 0 AND v.pendiente = 0
     GROUP BY v.id_vigilador
     ORDER BY o.nombre, v.apellido"
);

$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
    if (!$r['id_objetivo']) {
        $r['estado'] = 'sin-objetivo';
    } elseif ($r['hora_entrada_hoy'] && $r['hora_salida_hoy']) {
        $r['estado'] = 'completado';
    } elseif ($r['hora_entrada_hoy']) {
        $r['estado'] = 'presente';
    } else {
        $r['estado'] = 'ausente';
    }
    // Formatear horas HH:MM
    foreach (['hora_entrada_hoy','hora_salida_hoy','ultima_actividad','turno_entrada','turno_salida'] as $campo) {
        if ($r[$campo]) $r[$campo] = substr($r[$campo], 0, 5);
    }
}

echo json_encode($rows);
