<?php

$servidor = 'localhost';
$baseDatos = 'volts';
$usuarioMySQL = 'root';
$claveMySQL = '';

$conexion = new PDO(
    "mysql:host=$servidor;dbname=$baseDatos;charset=utf8mb4",
    $usuarioMySQL,
    $claveMySQL,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
