-- ============================================================
-- TDV v2 - Agregar columnas para admin y auto-registro
-- Ejecutar sobre la base ya existente
-- ============================================================

USE tdv_asistencias;

-- Columna para distinguir admins
ALTER TABLE vigiladores
    ADD COLUMN es_admin  TINYINT(1) NOT NULL DEFAULT 0 AFTER activo,
    ADD COLUMN pendiente TINYINT(1) NOT NULL DEFAULT 0 AFTER es_admin;

-- Agregar también radio_metros al objetivo si no existe
ALTER TABLE objetivo
    ADD COLUMN IF NOT EXISTS radio_metros INT NOT NULL DEFAULT 200 AFTER coord_long;

-- Agregar coordenadas al registro de novedades si no existen
ALTER TABLE novedades
    ADD COLUMN IF NOT EXISTS coord_lat  DECIMAL(10,8) AFTER ip_dispositivo,
    ADD COLUMN IF NOT EXISTS coord_long DECIMAL(11,8) AFTER coord_lat;

-- Usuario administrador del sistema
-- Contraseña: admin2024  (cambiarla después del primer login)
INSERT INTO vigiladores (nombre, apellido, dni, usuario, contrasena, activo, es_admin, pendiente)
VALUES ('Administrador', 'Sistema', '00000000',
        'admin',
        '$2y$10$YourHashHere_CHANGE_ME',   -- reemplazar con: password_hash('admin2024', PASSWORD_DEFAULT)
        1, 1, 0);

-- NOTA: para generar el hash correcto ejecutar en PHP:
--   echo password_hash('admin2024', PASSWORD_DEFAULT);
-- y actualizar con:
--   UPDATE vigiladores SET contrasena='HASH' WHERE usuario='admin';
