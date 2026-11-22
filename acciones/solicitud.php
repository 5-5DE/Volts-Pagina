<?php
require_once dirname(__DIR__) . '/config/sesion.php';
require_once dirname(__DIR__) . '/includes/solicitudes.php';
require_once dirname(__DIR__) . '/catalogo-paquetes.php';

function volverAlFormularioSolicitud(): void
{
    header('Location: ../paginas/paquetes.php#consulta-trabajo', true, 303);
    exit;
}

function confirmarSolicitud(array $solicitud): void
{
    unset($_SESSION['datos_solicitud'], $_SESSION['errores_solicitud']);
    $_SESSION['solicitud_guardada'] = [
        'folio' => folioSolicitud((int) $solicitud['id']),
        'total' => $solicitud['total_estimado'],
    ];
    volverAlFormularioSolicitud();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volverAlFormularioSolicitud();
}

if (empty($_SESSION['usuario_id'])) {
    $_SESSION['destino_login'] = 'paquetes';
    $_SESSION['error_login'] = 'Inicia sesión para enviar tu solicitud.';
    header('Location: ../paginas/login.php', true, 303);
    exit;
}

$datos = [];
foreach (['nombre', 'telefono', 'zona', 'descripcion', 'servicio', 'paquetes', 'clave_envio'] as $campo) {
    $valor = $_POST[$campo] ?? '';
    $datos[$campo] = is_string($valor) && strlen($valor) <= 5000 ? trim($valor) : '';
}
$_SESSION['datos_solicitud'] = $datos;

if (!tokenCsrfValido()) {
    $_SESSION['errores_solicitud'] = ['general' => 'Tu sesión del formulario venció. Revisa los datos y vuelve a enviar.'];
    volverAlFormularioSolicitud();
}

$errores = [];
if (!preg_match('/^[a-f0-9]{64}$/D', $datos['clave_envio'])) {
    $errores['general'] = 'El formulario venció. Revisa los datos y vuelve a enviar.';
}
if (mb_strlen($datos['zona']) < 3 || mb_strlen($datos['zona']) > 150) {
    $errores['zona'] = 'Indica la colonia o zona del trabajo, entre 3 y 150 caracteres.';
}
if (mb_strlen($datos['descripcion']) < 10 || mb_strlen($datos['descripcion']) > 1000) {
    $errores['descripcion'] = 'Describe tu trabajo con entre 10 y 1000 caracteres.';
}
if (!in_array($datos['servicio'], tiposServicioSolicitud(), true)) {
    $errores['servicio'] = 'Selecciona un tipo de servicio válido.';
}

try {
    require dirname(__DIR__) . '/config/conexion.php';
    $conexion->beginTransaction();

    // Bloquea solo el perfil de este cliente y permite reconocer un envío repetido.
    $consulta = $conexion->prepare('SELECT id, nombre, telefono FROM usuarios WHERE id = ? FOR UPDATE');
    $consulta->execute([$_SESSION['usuario_id']]);
    $usuario = $consulta->fetch();
    if (!$usuario) {
        $conexion->rollBack();
        unset($_SESSION['usuario_id'], $_SESSION['correo']);
        $_SESSION['destino_login'] = 'paquetes';
        $_SESSION['error_login'] = 'Vuelve a iniciar sesión para enviar tu solicitud.';
        header('Location: ../paginas/login.php', true, 303);
        exit;
    }

    $consulta = $conexion->prepare('SELECT id, total_estimado FROM solicitudes WHERE usuario_id = ? AND clave_envio = ?');
    $consulta->execute([$usuario['id'], $datos['clave_envio']]);
    $existente = $consulta->fetch();
    if ($existente) {
        $conexion->commit();
        confirmarSolicitud($existente);
    }

    try {
        $catalogo = cargarCatalogoPaquetes($conexion, true);
        $seleccion = paquetesParaSolicitud($datos['paquetes'], $catalogo);
        if ($seleccion['paquetes']) {
            $datos['servicio'] = 'Paquetes de trabajo';
            unset($errores['servicio']);
        } elseif ($datos['servicio'] === 'Paquetes de trabajo') {
            $errores['paquetes'] = 'Agrega al menos un paquete o elige otro tipo de servicio.';
        }
    } catch (InvalidArgumentException $e) {
        $errores['paquetes'] = $e->getMessage();
    }

    $nombre = trim($usuario['nombre'] ?? '') !== '' ? $usuario['nombre'] : $datos['nombre'];
    $telefono = trim($usuario['telefono'] ?? '') !== '' ? $usuario['telefono'] : $datos['telefono'];
    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        $errores['nombre'] = 'Escribe tu nombre con entre 2 y 100 caracteres.';
    }
    $digitos = preg_replace('/\D/', '', $telefono);
    if (!preg_match('/^[0-9+() .-]{10,20}$/D', $telefono) || strlen($digitos) < 10 || strlen($digitos) > 15) {
        $errores['telefono'] = 'Escribe un teléfono válido con 10 a 15 dígitos.';
    }

    if ($errores) {
        $conexion->rollBack();
        $_SESSION['errores_solicitud'] = $errores;
        volverAlFormularioSolicitud();
    }

    $consulta = $conexion->prepare('UPDATE usuarios SET nombre = ?, telefono = ? WHERE id = ?');
    $consulta->execute([$nombre, $telefono, $usuario['id']]);

    $consulta = $conexion->prepare('INSERT INTO solicitudes (usuario_id, clave_envio, tipo_servicio, zona, descripcion, total_estimado) VALUES (?, ?, ?, ?, ?, ?)');
    $consulta->execute([$usuario['id'], $datos['clave_envio'], $datos['servicio'], $datos['zona'], $datos['descripcion'], $seleccion['total']]);
    $solicitudId = (int) $conexion->lastInsertId();

    $detalle = $conexion->prepare('INSERT INTO solicitud_paquetes (solicitud_id, paquete_id, codigo_paquete, nombre_paquete, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($seleccion['paquetes'] as $paquete) {
        $detalle->execute([$solicitudId, $paquete['id'], $paquete['codigo'], $paquete['nombre'], $paquete['cantidad'], $paquete['precio_unitario']]);
    }

    $conexion->commit();
    confirmarSolicitud(['id' => $solicitudId, 'total_estimado' => $seleccion['total']]);
} catch (PDOException $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('No se pudo guardar una solicitud de Volts. Código SQL: ' . $e->getCode());
    $_SESSION['errores_solicitud'] = ['general' => 'No pudimos guardar tu solicitud. Conservamos tus datos para que vuelvas a intentarlo.'];
    volverAlFormularioSolicitud();
}
