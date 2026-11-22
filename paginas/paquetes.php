<?php
require_once dirname(__DIR__) . '/config/sesion.php';
require_once dirname(__DIR__) . '/includes/solicitudes.php';
require_once dirname(__DIR__) . '/catalogo-paquetes.php';

$usuarioSolicitud = null;
$paquetes = [];
$errorCatalogo = '';
$errorCargaSolicitud = '';
$erroresSolicitud = $_SESSION['errores_solicitud'] ?? [];
$datosSolicitud = $_SESSION['datos_solicitud'] ?? [];
$solicitudGuardada = $_SESSION['solicitud_guardada'] ?? null;
unset($_SESSION['solicitud_guardada']);

try {
    require dirname(__DIR__) . '/config/conexion.php';
    $paquetes = cargarCatalogoPaquetes($conexion);
} catch (PDOException $e) {
    error_log('No se pudo cargar el catálogo de paquetes de Volts.');
    $errorCatalogo = 'No pudimos cargar los paquetes. Vuelve a intentar más tarde.';
    $errorCargaSolicitud = $errorCatalogo;
}

if (!empty($_SESSION['usuario_id']) && $errorCatalogo === '') {
    try {
        $consulta = $conexion->prepare('SELECT id, correo, nombre, telefono FROM usuarios WHERE id = ?');
        $consulta->execute([$_SESSION['usuario_id']]);
        $usuarioSolicitud = $consulta->fetch();
        if (!$usuarioSolicitud) {
            unset($_SESSION['usuario_id'], $_SESSION['correo']);
        }
    } catch (PDOException $e) {
        error_log('No se pudo cargar el perfil para una solicitud de Volts.');
        $errorCargaSolicitud = 'No pudimos cargar tus datos. Vuelve a intentar más tarde.';
    }
}

if ($errorCargaSolicitud === '') {
    unset($_SESSION['errores_solicitud'], $_SESSION['datos_solicitud']);
}

