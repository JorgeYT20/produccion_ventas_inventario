<?php

function reporteVentasColumnaExiste(mysqli $conexion, string $tabla, string $columna): bool
{
    static $cache = [];
    $cacheKey = $tabla . '.' . $columna;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $stmt = $conexion->prepare("SELECT 1
                                FROM information_schema.columns
                                WHERE table_schema = DATABASE()
                                  AND table_name = ?
                                  AND column_name = ?
                                LIMIT 1");
    $stmt->bind_param('ss', $tabla, $columna);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cache[$cacheKey] = $resultado && $resultado->num_rows > 0;
    $stmt->close();

    return $cache[$cacheKey];
}

function reporteVentasTablaExiste(mysqli $conexion, string $tabla): bool
{
    static $cache = [];

    if (array_key_exists($tabla, $cache)) {
        return $cache[$tabla];
    }

    $stmt = $conexion->prepare("SELECT 1
                                FROM information_schema.tables
                                WHERE table_schema = DATABASE()
                                  AND table_name = ?
                                LIMIT 1");
    $stmt->bind_param('s', $tabla);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cache[$tabla] = $resultado && $resultado->num_rows > 0;
    $stmt->close();

    return $cache[$tabla];
}

function limpiarFechaReporte(?string $valor, string $final = '00:00:00'): ?string
{
    if (!$valor) {
        return null;
    }

    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    if (!$fecha) {
        return null;
    }

    return $fecha->format('Y-m-d') . ' ' . $final;
}

function obtenerFiltrosReporteVentas(array $input): array
{
    $fechaInicio = limpiarFechaReporte($input['fecha_inicio'] ?? date('Y-m-01'));
    $fechaFin = limpiarFechaReporte($input['fecha_fin'] ?? date('Y-m-t'), '23:59:59');

    return [
        'fecha_inicio' => substr((string)($input['fecha_inicio'] ?? date('Y-m-01')), 0, 10),
        'fecha_fin' => substr((string)($input['fecha_fin'] ?? date('Y-m-t')), 0, 10),
        'fecha_inicio_sql' => $fechaInicio ?: date('Y-m-01 00:00:00'),
        'fecha_fin_sql' => $fechaFin ?: date('Y-m-t 23:59:59'),
        'id_usuario' => isset($input['id_usuario']) ? max(0, (int)$input['id_usuario']) : 0,
        'metodo_pago' => trim((string)($input['metodo_pago'] ?? '')),
        'id_arqueo' => isset($input['id_arqueo']) ? max(0, (int)$input['id_arqueo']) : 0
    ];
}

function construirWhereReporteVentas(array $filtros, bool $tieneArqueo, bool $tieneVentaPagos): array
{
    $condiciones = ["v.estado = 'completada'", 'v.fecha_venta BETWEEN ? AND ?'];
    $types = 'ss';
    $params = [$filtros['fecha_inicio_sql'], $filtros['fecha_fin_sql']];

    if (!empty($filtros['id_usuario'])) {
        $condiciones[] = 'v.id_usuario = ?';
        $types .= 'i';
        $params[] = $filtros['id_usuario'];
    }

    if (!empty($filtros['metodo_pago'])) {
        if ($tieneVentaPagos) {
            $condiciones[] = 'EXISTS (
                SELECT 1
                FROM venta_pagos vp_filter
                WHERE vp_filter.id_venta = v.id_venta
                  AND LOWER(vp_filter.metodo_pago) = LOWER(?)
            )';
        } else {
            $condiciones[] = 'LOWER(v.metodo_pago) = LOWER(?)';
        }
        $types .= 's';
        $params[] = $filtros['metodo_pago'];
    }

    if ($tieneArqueo && !empty($filtros['id_arqueo'])) {
        $condiciones[] = 'v.id_arqueo = ?';
        $types .= 'i';
        $params[] = $filtros['id_arqueo'];
    }

    return [
        'sql' => ' WHERE ' . implode(' AND ', $condiciones),
        'types' => $types,
        'params' => $params
    ];
}

function ejecutarConsultaReporte(mysqli $conexion, string $sql, string $types = '', array $params = []): mysqli_result
{
    $stmt = $conexion->prepare($sql);
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return $resultado;
}

function obtenerOpcionesReporteVentas(mysqli $conexion, bool $tieneArqueo): array
{
    $cajeros = $conexion->query("SELECT id_usuario, nombre_completo FROM usuarios WHERE activo = 1 ORDER BY nombre_completo ASC")->fetch_all(MYSQLI_ASSOC);
    $turnos = [];

    if ($tieneArqueo) {
        $turnos = $conexion->query("SELECT t.id_turno, t.fecha_apertura, t.fecha_cierre, u.nombre_completo
                                    FROM turnos_caja t
                                    JOIN usuarios u ON u.id_usuario = t.id_usuario
                                    ORDER BY t.fecha_apertura DESC
                                    LIMIT 100")->fetch_all(MYSQLI_ASSOC);
    }

    return [
        'cajeros' => $cajeros,
        'turnos' => $turnos,
        'metodos_pago' => [
            'efectivo' => 'Efectivo',
            'yape_plin' => 'Yape / Plin',
            'transferencia' => 'Transferencia',
            'tarjeta_credito' => 'Tarjeta'
        ]
    ];
}

function obtenerDatosReporteVentas(mysqli $conexion, array $filtros): array
{
    $tieneArqueo = reporteVentasColumnaExiste($conexion, 'ventas', 'id_arqueo');
    $tieneVentaPagos = reporteVentasTablaExiste($conexion, 'venta_pagos');
    $where = construirWhereReporteVentas($filtros, $tieneArqueo, $tieneVentaPagos);

    $sqlResumen = "SELECT
                        COALESCE(SUM(v.total_venta), 0) AS total_ingresos,
                        COUNT(*) AS numero_ventas,
                        COALESCE(AVG(v.total_venta), 0) AS ticket_promedio,
                        COALESCE(MAX(v.total_venta), 0) AS venta_maxima,
                        COALESCE(MIN(v.total_venta), 0) AS venta_minima
                   FROM ventas v" . $where['sql'];
    $resumen = ejecutarConsultaReporte($conexion, $sqlResumen, $where['types'], $where['params'])->fetch_assoc();

    if ($tieneVentaPagos) {
        $sqlMetodos = "SELECT
                            vp.metodo_pago,
                            COUNT(DISTINCT v.id_venta) AS numero_ventas,
                            COALESCE(SUM(vp.monto), 0) AS total
                       FROM ventas v
                       JOIN venta_pagos vp ON vp.id_venta = v.id_venta" . $where['sql'] . "
                       GROUP BY vp.metodo_pago
                       ORDER BY total DESC";
    } else {
        $sqlMetodos = "SELECT
                            v.metodo_pago,
                            COUNT(*) AS numero_ventas,
                            COALESCE(SUM(v.total_venta), 0) AS total
                       FROM ventas v" . $where['sql'] . "
                       GROUP BY v.metodo_pago
                       ORDER BY total DESC";
    }
    $metodosPago = ejecutarConsultaReporte($conexion, $sqlMetodos, $where['types'], $where['params'])->fetch_all(MYSQLI_ASSOC);

    $sqlTurnos = $tieneArqueo
        ? "SELECT
                COALESCE(v.id_arqueo, 0) AS id_arqueo,
                COUNT(*) AS numero_ventas,
                COALESCE(SUM(v.total_venta), 0) AS total_ventas
           FROM ventas v" . $where['sql'] . "
           GROUP BY COALESCE(v.id_arqueo, 0)
           ORDER BY total_ventas DESC"
        : null;
    $ventasTurno = $tieneArqueo ? ejecutarConsultaReporte($conexion, $sqlTurnos, $where['types'], $where['params'])->fetch_all(MYSQLI_ASSOC) : [];

    $sqlCajeros = "SELECT
                        u.nombre_completo AS cajero,
                        COUNT(*) AS numero_ventas,
                        COALESCE(SUM(v.total_venta), 0) AS total_vendido
                   FROM ventas v
                   JOIN usuarios u ON u.id_usuario = v.id_usuario" . $where['sql'] . "
                   GROUP BY v.id_usuario, u.nombre_completo
                   ORDER BY total_vendido DESC";
    $ventasCajero = ejecutarConsultaReporte($conexion, $sqlCajeros, $where['types'], $where['params'])->fetch_all(MYSQLI_ASSOC);

    $sqlTopProductos = "SELECT
                            p.nombre AS producto,
                            COALESCE(SUM(dv.cantidad), 0) AS cantidad_vendida,
                            COALESCE(SUM((dv.cantidad * dv.precio_unitario) - COALESCE(dv.descuento, 0)), 0) AS total_generado
                        FROM detalle_ventas dv
                        JOIN ventas v ON v.id_venta = dv.id_venta
                        JOIN productos p ON p.id_producto = dv.id_producto" . $where['sql'] . "
                        GROUP BY dv.id_producto, p.nombre
                        ORDER BY cantidad_vendida DESC, total_generado DESC
                        LIMIT 10";
    $topProductos = ejecutarConsultaReporte($conexion, $sqlTopProductos, $where['types'], $where['params'])->fetch_all(MYSQLI_ASSOC);

    $sqlDetalleVentas = "SELECT
                            v.fecha_venta,
                            v.id_venta,
                            u.nombre_completo AS cajero,
                            v.metodo_pago,
                            v.total_venta,
                            v.estado,
                            " . ($tieneArqueo ? "v.id_arqueo" : "NULL") . " AS id_arqueo
                         FROM ventas v
                         JOIN usuarios u ON u.id_usuario = v.id_usuario" . $where['sql'] . "
                         ORDER BY v.fecha_venta DESC, v.id_venta DESC";
    $detalleVentas = ejecutarConsultaReporte($conexion, $sqlDetalleVentas, $where['types'], $where['params'])->fetch_all(MYSQLI_ASSOC);

    return [
        'tiene_arqueo' => $tieneArqueo,
        'tiene_venta_pagos' => $tieneVentaPagos,
        'filtros' => $filtros,
        'resumen' => $resumen,
        'metodos_pago' => $metodosPago,
        'ventas_turno' => $ventasTurno,
        'ventas_cajero' => $ventasCajero,
        'top_productos' => $topProductos,
        'detalle_ventas' => $detalleVentas,
        'opciones' => obtenerOpcionesReporteVentas($conexion, $tieneArqueo)
    ];
}

function etiquetaMetodoPagoReporte(string $metodo): string
{
    return match (strtolower($metodo)) {
        'efectivo' => 'Efectivo',
        'yape', 'plin', 'yape_plin', 'yape/plin' => 'Yape / Plin',
        'transferencia' => 'Transferencia',
        'tarjeta_credito', 'tarjeta' => 'Tarjeta',
        'mixto' => 'Pago mixto',
        default => ucfirst(str_replace('_', ' ', $metodo))
    };
}

function porcentajeReporte(float $valor, float $total): float
{
    if ($total <= 0) {
        return 0;
    }

    return round(($valor / $total) * 100, 2);
}
