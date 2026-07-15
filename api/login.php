<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data    = json_decode(file_get_contents('php://input'), true);
$usuario = trim($data['usuario']    ?? '');
$clave   = $data['contrasena'] ?? '';

if ($usuario === '' || $clave === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos']);
    exit;
}

$db = getDB();

// ---- Intentar como vigilador ----
$stmt = $db->prepare(
    "SELECT v.*, o.nombre AS objetivo_nombre
     FROM vigiladores v
     LEFT JOIN objetivo o ON v.objetivo_id = o.id_objetivo
     WHERE v.usuario = ?"
);
$stmt->execute([$usuario]);
$row = $stmt->fetch();

if ($row) {
    // Usuario encontrado como vigilador: verificar contraseña
    if (!password_verify($clave, $row['contrasena'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
        exit;
    }
    if ($row['pendiente']) {
        http_response_code(403);
        echo json_encode(['error' => 'Tu cuenta está pendiente de aprobación. Contactá al administrador.']);
        exit;
    }
    if (!$row['activo']) {
        http_response_code(403);
        echo json_encode(['error' => 'Cuenta desactivada. Contactá al administrador.']);
        exit;
    }

    // Detectar si es supervisor (por columna tipo = 2 o es_admin = 2)
    $es_supervisor = false;
    if (isset($row['tipo']) && (int)$row['tipo'] === 2) {
        $es_supervisor = true;
    } elseif (isset($row['es_admin']) && (int)$row['es_admin'] === 2) {
        $es_supervisor = true;
    }

    if ($es_supervisor) {
        // Buscar el id_supervisor correspondiente en la tabla supervisores usando el DNI
        $supId = $row['id_vigilador'];
        try {
            $stmtSup = $db->prepare("SELECT id_supervisor FROM supervisores WHERE dni = ?");
            $stmtSup->execute([$row['dni']]);
            $supRow = $stmtSup->fetch();
            if ($supRow) {
                $supId = (int)$supRow['id_supervisor'];
            }
        } catch (Exception $e) {
            // Ignorar y usar id_vigilador como fallback
        }

        $_SESSION['supervisor_id']   = $supId;
        $_SESSION['nombre_completo'] = $row['nombre'] . ' ' . $row['apellido'];
        $_SESSION['es_supervisor']   = true;

        echo json_encode([
            'success'      => true,
            'es_admin'     => false,
            'es_supervisor'=> true,
            'nombre'       => $_SESSION['nombre_completo'],
        ]);
        exit;
    }

    if ($row['es_admin']) {
        $_SESSION['vigilador_id']    = $row['id_vigilador'];
        $_SESSION['nombre_completo'] = $row['nombre'] . ' ' . $row['apellido'];
        $_SESSION['es_admin']        = true;
        $_SESSION['objetivo_nombre'] = $row['objetivo_nombre'];

        echo json_encode([
            'success'      => true,
            'es_admin'     => true,
            'es_supervisor'=> false,
            'nombre'       => $_SESSION['nombre_completo'],
            'objetivo'     => $row['objetivo_nombre'],
        ]);
        exit;
    }

    if (!$row['id_objetivo']) {
        http_response_code(403);
        echo json_encode(['error' => 'No tiene un objetivo asignado. Contactá al administrador.']);
        exit;
    }

    $_SESSION['vigilador_id']    = $row['id_vigilador'];
    $_SESSION['nombre_completo'] = $row['nombre'] . ' ' . $row['apellido'];
    $_SESSION['es_admin']        = false;
    $_SESSION['objetivo_nombre'] = $row['objetivo_nombre'];

    echo json_encode([
        'success'      => true,
        'es_admin'     => false,
        'es_supervisor'=> false,
        'nombre'       => $_SESSION['nombre_completo'],
        'objetivo'     => $row['objetivo_nombre'],
    ]);
    exit;
}

// ---- Intentar como supervisor directo (si la tabla tiene credenciales) ----
try {
    $stmt = $db->prepare(
        "SELECT id_supervisor, nombre, apellido, contrasena, estado
         FROM supervisores
         WHERE usuario = ?"
    );
    $stmt->execute([$usuario]);
    $sup = $stmt->fetch();

    if ($sup && password_verify($clave, $sup['contrasena'])) {
        if ($sup['estado'] != 1) {
            http_response_code(403);
            echo json_encode(['error' => 'Cuenta de supervisor desactivada. Contactá al administrador.']);
            exit;
        }

        $_SESSION['supervisor_id']   = $sup['id_supervisor'];
        $_SESSION['nombre_completo'] = $sup['nombre'] . ' ' . $sup['apellido'];
        $_SESSION['es_supervisor']   = true;

        echo json_encode([
            'success'      => true,
            'es_admin'     => false,
            'es_supervisor'=> true,
            'nombre'       => $_SESSION['nombre_completo'],
        ]);
        exit;
    }
} catch (Exception $e) {
    // Si la tabla supervisores no contiene las columnas de login, ignorar error
}

// ---- No encontrado en ninguna tabla ----
http_response_code(401);
echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
