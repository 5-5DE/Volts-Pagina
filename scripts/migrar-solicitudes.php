<?php
// Ejecutar una vez desde la terminal: C:\xampp\php\php.exe scripts/migrar-solicitudes.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    require dirname(__DIR__) . '/config/conexion.php';

    $columnas = array_column($conexion->query('SHOW COLUMNS FROM usuarios')->fetchAll(), 'Field');
    if (!in_array('nombre', $columnas, true)) {
        $conexion->exec('ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(100) NULL');
    }
    if (!in_array('telefono', $columnas, true)) {
        $conexion->exec('ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL');
    }

    $conexion->exec("CREATE TABLE IF NOT EXISTS paquetes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        codigo VARCHAR(60) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT NOT NULL,
        incluye TEXT NOT NULL,
        precio DECIMAL(10,2) UNSIGNED NOT NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY paquetes_codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Solo datos iniciales: volver a ejecutar no reemplaza cambios hechos en el catálogo.
    $iniciales = [
        ['contactos', 'Contactos y apagadores',
            'Cambio de hasta 2 contactos o apagadores en puntos que ya cuentan con instalación.',
            'Retiro de las piezas anteriores, colocación y prueba de funcionamiento.', '350.00', 1],
        ['iluminacion', 'Instalación de iluminación',
            'Colocación de hasta 2 lámparas en puntos eléctricos existentes.',
            'Montaje, conexión y prueba de encendido de las luminarias.', '450.00', 2],
        ['revision', 'Revisión eléctrica',
            'Visita de diagnóstico para revisar una falla eléctrica en tu casa o negocio.',
            'Revisión del punto reportado y explicación del trabajo recomendado.', '600.00', 3],
    ];
    $insertar = $conexion->prepare('INSERT INTO paquetes (codigo, nombre, descripcion, incluye, precio, orden) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE codigo = VALUES(codigo)');
    foreach ($iniciales as $paquete) {
        $insertar->execute($paquete);
    }

    $conexion->exec("CREATE TABLE IF NOT EXISTS solicitudes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id INT UNSIGNED NOT NULL,
        clave_envio CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        tipo_servicio VARCHAR(80) NOT NULL,
        zona VARCHAR(150) NOT NULL,
        descripcion TEXT NOT NULL,
        total_estimado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        estado ENUM('pendiente', 'en_proceso', 'terminada') NOT NULL DEFAULT 'pendiente',
        fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY solicitud_envio (usuario_id, clave_envio),
        KEY solicitudes_usuario_fecha (usuario_id, fecha_creacion),
        CONSTRAINT solicitudes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conexion->exec("CREATE TABLE IF NOT EXISTS solicitud_paquetes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        solicitud_id INT UNSIGNED NOT NULL,
        paquete_id INT UNSIGNED NOT NULL,
        codigo_paquete VARCHAR(60) NOT NULL,
        nombre_paquete VARCHAR(150) NOT NULL,
        cantidad TINYINT UNSIGNED NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY solicitud_codigo (solicitud_id, codigo_paquete),
        CONSTRAINT paquetes_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id),
        CONSTRAINT solicitud_catalogo FOREIGN KEY (paquete_id) REFERENCES paquetes(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Actualiza instalaciones anteriores manteniendo los nombres y precios históricos.
    $columnasDetalle = array_column($conexion->query('SHOW COLUMNS FROM solicitud_paquetes')->fetchAll(), null, 'Field');
    if (!isset($columnasDetalle['paquete_id'])) {
        $conexion->exec('ALTER TABLE solicitud_paquetes ADD COLUMN paquete_id INT UNSIGNED NULL AFTER solicitud_id');
    }
    $conexion->exec('UPDATE solicitud_paquetes sp JOIN paquetes p ON p.codigo = sp.codigo_paquete SET sp.paquete_id = p.id WHERE sp.paquete_id IS NULL');
    if ((int) $conexion->query('SELECT COUNT(*) FROM solicitud_paquetes WHERE paquete_id IS NULL')->fetchColumn() > 0) {
        throw new RuntimeException('Hay paquetes históricos sin un código en el catálogo. Agrégalos a paquetes y vuelve a ejecutar la migración.');
    }
    if (!isset($columnasDetalle['paquete_id']) || $columnasDetalle['paquete_id']['Null'] === 'YES') {
        $conexion->exec('ALTER TABLE solicitud_paquetes MODIFY COLUMN paquete_id INT UNSIGNED NOT NULL');
    }
    $relacion = $conexion->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'solicitud_paquetes'
        AND CONSTRAINT_NAME = 'solicitud_catalogo' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->fetchColumn();
    if ((int) $relacion === 0) {
        $conexion->exec('ALTER TABLE solicitud_paquetes ADD CONSTRAINT solicitud_catalogo FOREIGN KEY (paquete_id) REFERENCES paquetes(id)');
    }

    echo "Listo: catálogo de paquetes cargado y solicitudes vinculadas a sus paquetes.\n";
} catch (RuntimeException $e) {
    if (!($e instanceof PDOException)) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
    fwrite(STDERR, "No se pudo completar la migración. Revisa la conexión y los permisos de la base de datos.\n");
    exit(1);
}
