<?php
$titulo = 'Contacto y seguimiento | Volts';
$pagina = 'contacto';
$descripcion = 'Resuelve tus dudas o solicita seguimiento de un trabajo realizado por Volts. Atención por WhatsApp, teléfono y correo en Chihuahua.';
$correoContacto = 'atencion.volts@gmail.com';
$telefonoContacto = '526142131223';
$enlaceWhatsapp = 'https://wa.me/' . $telefonoContacto . '?' . http_build_query([
    'text' => 'Hola, equipo de Volts. Necesito ayuda con una consulta o con el seguimiento de un trabajo que realizaron.',
], '', '&', PHP_QUERY_RFC3986);
$enlaceCorreo = 'https://mail.google.com/mail/?' . http_build_query([
    'view' => 'cm',
    'fs' => '1',
    'to' => $correoContacto,
    'su' => 'Consulta o seguimiento de un trabajo | Volts',
    'body' => "Hola, equipo de Volts.\r\n\r\nMi nombre es: \r\nNecesito ayuda con: \r\nFecha aproximada del trabajo (si aplica): \r\n",
], '', '&', PHP_QUERY_RFC3986);
require dirname(__DIR__) . '/includes/header.php';
?>
<main class="container">
    <header class="contacto-intro">
        <p class="contacto-etiqueta">Contacto y atención al cliente</p>
        <h1>¿En qué podemos ayudarte?</h1>
    </header>

    <section class="contacto-tarjeta" aria-labelledby="titulo-ayuda">
        <img class="contacto-icono" src="../assets/icons/message-circle-question-mark.svg" width="32" height="32" alt="" aria-hidden="true">
        <h2 id="titulo-ayuda">¿Necesitas ayuda o tuviste alguna falla?</h2>
        <p>Si tienes una duda sobre nuestros servicios o detectaste una falla en un trabajo que realizamos, escríbenos por WhatsApp o correo. Cuéntanos qué necesitas para poder ayudarte y darle seguimiento.</p>
        <p class="contacto-ayuda">Si se trata de una revisión, incluye tu nombre, la fecha aproximada del trabajo y una descripción del problema. También puedes enviarnos fotos para ayudarnos a revisar el caso.</p>
        <div class="contacto-acciones">
            <a class="contacto-boton" href="<?= htmlspecialchars($enlaceWhatsapp, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Contactar por WhatsApp <img class="icono-accion" src="../assets/icons/arrow-up-right-oscuro.svg" width="18" height="18" alt="" aria-hidden="true"></a>
            <a class="contacto-boton contacto-boton-correo" href="<?= htmlspecialchars($enlaceCorreo, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Contactar por correo <img class="icono-accion" src="../assets/icons/arrow-up-right.svg" width="18" height="18" alt="" aria-hidden="true"></a>
        </div>
    </section>


    <p class="contacto-nuevo">¿Buscas un nuevo trabajo? <a class="enlace-contacto" href="paquetes.php#consulta-trabajo">Consulta nuestros paquetes y envía tu solicitud</a>.</p>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
