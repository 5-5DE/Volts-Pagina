<?php
// Los servicios y precios se administran en la tabla paquetes de la base de datos.
function cargarCatalogoPaquetes(PDO $conexion, bool $bloquear = false): array
{
    $sql = 'SELECT id, codigo, nombre, descripcion, incluye, precio FROM paquetes WHERE activo = 1 ORDER BY orden, id';
    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }

    $catalogo = [];
    foreach ($conexion->query($sql)->fetchAll() as $paquete) {
        $paquete['id'] = (int) $paquete['id'];
        $catalogo[$paquete['codigo']] = $paquete;
    }
    return $catalogo;
}
