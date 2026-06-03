<?php
session_start();
if (!isset($_SESSION['vigilador_id'])) {
    header('Location: index.php');
    exit;
}
if (!empty($_SESSION['es_admin'])) {
    header('Location: admin/dashboard.php');
    exit;
}

require_once 'config/db.php';

// Cargar datos del vigilador y su objetivo
$db   = getDB();
$stmt = $db->prepare(
    "SELECT v.nombre, v.apellido, v.hora_entrada, v.hora_salida,
            o.nombre AS obj_nombre, o.radio_metros
     FROM vigiladores v
     LEFT JOIN objetivo o ON v.objetivo_id = o.id_objetivo
     WHERE v.id_vigilador = ?"
);
$stmt->execute([$_SESSION['vigilador_id']]);
$vigi = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>TDV — Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- HEADER -->
<header class="app-header">
    <div class="brand">
        <span>&#x1F6E1;</span> TDV Seguridad
    </div>
    <div class="header-user">
        <strong><?= htmlspecialchars($vigi['nombre'] . ' ' . $vigi['apellido']) ?></strong>
        <a href="api/logout.php" style="color:rgba(255,255,255,.75);font-size:.8rem;text-decoration:none;">
            Cerrar sesión
        </a>
    </div>
</header>

<!-- CONTENIDO -->
<main class="app-content">

    <!-- Reloj -->
    <div style="text-align:center;padding:.8rem 0 .2rem;">
        <div id="relojHora" style="
            font-size:2.8rem;font-weight:700;letter-spacing:.05em;
            color:var(--primary);font-variant-numeric:tabular-nums;line-height:1;">
            --:--:--
        </div>
        <div id="relojFecha" style="font-size:.82rem;color:var(--text-muted);margin-top:.2rem;"></div>
    </div>

    <!-- Objetivo asignado -->
    <div style="text-align:center; padding:.5rem 0 .2rem;">
        <span class="objetivo-badge">
            &#x1F4CD; <?= htmlspecialchars($vigi['obj_nombre'] ?? 'Sin objetivo') ?>
        </span>
        <div style="font-size:.8rem;color:var(--text-muted);margin-top:.3rem;">
            Turno: <?= substr($vigi['hora_entrada'],0,5) ?> hs &mdash; <?= substr($vigi['hora_salida'],0,5) ?> hs
        </div>
    </div>

    <!-- Estado del día -->
    <div class="card" id="cardEstado">
        <div class="card-title">&#x1F4CB; Registros de hoy</div>
        <div id="estadoContent">
            <div class="loc-status">
                <div class="spinner spinner-dark"></div>
                Cargando registros...
            </div>
        </div>
    </div>

    <!-- Formulario de registro -->
    <div class="card">
        <div class="card-title">&#x270F; Registrar novedad</div>

        <!-- Mensajes -->
        <div class="alert alert-danger"  id="regError"   role="alert"><span>&#9888;</span><span id="regErrorMsg"></span></div>
        <div class="alert alert-success" id="regSuccess" role="alert"><span>&#9989;</span><span id="regSuccessMsg"></span></div>

        <form id="formNovedad" novalidate>

            <div class="form-group">
                <label for="tipoNovedad">Tipo de novedad</label>
                <select id="tipoNovedad" required>
                    <option value="">— Seleccione —</option>
                </select>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones <span style="font-weight:400;color:var(--text-muted)">(opcional)</span></label>
                <textarea id="observaciones" placeholder="Ingrese observaciones..."></textarea>
            </div>

            <!-- Estado de geolocalización -->
            <div class="loc-status" id="locStatus">
                <span>&#x1F4F1;</span>
                <span id="locMsg">La ubicación se obtendrá al confirmar.</span>
            </div>
            <div class="progress-wrap" id="progressWrap" style="display:none;">
                <div class="progress-bar" id="progressBar"></div>
            </div>

            <button type="submit" class="btn btn-success" id="btnRegistrar" style="margin-top:1rem;">
                <span id="btnIcon">&#x2705;</span>
                <span id="btnText">Confirmar asistencia</span>
            </button>

        </form>
    </div>

</main>

<footer class="app-footer">TDV Seguridad &mdash; <?= date('d/m/Y') ?></footer>

<script src="js/app.js"></script>
<script>
(function reloj() {
    const DIAS   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const MESES  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    function tick() {
        const n  = new Date();
        const hh = String(n.getHours()).padStart(2,'0');
        const mm = String(n.getMinutes()).padStart(2,'0');
        const ss = String(n.getSeconds()).padStart(2,'0');
        document.getElementById('relojHora').textContent  = `${hh}:${mm}:${ss}`;
        document.getElementById('relojFecha').textContent =
            `${DIAS[n.getDay()]} ${n.getDate()} de ${MESES[n.getMonth()]} de ${n.getFullYear()}`;
    }
    tick();
    setInterval(tick, 1000);
})();
</script>

</body>
</html>
