<?php

function tiposServicioSolicitud(): array
{
    return [
        'Paquetes de trabajo',
        'Contactos y apagadores',
        'Iluminación',
        'Centros de carga',
        'Conexión de minisplit',
        'Fallas eléctricas',
        'Mantenimiento',
        'Otro',
    ];
}

function folioSolicitud(int $id): string
{
    return 'VOL-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
}

function paquetesParaSolicitud(string $json, array $catalogo): array
{
    if (strlen($json) > 4096) {
        throw new InvalidArgumentException('La selección de paquetes es demasiado grande.');
    }

    try {
        $seleccion = json_decode($json, false, 16, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new InvalidArgumentException('Vuelve a elegir los paquetes de tu solicitud.');
    }

    if (!$seleccion instanceof stdClass || count(get_object_vars($seleccion)) > count($catalogo)) {
        throw new InvalidArgumentException('Vuelve a elegir los paquetes de tu solicitud.');
    }

    $paquetes = [];
    $totalCentavos = 0;
    foreach ($seleccion as $codigo => $cantidad) {
        if (!isset($catalogo[$codigo]) || !is_int($cantidad) || $cantidad < 1 || $cantidad > 10) {
            throw new InvalidArgumentException('Elige paquetes válidos, con un máximo de 10 unidades de cada uno.');
        }

        // El navegador solo aporta códigos y cantidades. El servidor fija nombres y precios.
        $precioCentavos = (int) round($catalogo[$codigo]['precio'] * 100);
        $paquetes[] = [
            'id' => $catalogo[$codigo]['id'],
            'codigo' => $codigo,
            'nombre' => $catalogo[$codigo]['nombre'],
            'cantidad' => $cantidad,
            'precio_unitario' => number_format($precioCentavos / 100, 2, '.', ''),
        ];
        $totalCentavos += $cantidad * $precioCentavos;
    }

    return [
        'paquetes' => $paquetes,
        'total' => number_format($totalCentavos / 100, 2, '.', ''),
    ];
}
