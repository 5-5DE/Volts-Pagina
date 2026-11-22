<?php
require_once dirname(__DIR__) . '/config/sesion.php';

if (!empty($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = $_SESSION['error_registro'] ?? '';
$correo = $_SESSION['correo_registro'] ?? '';
unset($_SESSION['error_registro'], $_SESSION['correo_registro']);

$titulo = 'Crear cuenta | Volts';
$pagina = 'registro';
require dirname(__DIR__) . '/includes/header.php';
?>

    <main class="login-main">
        <section class="acceso" aria-labelledby="titulo-registro">
            <div class="acceso-presentacion">
                <span class="acceso-etiqueta">SERVICIOS ELÉCTRICOS</span>
                <img class="rayo-grande" src="../assets/icons/zap.svg" width="82" height="120" alt="" aria-hidden="true">
                <h1 id="titulo-registro">Tu próximo proyecto<br>empieza aquí.</h1>
                <p>Crea tu cuenta y forma parte de Volts.</p>
                <span class="acceso-firma">VOLTS / CHIHUAHUA</span>
            </div>

            <div class="acceso-formulario">
                <h2>Crear cuenta</h2>
                <p class="acceso-intro">Regístrate con tu correo electrónico.</p>
                <form action="../acciones/registro.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <label for="correo">Correo electrónico</label>
                    <input id="correo" name="correo" type="email" autocomplete="username"
                        placeholder="nombre@ejemplo.com" maxlength="254" required
                        value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>"
                        aria-describedby="error-correo">
                    <p class="error-campo" id="error-correo" aria-live="polite"></p>

                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres" minlength="8" required
                        aria-describedby="ayuda-password error-password">
                    <p class="acceso-nota" id="ayuda-password">Usa al menos 8 caracteres.</p>
                    <p class="error-campo" id="error-password" aria-live="polite"></p>

                    <label for="confirmar-password">Confirmar contraseña</label>
                    <input id="confirmar-password" name="confirmar_password" type="password"
                        autocomplete="new-password" placeholder="Repite tu contraseña" minlength="8"
                        required aria-describedby="error-confirmar-password">
                    <p class="error-campo" id="error-confirmar-password" aria-live="polite"></p>

                    <button class="boton-acceso" type="submit">Crear cuenta <img class="icono-accion" src="../assets/icons/zap-oscuro.svg" width="22" height="26" alt="" aria-hidden="true"></button>
                </form>
                <p class="mensaje-formulario" id="mensaje-login" role="status"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="acceso-registro">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
                <div class="acceso-ayuda">
                    <span>¿Necesitas ayuda?</span>
                    <a href="contacto.php">Contáctanos <img class="icono-accion" src="../assets/icons/arrow-right.svg" width="18" height="18" alt="" aria-hidden="true"></a>
                </div>
            </div>
        </section>
    </main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
