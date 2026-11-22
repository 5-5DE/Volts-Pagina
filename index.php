<?php
$titulo = 'Inicio | Volts';
$pagina = 'inicio';
$descripcion = 'Instalaciones y reparaciones eléctricas en Chihuahua. Conoce los trabajos de Volts y consulta por tu instalación.';
require __DIR__ . '/includes/header.php';
?>

    <main class="inicio-contenido">
        <section class="inicio-portada" aria-labelledby="titulo-inicio">
            <div class="inicio-portada-texto">
                <p class="inicio-etiqueta"><span aria-hidden="true"></span> Servicios eléctricos en Chihuahua</p>
                <h1 id="titulo-inicio">Electricidad<br><span>bien hecha.</span></h1>
                <div class="inicio-acciones">
                    <a class="inicio-boton" href="paginas/paquetes.php#consulta-trabajo">Consultar un trabajo <img class="icono-accion" src="assets/icons/arrow-up-right-oscuro.svg" width="20" height="20" alt="" aria-hidden="true"></a>
                    <a class="inicio-enlace" href="paginas/evidencias.php">Ver nuestros trabajos <img class="icono-accion" src="assets/icons/arrow-right.svg" width="18" height="18" alt="" aria-hidden="true"></a>
                </div>
               
            </div>
        </section>

        <section class="inicio-servicios" id="servicios" aria-labelledby="titulo-servicios">
            <div class="inicio-seccion-encabezado">
                <div>
                    <p class="inicio-etiqueta">Lo que hacemos</p>
                    <h2 id="titulo-servicios">Soluciones para tu espacio.</h2>
                </div>
                <a class="inicio-enlace" href="paginas/paquetes.php">Ver paquetes <img class="icono-accion" src="assets/icons/arrow-up-right.svg" width="18" height="18" alt="" aria-hidden="true"></a>
            </div>
            <ul class="inicio-servicios-lista">
                <li>
                    <img class="inicio-servicio-icono" src="assets/icons/plug.svg" width="30" height="30" alt="" aria-hidden="true">
                    <h3>Instalaciones</h3>
                    <p>Contactos, iluminación, centros de carga y conexiones para minisplits.</p>
                </li>
                <li>
                    <img class="inicio-servicio-icono" src="assets/icons/zap.svg" width="30" height="30" alt="" aria-hidden="true">
                    <h3>Reparaciones</h3>
                    <p>Revisión de fallas eléctricas, cortocircuitos y conexiones.</p>
                </li>
                <li>
                    <img class="inicio-servicio-icono" src="assets/icons/shield-check.svg" width="30" height="30" alt="" aria-hidden="true">
                    <h3>Mantenimiento</h3>
                    <p>Revisión del cableado y los componentes de tu instalación.</p>
                </li>
            </ul>
        </section>

        <section class="inicio-contacto" aria-labelledby="titulo-contacto">
            <div>
                <h2 id="titulo-contacto">¿Tienes una duda sobre tu trabajo?</h2>
                <p>Si algo necesita revisión o tienes alguna pregunta, estamos para escucharte.</p>
            </div>
            <a class="inicio-boton inicio-boton-contacto" href="paginas/contacto.php">Contáctanos <img class="icono-accion" src="assets/icons/arrow-up-right.svg" width="20" height="20" alt="" aria-hidden="true"></a>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
