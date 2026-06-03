-- ============================================================
-- Sistema de Asistencias TDV - Esquema de base de datos
-- ============================================================

CREATE DATABASE IF NOT EXISTS tdv_asistencias
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tdv_asistencias;

-- Objetivos / Puestos de guardia
CREATE TABLE objetivo (
    id_objetivo  INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    descripcion  TEXT,
    coord_lat    DECIMAL(10, 8) NOT NULL,
    coord_long   DECIMAL(11, 8) NOT NULL,
    radio_metros INT NOT NULL DEFAULT 200,
    hora_entrada TIME NOT NULL,
    hora_salida  TIME NOT NULL
);

-- Vigiladores
CREATE TABLE vigiladores (
    id_vigilador INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    apellido     VARCHAR(100) NOT NULL,
    dni          VARCHAR(20)  NOT NULL UNIQUE,
    telefono     VARCHAR(20),
    email        VARCHAR(150),
    usuario      VARCHAR(50)  NOT NULL UNIQUE,
    contrasena   VARCHAR(255) NOT NULL,  -- Siempre almacenar hash bcrypt
    objetivo_id  INT,
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (objetivo_id) REFERENCES objetivo(id_objetivo)
);

-- Tipos de novedad
CREATE TABLE tipo_novedad (
    id_tipo     INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion TEXT
);

-- Novedades / Registro de asistencias e incidentes
CREATE TABLE novedades (
    id_novedad     INT AUTO_INCREMENT PRIMARY KEY,
    fecha          DATE         NOT NULL,
    hora           TIME         NOT NULL,
    tipo           INT          NOT NULL,
    observaciones  TEXT,
    vigilador_id   INT          NOT NULL,
    ip_dispositivo VARCHAR(45)  NOT NULL,
    coord_lat      DECIMAL(10, 8),
    coord_long     DECIMAL(11, 8),
    FOREIGN KEY (tipo)         REFERENCES tipo_novedad(id_tipo),
    FOREIGN KEY (vigilador_id) REFERENCES vigiladores(id_vigilador)
);

-- ============================================================
-- Datos de ejemplo
-- ============================================================

INSERT INTO tipo_novedad (nombre, descripcion) VALUES
    ('Entrada',   'Registro de inicio de turno'),
    ('Salida',    'Registro de fin de turno'),
    ('Novedad',   'Situación especial durante el turno'),
    ('Incidente', 'Reporte de incidente o irregularidad');

INSERT INTO objetivo (nombre, descripcion, coord_lat, coord_long, radio_metros, hora_entrada, hora_salida) VALUES
    ('Sede Central', 'Edificio principal de la empresa', -34.60376, -58.38162, 300, '08:00:00', '20:00:00'),
    ('Planta Norte', 'Planta de producción zona norte',  -34.57000, -58.45000, 200, '06:00:00', '18:00:00');

-- Contrasena de muestra: "test123"  (generada con password_hash)
INSERT INTO vigiladores (nombre, apellido, dni, telefono, email, usuario, contrasena, objetivo_id) VALUES
    ('Juan',   'Pérez',  '30111222', '1144455566', 'juan.perez@tdv.com',
     'jperez',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
    ('María',  'García', '28999888', '1155566677', 'maria.garcia@tdv.com',
     'mgarcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- NOTA: el hash de arriba corresponde a la contraseña "password"
-- Para generar hashes propios usá: echo password_hash('tuClave', PASSWORD_DEFAULT);
