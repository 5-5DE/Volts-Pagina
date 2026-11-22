<?php
$titulo = 'Evidencias | Volts';
$pagina = 'evidencias';
require dirname(__DIR__) . '/includes/header.php';
?>

    <main class="evidencias-contenido">
        <h1>Evidencias de nuestros trabajos</h1>
        <p class="evidencias-intro">Un espacio para mostrar los trabajos realizados y sus detalles.</p>

        <div class="evidencias-lista">
    
            <article class="evidencia-tarjeta">
                <div class="evidencia-imagen">
                    <video controls preload="metadata">
                    <source src="../assets/videos/trabajo-2.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="evidencia-texto">
                    <h2>Instalacion de centro de carga para Mnisplits</h2>
                    <p>Trabajo realizado para la alimentacion de tres Mnisplits, con pastillas termicas para cada Minisplit.</p>
                </div>
            </article>

            <article class="evidencia-tarjeta">
                <div class="evidencia-imagen">
                   
                    <img src="../assets/images/trabajo1.jpeg" alt="Descripción de la fotografía">
                </div>
                <div class="evidencia-texto">
                    <h2>Instalacion de medior de 110V</h2>
                    <p>Instalacion de medidor de 110V, con cometida para CFE.</p>
                </div>
            </article>

            <article class="evidencia-tarjeta">
                <div class="evidencia-imagen">
                    <video controls preload="metadata">
                    <source src="../assets/videos/trabajo-3.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="evidencia-texto">
                    <h2>Instalacion de Minisplit</h2>
                    <p>Instalacion de Minisplit con instalación electrica desde el centro de carga con pastilla termica independiente
                         hasta el equipo.</p>
                </div>
            </article>
        </div>
    </main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
