-- ============================================================
-- TDV v4 - Tabla supervisores + FK en objetivo
-- ============================================================

USE tdv_asistencias;

CREATE TABLE supervisores (
    id_supervisor INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    apellido      VARCHAR(100) NOT NULL,
    dni           VARCHAR(20)  NOT NULL UNIQUE,
    telefono      VARCHAR(20),
    email         VARCHAR(150),
    estado        TINYINT(1)   NOT NULL DEFAULT 1
);

ALTER TABLE objetivo
    ADD COLUMN supervisor_id INT NULL AFTER radio_metros,
    ADD CONSTRAINT fk_obj_supervisor
        FOREIGN KEY (supervisor_id) REFERENCES supervisores(id_supervisor)
        ON DELETE SET NULL;
