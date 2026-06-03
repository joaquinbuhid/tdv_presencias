-- ============================================================
-- TDV v3 - Mover hora_entrada / hora_salida de objetivo a vigiladores
-- ============================================================

USE tdv_asistencias;

-- 1. Agregar las columnas en vigiladores
ALTER TABLE vigiladores
    ADD COLUMN hora_entrada TIME NULL AFTER objetivo_id,
    ADD COLUMN hora_salida  TIME NULL AFTER hora_entrada;

-- 2. Quitar las columnas de objetivo
ALTER TABLE objetivo
    DROP COLUMN hora_entrada,
    DROP COLUMN hora_salida;
