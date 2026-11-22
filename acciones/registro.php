<?php
require_once dirname(__DIR__) . '/config/sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../paginas/registro.php');
    exit;
}

if (!tokenCsrfValido()) {
    $_SESSION['error_registro'] = 'Tu sesión del formulario venció. Inténtalo de nuevo.';
    header('Location: ../paginas/registro.php', true, 303);
    exit;
}

$correo = is_string($_POST['correo'] ?? null) ? strtolower(trim($_POST['correo'])) : '';
$password = $_POST['password'] ?? null;
$confirmacion = $_POST['confirmar_password'] ?? null;
$_SESSION['correo_registro'] = $correo;

if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 254) {
    $error = 'Ingresa un correo electrónico válido.';
} elseif (!is_string($password) || !preg_match('/^.{8,}$/us', $password)
    || strpos($password, "\0") !== false) {
    $error = 'La contraseña debe tener al menos 8 caracteres válidos.';
} elseif (strlen($password) > 72) {
    $error = 'La contraseña es demasiado larga. Usa menos caracteres.';
} elseif (!is_string($confirmacion) || $password !== $confirmacion) {
    $error = 'Las contraseñas no coinciden.';
} else {
    try {
        require dirname(__DIR__) . '/config/conexion.php';

        $consulta = $conexion->prepare('INSERT INTO usuarios (correo, password_hash) VALUES (?, ?)');
        $consulta->execute([$correo, password_hash($password, PASSWORD_DEFAULT)]);

        unset($_SESSION['correo_registro'], $_SESSION['error_registro'], $_SESSION['error_login']);
        $_SESSION['correo_login'] = $correo;
        $_SESSION['exito_registro'] = 'Tu cuenta se creó correctamente. Ya puedes iniciar sesión.';
        header('Location: ../paginas/login.php', true, 303);
        exit;
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) === 1062) {
            $error = 'Este correo ya está registrado. Inicia sesión con tu cuenta.';
        } else {
            error_log('Error de acceso a la base de datos en el registro.');
            $error = 'No pudimos crear tu cuenta. Inténtalo más tarde.';
        }
    }
}

$_SESSION['error_registro'] = $error;
header('Location: ../paginas/registro.php', true, 303);
exit;
