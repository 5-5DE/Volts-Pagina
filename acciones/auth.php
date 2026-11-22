<?php
require_once dirname(__DIR__) . '/config/sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../paginas/login.php');
    exit;
}

if (!tokenCsrfValido()) {
    $_SESSION['error_login'] = 'Tu sesión del formulario venció. Inténtalo de nuevo.';
    header('Location: ../paginas/login.php', true, 303);
    exit;
}

$correo = is_string($_POST['correo'] ?? null) ? strtolower(trim($_POST['correo'])) : '';
$password = $_POST['password'] ?? null;
$_SESSION['correo_login'] = $correo;

if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 254
    || !is_string($password) || $password === '' || strlen($password) > 72
    || strpos($password, "\0") !== false) {
    $_SESSION['error_login'] = 'Ingresa un correo válido y tu contraseña.';
    header('Location: ../paginas/login.php', true, 303);
    exit;
}

try {
    require dirname(__DIR__) . '/config/conexion.php';

    $consulta = $conexion->prepare(
        'SELECT id, correo, password_hash FROM usuarios WHERE correo = ? LIMIT 1'
    );
    $consulta->execute([$correo]);
    $usuario = $consulta->fetch();

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['error_login'], $_SESSION['correo_login'], $_SESSION['exito_registro']);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $destino = ($_SESSION['destino_login'] ?? '') === 'paquetes'
            ? '../paginas/paquetes.php#consulta-trabajo'
            : '../index.php';
        unset($_SESSION['destino_login']);
        header('Location: ' . $destino, true, 303);
        exit;
    }

    $_SESSION['error_login'] = 'Correo o contraseña incorrectos.';
} catch (PDOException $e) {
    error_log('Error de acceso a la base de datos en el inicio de sesión.');
    $_SESSION['error_login'] = 'No pudimos iniciar sesión. Inténtalo más tarde.';
}

header('Location: ../paginas/login.php', true, 303);
exit;
