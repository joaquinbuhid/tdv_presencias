<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if (empty($_SESSION['supervisor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$supId      = (int)$_SESSION['supervisor_id'];
$objetivoId = isset($_GET['objetivo_id']) ? (int)$_GET['objetivo_id'] : 0;

$db = getDB();

// Si filtra por objetivo, verificar que le pertenece al supervisor
if ($objetivoId) {
    $chk = $db->prepare("SELECT id_objetivo FROM objetivo WHERE id_objetivo = ? AND supervisor_id = ?");
    $chk->execute([$objetivoId, $supId]);
    if (!$chk->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Objetivo no autorizado']);
        exit;
    }
    $where = "AND v.objetivo_id = $objetivoId";
} else {
    $where = "AND o.supervisor_id = $supId";
}

$sql = "SELECT
            v.id_vigilador, v.nombre, v.apellido,
            v.hora_entrada AS turno_entrada,
            v.hora_salida  AS turno_salida,
            o.id_objetivo, o.nombre AS objetivo_nombre,
            MAX(CASE WHEN tn.nombre = 'Entrada' THEN n.hora END) AS hora_entrada_hoy,
            MAX(CASE WHEN tn.nombre = 'Salida'  THEN n.hora END) AS hora_salida_hoy
        FROM vigiladores v
        LEFT JOIN objetivo     o  ON v.objetivo_id  = o.id_objetivo
        LEFT JOIN novedades    n  ON v.id_vigilador = n.vigilador_id AND n.fecha = CURDATE()
        LEFT JOIN tipo_novedad tn ON n.tipo          = tn.id_tipo
        WHERE v.activo = 1 AND v.es_admin = 0 AND v.pendiente = 0
        $where
        GROUP BY v.id_vigilador
        ORDER BY o.nombre, v.apellido";

$stmt = $db->query($sql);
$rows = $stmt->fetchAll();
$ahora = date('H:i');

foreach ($rows as &$r) {
    $te = $r['turno_entrada'] ? substr($r['turno_entrada'], 0, 5) : null;
    $ts = $r['turno_salida']  ? substr($r['turno_salida'],  0, 5) : null;

    if ($r['hora_entrada_hoy'] && $r['hora_salida_hoy']) {
        $r['estado'] = 'completado';
    } elseif ($r['hora_entrada_hoy']) {
        $r['estado'] = ($ts && $ahora > $ts) ? 'sin-salida' : 'presente';
    } else {
        $r['estado'] = ($ts && $ahora > $ts) ? 'ausente' : 'por-iniciar';
    }

    foreach (['hora_entrada_hoy','hora_salida_hoy','turno_entrada','turno_salida'] as $c) {
        if ($r[$c]) $r[$c] = substr($r[$c], 0, 5);
    }
}

echo json_encode($rows);
