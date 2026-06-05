-- ============================================================
-- TDV v5 - Tabla de reportes de error de vigiladores
-- ============================================================

USE tdv_asistencias;

CREATE TABLE reportes_error (
    id_reporte     INT AUTO_INCREMENT PRIMARY KEY,
    vigilador_id   INT          NOT NULL,
    fecha          DATE         NOT NULL,
    hora           TIME         NOT NULL,
    accion         VARCHAR(150),                  -- qué estaba intentando hacer
    mensaje_error  TEXT,                          -- mensaje de error que vio en pantalla
    descripcion    TEXT         NOT NULL,         -- descripción libre del problema
    ip_dispositivo VARCHAR(45),
    user_agent     VARCHAR(350),
    estado         ENUM('pendiente','revisado','resuelto') NOT NULL DEFAULT 'pendiente',
    notas_admin    TEXT,
    fecha_revision DATETIME,
    FOREIGN KEY (vigilador_id) REFERENCES vigiladores(id_vigilador)
);
