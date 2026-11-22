    
<?php

$servidor = getenv('MYSQLHOST') ?: 'localhost';
$puerto = getenv('MYSQLPORT') ?: '3306';
$baseDatos = getenv('MYSQLDATABASE') ?: 'volts';
$usuarioMySQL = getenv('MYSQLUSER') ?: 'root';
$claveMySQL = getenv('MYSQLPASSWORD');

if ($claveMySQL === false) {
    $claveMySQL = '';
}

$conexion = new PDO(
    "mysql:host=$servidor;port=$puerto;dbname=$baseDatos;charset=utf8mb4",
    $usuarioMySQL,
    $claveMySQL,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
