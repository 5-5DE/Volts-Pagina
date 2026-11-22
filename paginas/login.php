<?php
require_once dirname(__DIR__) . '/config/sesion.php';

if (($_GET['continuar'] ?? '') === 'paquetes') {
    $_SESSION['destino_login'] = 'paquetes';
}

if (!empty($_SESSION['usuario_id'])) {
    $destino = ($_SESSION['destino_login'] ?? '') === 'paquetes' ? 'paquetes.php#consulta-trabajo' : '../index.php';
    unset($_SESSION['destino_login']);
    header('Location: ' . $destino);
    exit;
}

$error = $_SESSION['error_login'] ?? '';
$exito = $_SESSION['exito_registro'] ?? '';
$correo = $_SESSION['correo_login'] ?? '';
unset($_SESSION['error_login'], $_SESSION['exito_registro'], $_SESSION['correo_login']);

$titulo = 'Iniciar sesión | Volts';
$pagina = 'login';
require dirname(__DIR__) . '/includes/header.php';
?>



    <main class="login-main">
        <section class="acceso" aria-labelledby="titulo-login">
            <div class="acceso-presentacion">
                <span class="acceso-etiqueta">SERVICIOS ELÉCTRICOS</span>
                <img class="rayo-grande" src="../assets/icons/zap.svg" width="82" height="120" alt="" aria-hidden="true">
                <h1 id="titulo-login">Conecta con<br>tu espacio.</h1>
                <p>Bienvenido a Volts. Un lugar para empezar tus próximos proyectos.</p>
                <span class="acceso-firma">VOLTS / CHIHUAHUA</span>
            </div>

            <div class="acceso-formulario">
                <h2>Iniciar sesión</h2>
                <p class="acceso-intro">Ingresa tus datos.</p>
                <form action="../acciones/auth.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <label for="correo">Correo electrónico</label>
                    <input id="correo" name="correo" type="email"
                        autocomplete="username" placeholder="nombre@ejemplo.com"
                        value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="254" required aria-describedby="error-correo">
                    <p class="error-campo" id="error-correo" aria-live="polite"></p>

                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password"
                        autocomplete="current-password" placeholder="Tu contraseña"
                        required aria-describedby="error-password">
                    <p class="error-campo" id="error-password" aria-live="polite"></p>

                    <button class="boton-acceso" type="submit">Iniciar sesión <img class="icono-accion" src="../assets/icons/zap-oscuro.svg" width="22" height="26" alt="" aria-hidden="true"></button>
                </form>
                <p class="mensaje-formulario<?= $exito !== '' && $error === '' ? ' mensaje-correcto' : '' ?>" id="mensaje-login" role="status"><?= htmlspecialchars($error !== '' ? $error : $exito, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="acceso-registro">¿Aún no tienes cuenta? <a href="registro.php">Regístrate</a></p>
                <div class="acceso-ayuda">
                    <span>¿Necesitas ayuda?</span>
                    <a href="contacto.php">Contáctanos <img class="icono-accion" src="../assets/icons/arrow-right.svg" width="18" height="18" alt="" aria-hidden="true"></a>
                </div>
            </div>
        </section>

    </main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
