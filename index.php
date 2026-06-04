<?php
session_start();
// Si ya tiene sesión, ir al dashboard
if (isset($_SESSION['vigilador_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>TDV — Ingresar</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-wrap">
    <div class="login-card">

        <div class="login-logo">
            <h1>TDV Seguridad</h1>
            <p>Sistema de Asistencias</p>
        </div>

        <div class="alert alert-danger" id="loginError" role="alert">
            <span>&#9888;</span>
            <span id="loginErrorMsg"></span>
        </div>

        <form id="loginForm" novalidate>
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario"
                       autocomplete="username"
                       placeholder="Ingrese su usuario"
                       required autofocus>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena"
                       autocomplete="current-password"
                       placeholder="Ingrese su contraseña"
                       required>
            </div>

            <button type="submit" class="btn btn-primary" id="btnLogin">
                Ingresar
            </button>
        </form>

        <p style="text-align:center;margin-top:1.2rem;font-size:.88rem;color:var(--text-muted);">
            ¿Primera vez? <a href="registro.php" style="color:var(--accent);">Solicitar cuenta</a>
        </p>
        <p class="app-footer" style="margin-top:.8rem;">
            Versión 1.0 &mdash; <?= date('Y') ?>
        </p>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn      = document.getElementById('btnLogin');
    const errDiv   = document.getElementById('loginError');
    const errMsg   = document.getElementById('loginErrorMsg');
    const usuario  = document.getElementById('usuario').value.trim();
    const clave    = document.getElementById('contrasena').value;

    errDiv.classList.remove('show');

    if (!usuario || !clave) {
        errMsg.textContent = 'Por favor complete todos los campos.';
        errDiv.classList.add('show');
        return;
    }

    btn.disabled    = true;
    btn.textContent = 'Verificando...';

    try {
        const res  = await fetch('api/login.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ usuario, contrasena: clave })
        });
        const data = await res.json();

        if (res.ok && data.success) {
            btn.textContent = 'Accediendo...';
            window.location.href = data.es_admin ? 'admin/dashboard.php' : 'dashboard.php';
        } else {
            errMsg.textContent = data.error || 'Error al iniciar sesión.';
            errDiv.classList.add('show');
            btn.disabled    = false;
            btn.textContent = 'Ingresar';
        }
    } catch (err) {
        errMsg.textContent = 'No se pudo conectar al servidor. Intente nuevamente.';
        errDiv.classList.add('show');
        btn.disabled    = false;
        btn.textContent = 'Ingresar';
    }
});
</script>

</body>
</html>
