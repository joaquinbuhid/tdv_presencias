<?php
// Configuración de la base de datos
define('DB_HOST', 'srv847.hstgr.io');
define('DB_USER', 'u331324140_presencias');
define('DB_PASS', '#Malbor0');
define('DB_NAME', 'u331324140_presencias');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            die(json_encode(['error' => 'Error de conexión a la base de datos']));
        }
    }
    return $pdo;
}

function getClientIP(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Fórmula de Haversine: distancia en metros entre dos coordenadas
function haversineMetros(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R      = 6371000;
    $phi1   = deg2rad($lat1);
    $phi2   = deg2rad($lat2);
    $dphi   = deg2rad($lat2 - $lat1);
    $dlambda = deg2rad($lon2 - $lon1);
    $a = sin($dphi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dlambda / 2) ** 2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