$claveEnvio = $datosSolicitud['clave_envio'] ?? '';
if (!preg_match('/^[a-f0-9]{64}$/D', $claveEnvio)) {
    $claveEnvio = bin2hex(random_bytes(32));
}
$escSolicitud = static function ($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

$titulo = 'Paquetes | Volts';
$pagina = 'paquetes';
$descripcion = 'Elige tus paquetes de servicios eléctricos y consulta el total estimado de tu trabajo con Volts.';
require dirname(__DIR__) . '/includes/header.php';
?>

    <main class="paquetes-contenido">
        <section class="seccion-paquetes" id="paquetes" aria-labelledby="titulo-paquetes">
            <div class="servicios-encabezado paquetes-encabezado">
                <p class="paquetes-etiqueta">ELIGE TU SERVICIO</p>
                <h1 id="titulo-paquetes">Paquetes de trabajo</h1>
                <p>Conoce qué incluye cada trabajo y agrega los paquetes que necesitas.</p>
            </div>
            <p class="paquetes-aviso" id="aviso-precios">Precios de ejemplo en MXN, solo mano de obra. Los materiales y trabajos adicionales se cotizan por separado. Confirma el costo al consultar.</p>
<?php if ($errorCatalogo !== ''): ?>
            <p class="mensaje-formulario" role="status"><?= $escSolicitud($errorCatalogo) ?></p>
<?php elseif (!$paquetes): ?>
            <p class="paquetes-aviso">Por ahora no hay paquetes disponibles. Puedes describir otro trabajo en el formulario.</p>
<?php endif; ?>

            <div class="lista-paquetes">
<?php foreach ($paquetes as $id => $paquete): ?>
                <article class="paquete-tarjeta" data-paquete-id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" data-paquete-nombre="<?= htmlspecialchars($paquete['nombre'], ENT_QUOTES, 'UTF-8') ?>" data-paquete-precio="<?= $paquete['precio'] ?>">
                    <h2><?= htmlspecialchars($paquete['nombre'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="paquete-descripcion"><?= htmlspecialchars($paquete['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="paquete-incluye"><strong>Incluye:</strong> <?= htmlspecialchars($paquete['incluye'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="paquete-precio">$<?= number_format($paquete['precio'], 2) ?> <span>MXN / paquete</span></p>
                    <button class="boton-servicios paquete-agregar" type="button" aria-label="Agregar <?= htmlspecialchars($paquete['nombre'], ENT_QUOTES, 'UTF-8') ?>" aria-describedby="aviso-precios" disabled><span class="paquete-agregar-texto">Agregar paquete</span> <img class="icono-accion" src="../assets/icons/plus-oscuro.svg" width="18" height="18" alt="" aria-hidden="true"></button>
                </article>
<?php endforeach; ?>
            </div>

<?php if ($paquetes): ?>
            <noscript><p class="paquetes-aviso">Activa JavaScript para seleccionar paquetes o <a href="contacto.php">consulta los paquetes por contacto</a>.</p></noscript>
<?php endif; ?>

            <div class="paquetes-resumen" aria-labelledby="titulo-seleccion" hidden>
                <div class="paquetes-resumen-encabezado">
                    <h2 id="titulo-seleccion">Tu selección <span id="cantidad-paquetes">0 paquetes</span></h2>
                    <button class="paquetes-vaciar" id="vaciar-paquetes" type="button" hidden>Vaciar selección</button>
                </div>
                <p class="paquetes-vacio" id="paquetes-vacio">Aún no has agregado paquetes. Elige uno para comenzar.</p>
                <ul class="paquetes-seleccionados" id="paquetes-seleccionados" aria-label="Paquetes seleccionados"></ul>
                <div class="paquetes-resumen-pie" id="paquetes-resumen-pie" hidden>
                    <p class="paquetes-total">Total estimado <strong id="total-paquetes">$0.00 MXN</strong></p>
                    <a class="boton-servicios" id="consultar-paquetes" href="#consulta-trabajo">Consultar paquetes <img class="icono-accion" src="../assets/icons/arrow-right-oscuro.svg" width="18" height="18" alt="" aria-hidden="true"></a>
                </div>
                <p class="paquetes-mensaje" id="mensaje-paquetes" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
        </section>

        <section class="contacto-formulario" id="consulta-trabajo" aria-labelledby="titulo-formulario">
<?php if ($solicitudGuardada): ?>
            <div class="mensaje-formulario mensaje-correcto solicitud-confirmacion" id="solicitud-confirmada" role="status">
                <strong>Solicitud <?= $escSolicitud($solicitudGuardada['folio']) ?> recibida.</strong>
                <p>Estado: Pendiente. Usaremos los datos de tu cuenta para contactarte.</p>
<?php if ((float) $solicitudGuardada['total'] > 0): ?>
                <p>Total estimado: $<?= number_format((float) $solicitudGuardada['total'], 2) ?> MXN. El costo final se confirma al revisar el trabajo.</p>
<?php else: ?>
                <p>El costo del trabajo se confirmará al revisar tu solicitud.</p>
<?php endif; ?>
            </div>
<?php endif; ?>
            <h2 id="titulo-formulario">Cuéntanos sobre tu trabajo</h2>
<?php if ($errorCargaSolicitud !== ''): ?>
            <p class="mensaje-formulario" role="status"><?= $escSolicitud($errorCargaSolicitud) ?></p>
<?php elseif (!$usuarioSolicitud): ?>
            <p>Elige tus paquetes e inicia sesión para enviar tu solicitud. Usaremos los datos de tu cuenta.</p>
            <a class="boton-servicios" href="login.php?continuar=paquetes">Iniciar sesión para continuar <img class="icono-accion" src="../assets/icons/arrow-right-oscuro.svg" width="18" height="18" alt="" aria-hidden="true"></a>
<?php else: ?>
            <p>Describe el trabajo y su ubicación. Todos los campos son obligatorios.</p>
<?php if (trim($usuarioSolicitud['nombre'] ?? '') !== '' && trim($usuarioSolicitud['telefono'] ?? '') !== ''): ?>
            <p class="solicitud-cuenta">Datos de contacto: <?= $escSolicitud($usuarioSolicitud['nombre']) ?> · <?= $escSolicitud($usuarioSolicitud['telefono']) ?>.</p>
<?php else: ?>
            <p class="solicitud-cuenta">Completa tus datos de contacto esta primera vez. Los guardaremos en tu cuenta.</p>
<?php endif; ?>
            <p class="solicitud-paquetes" id="solicitud-paquetes" hidden></p>
            <form id="formulario-contacto" action="../acciones/solicitud.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $escSolicitud($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="clave_envio" value="<?= $escSolicitud($claveEnvio) ?>">
                <input type="hidden" name="paquetes" id="paquetes-elegidos" value="<?= $escSolicitud($datosSolicitud['paquetes'] ?? '{}') ?>" data-restaurar="<?= $datosSolicitud ? 'true' : 'false' ?>">
                <label for="correo-cuenta">Correo electrónico</label>
                <input id="correo-cuenta" name="correo" type="email" autocomplete="email" value="<?= $escSolicitud($usuarioSolicitud['correo']) ?>" readonly aria-describedby="error-correo-cuenta">
                <p class="error-campo" id="error-correo-cuenta" aria-live="polite"></p>
<?php if (trim($usuarioSolicitud['nombre'] ?? '') === ''): ?>
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" type="text" autocomplete="name" required minlength="2" maxlength="100" value="<?= $escSolicitud($datosSolicitud['nombre'] ?? '') ?>" aria-invalid="<?= isset($erroresSolicitud['nombre']) ? 'true' : 'false' ?>" aria-describedby="error-nombre">
                <p class="error-campo" id="error-nombre" aria-live="polite"><?= $escSolicitud($erroresSolicitud['nombre'] ?? '') ?></p>
<?php endif; ?>
<?php if (trim($usuarioSolicitud['telefono'] ?? '') === ''): ?>
                <label for="telefono">Teléfono</label>
                <input id="telefono" name="telefono" type="tel" autocomplete="tel" placeholder="614 123 4567" required maxlength="20" pattern="[0-9+\(\) .\-]{10,20}" value="<?= $escSolicitud($datosSolicitud['telefono'] ?? '') ?>" aria-invalid="<?= isset($erroresSolicitud['telefono']) ? 'true' : 'false' ?>" aria-describedby="error-telefono">
                <p class="error-campo" id="error-telefono" aria-live="polite"><?= $escSolicitud($erroresSolicitud['telefono'] ?? '') ?></p>
<?php endif; ?>
                <label for="zona">Colonia o zona del trabajo</label>
                <input id="zona" name="zona" type="text" required minlength="3" maxlength="150" placeholder="Por ejemplo, Colonia Centro, Chihuahua" value="<?= $escSolicitud($datosSolicitud['zona'] ?? '') ?>" aria-invalid="<?= isset($erroresSolicitud['zona']) ? 'true' : 'false' ?>" aria-describedby="error-zona">
                <p class="error-campo" id="error-zona" aria-live="polite"><?= $escSolicitud($erroresSolicitud['zona'] ?? '') ?></p>
                <label for="servicio">Tipo de servicio</label>
                <select id="servicio" name="servicio" required aria-invalid="<?= isset($erroresSolicitud['servicio']) ? 'true' : 'false' ?>" aria-describedby="error-servicio">
                    <option value="">Selecciona un servicio</option>
<?php foreach (tiposServicioSolicitud() as $tipoServicio): ?>
                    <option<?= ($datosSolicitud['servicio'] ?? '') === $tipoServicio ? ' selected' : '' ?>><?= $escSolicitud($tipoServicio) ?></option>
<?php endforeach; ?>
                </select>
                <p class="error-campo" id="error-servicio" aria-live="polite"><?= $escSolicitud($erroresSolicitud['servicio'] ?? '') ?></p>
                <p class="error-campo" id="error-paquetes" aria-live="polite"><?= $escSolicitud($erroresSolicitud['paquetes'] ?? '') ?></p>
                <label for="descripcion">Descripción del trabajo</label>
                <textarea id="descripcion" name="descripcion" rows="5" required minlength="10" maxlength="1000" placeholder="Cuéntanos qué falla o qué necesitas instalar." aria-invalid="<?= isset($erroresSolicitud['descripcion']) ? 'true' : 'false' ?>" aria-describedby="error-descripcion"><?= $escSolicitud($datosSolicitud['descripcion'] ?? '') ?></textarea>
                <p class="error-campo" id="error-descripcion" aria-live="polite"><?= $escSolicitud($erroresSolicitud['descripcion'] ?? '') ?></p>
                <button class="boton-servicios" type="submit">Enviar solicitud</button>
                <p class="mensaje-formulario" id="mensaje-contacto" role="status"><?= $escSolicitud($erroresSolicitud ? ($erroresSolicitud['general'] ?? 'Revisa los campos marcados antes de enviar tu solicitud.') : '') ?></p>
            </form>
<?php endif; ?>
        </section>
    </main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
