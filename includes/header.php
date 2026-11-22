<?php
require_once dirname(__DIR__) . '/config/sesion.php';

// Cada página define su título y su nombre antes de incluir este archivo.
$titulo = $titulo ?? 'Volts';
$pagina = $pagina ?? 'inicio';
$esAcceso = in_array($pagina, ['login', 'registro'], true);
// index.php está en la raíz; las demás páginas están dentro de paginas/.
$rutaBase = $pagina === 'inicio' ? '' : '../';
// Las rutas del servidor se calculan desde la ubicación de este archivo.
$raizProyecto = dirname(__DIR__);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (!empty($descripcion)): ?>
    <meta name="description" content="<?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= $rutaBase ?>assets/css/style.css?v=<?= filemtime($raizProyecto . '/assets/css/style.css') ?>">
<?php if (in_array($pagina, ['inicio', 'contacto'], true)): ?>
    <link rel="stylesheet" href="<?= $rutaBase ?>assets/css/inicio.css?v=<?= filemtime($raizProyecto . '/assets/css/inicio.css') ?>">
<?php endif; ?>
    <script src="<?= $rutaBase ?>assets/js/navegacion.js?v=<?= filemtime($raizProyecto . '/assets/js/navegacion.js') ?>" defer></script>
<?php if ($pagina === 'paquetes'): ?>
    <script src="<?= $rutaBase ?>assets/js/paquetes.js?v=<?= filemtime($raizProyecto . '/assets/js/paquetes.js') ?>" defer></script>
<?php endif; ?>
<?php if ($esAcceso): ?>
    <link rel="stylesheet" href="<?= $rutaBase ?>assets/css/login.css?v=<?= filemtime($raizProyecto . '/assets/css/login.css') ?>">
<?php endif; ?>
    <link rel="stylesheet" href="<?= $rutaBase ?>assets/css/cabecera.css?v=<?= filemtime($raizProyecto . '/assets/css/cabecera.css') ?>">
<?php if ($pagina === 'paquetes' || $esAcceso):
    $scriptFormulario = $pagina === 'paquetes' ? 'contacto' : 'login';
?>
    <script src="<?= $rutaBase ?>assets/js/formularios.js?v=<?= filemtime($raizProyecto . '/assets/js/formularios.js') ?>" defer></script>
    <script src="<?= $rutaBase ?>assets/js/<?= $scriptFormulario ?>.js?v=<?= filemtime($raizProyecto . '/assets/js/' . $scriptFormulario . '.js') ?>" defer></script>
<?php endif; ?>
</head>
<body class="pagina-<?= htmlspecialchars($pagina, ENT_QUOTES, 'UTF-8') ?>">
    <header class="cabecera-inicio">
        <a class="logo" href="<?= $rutaBase ?>index.php"><img class="logo-icono" src="<?= $rutaBase ?>assets/icons/zap.svg" width="26" height="32" alt="" aria-hidden="true">Volts</a>
        <nav aria-label="Navegación principal">
            <a href="<?= $rutaBase ?>index.php"<?= $pagina === 'inicio' ? ' aria-current="page"' : '' ?>>Inicio</a>
            <a href="<?= $rutaBase ?>paginas/contacto.php"<?= $pagina === 'contacto' ? ' aria-current="page"' : '' ?>>Contacto</a>
            <details class="menu-explorar<?= in_array($pagina, ['paquetes', 'evidencias'], true) ? ' menu-explorar-actual' : '' ?>">
                <summary aria-controls="enlaces-explorar">Explorar <img class="menu-explorar-icono" src="<?= $rutaBase ?>assets/icons/chevron-down.svg" width="12" height="12" alt="" aria-hidden="true"></summary>
                <div class="menu-explorar-enlaces" id="enlaces-explorar">
                    <a href="<?= $pagina === 'inicio' ? '#servicios' : $rutaBase . 'index.php#servicios' ?>">Servicios</a>
                    <a href="<?= $rutaBase ?>paginas/paquetes.php"<?= $pagina === 'paquetes' ? ' aria-current="page"' : '' ?>>Paquetes</a>
                    <a href="<?= $rutaBase ?>paginas/evidencias.php"<?= $pagina === 'evidencias' ? ' aria-current="page"' : '' ?>>Nuestros trabajos</a>
                </div>
            </details>
<?php if (!empty($_SESSION['usuario_id'])): ?>
            <details class="menu-perfil">
                <summary aria-label="Menú de perfil" aria-controls="panel-perfil" title="Mi perfil">
                    <img class="menu-perfil-icono" src="<?= $rutaBase ?>assets/icons/user-round.svg" width="22" height="22" alt="" aria-hidden="true">
                </summary>
                <div class="menu-perfil-panel" id="panel-perfil">
                    <p class="menu-perfil-etiqueta">Mi cuenta</p>
                    <p class="menu-perfil-correo"><?= htmlspecialchars($_SESSION['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    <form class="sesion-salir" action="<?= $rutaBase ?>acciones/logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit"><img class="icono-accion" src="<?= $rutaBase ?>assets/icons/log-out.svg" width="18" height="18" alt="" aria-hidden="true">Cerrar sesión</button>
                    </form>
                </div>
            </details>
<?php else: ?>
            <a class="navegacion-acceso" href="<?= $rutaBase ?>paginas/login.php"<?= $pagina === 'login' ? ' aria-current="page"' : '' ?>>Iniciar sesión</a>
<?php endif; ?>
        </nav>
    </header>
