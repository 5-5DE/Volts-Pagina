<?php
require_once dirname(__DIR__) . '/config/sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !tokenCsrfValido()) {
    header('Location: ../index.php', true, 303);
    exit;
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'],
    ]);
}

session_destroy();
header('Location: ../index.php', true, 303);
exit;
