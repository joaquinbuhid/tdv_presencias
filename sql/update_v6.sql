-- v6: agregar credenciales de acceso a supervisores
ALTER TABLE supervisores
    ADD COLUMN usuario    VARCHAR(60)  NULL UNIQUE AFTER email,
    ADD COLUMN contrasena VARCHAR(255) NULL          AFTER usuario;
