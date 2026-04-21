<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

function tablaExisteArqueo(mysqli $conexion, string $tabla): bool
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

function expresionMetodoNormalizadoArqueo(string $campo): string
{
    return "REPLACE(REPLACE(REPLACE(REPLACE(LOWER($campo), '_', ''), '/', ''), '-', ''), ' ', '')";
}

if (!isset($_SESSION['usuario']) && function_exists('usuarioActual')) {
    $usuarioSesion = usuarioActual();
    if ($usuarioSesion) {
        $_SESSION['usuario'] = $usuarioSesion;
    }
}

if (!isset($_SESSION['usuario']) || !in_array((int)($_SESSION['usuario']['id_rol'] ?? 0), [1, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

$idTurno = isset($_GET['id_turno']) ? (int)$_GET['id_turno'] : 0;
if ($idTurno <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Turno no válido']);
    exit;
}

try {
    $tieneVentaPagos = tablaExisteArqueo($conexion, 'venta_pagos');

    $stmtTurno = $conexion->prepare("SELECT t.id_turno, t.fecha_apertura, t.fecha_cierre, t.estado, u.nombre_completo
                                     FROM turnos_caja t
                                     JOIN usuarios u ON u.id_usuario = t.id_usuario
                                     WHERE t.id_turno = ?
                                     LIMIT 1");
    $stmtTurno->bind_param('i', $idTurno);
    $stmtTurno->execute();
    $turno = $stmtTurno->get_result()->fetch_assoc();
    $stmtTurno->close();

    if (!$turno) {
        throw new Exception('No se encontró el arqueo solicitado.');
    }

    if ($tieneVentaPagos) {
        $metodoPagoNormalizado = expresionMetodoNormalizadoArqueo('vp.metodo_pago');
        $stmtResumenVentas = $conexion->prepare("SELECT
                                                    COUNT(*) AS numero_ventas,
                                                    COALESCE((
                                                        SELECT SUM(vp.monto)
                                                        FROM venta_pagos vp
                                                        JOIN ventas vpe ON vpe.id_venta = vp.id_venta
                                                        WHERE vpe.id_arqueo = ?
                                                          AND vpe.estado = 'completada'
                                                          AND $metodoPagoNormalizado = 'efectivo'
                                                    ), 0) AS total_efectivo,
                                                    COALESCE((
                                                        SELECT SUM(vp.monto)
                                                        FROM venta_pagos vp
                                                        JOIN ventas vpd ON vpd.id_venta = vp.id_venta
                                                        WHERE vpd.id_arqueo = ?
                                                          AND vpd.estado = 'completada'
                                                          AND $metodoPagoNormalizado IN ('yape', 'plin', 'yapeplin', 'transferencia', 'tarjeta', 'tarjetacredito', 'tarjetadebito')
                                                    ), 0) AS total_digital,
                                                    COALESCE(SUM(total_venta), 0) AS total_general
                                                 FROM ventas
                                                 WHERE id_arqueo = ? AND estado = 'completada'");
        $stmtResumenVentas->bind_param('iii', $idTurno, $idTurno, $idTurno);
    } else {
        $metodoPagoNormalizado = expresionMetodoNormalizadoArqueo('metodo_pago');
        $stmtResumenVentas = $conexion->prepare("SELECT
                                                    COUNT(*) AS numero_ventas,
                                                    COALESCE(SUM(CASE WHEN $metodoPagoNormalizado = 'efectivo' THEN total_venta ELSE 0 END), 0) AS total_efectivo,
                                                    COALESCE(SUM(CASE WHEN $metodoPagoNormalizado IN ('yape', 'plin', 'yapeplin', 'transferencia', 'tarjeta', 'tarjetacredito', 'tarjetadebito') THEN total_venta ELSE 0 END), 0) AS total_digital,
                                                    COALESCE(SUM(total_venta), 0) AS total_general
                                                 FROM ventas
                                                 WHERE id_arqueo = ? AND estado = 'completada'");
        $stmtResumenVentas->bind_param('i', $idTurno);
    }
    $stmtResumenVentas->execute();
    $resumenVentas = $stmtResumenVentas->get_result()->fetch_assoc();
    $stmtResumenVentas->close();

    $stmtVentas = $conexion->prepare("SELECT id_venta, fecha_venta, metodo_pago, total_venta
                                      FROM ventas
                                      WHERE id_arqueo = ? AND estado = 'completada'
                                      ORDER BY fecha_venta DESC, id_venta DESC");
    $stmtVentas->bind_param('i', $idTurno);
    $stmtVentas->execute();
    $ventas = $stmtVentas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtVentas->close();

    $stmtProductos = $conexion->prepare("SELECT
                                            p.nombre,
                                            COALESCE(SUM(dv.cantidad), 0) AS cantidad_vendida
                                         FROM detalle_ventas dv
                                         JOIN ventas v ON v.id_venta = dv.id_venta
                                         JOIN productos p ON p.id_producto = dv.id_producto
                                         WHERE v.id_arqueo = ? AND v.estado = 'completada'
                                         GROUP BY dv.id_producto, p.nombre
                                         ORDER BY cantidad_vendida DESC, p.nombre ASC
                                         LIMIT 10");
    $stmtProductos->bind_param('i', $idTurno);
    $stmtProductos->execute();
    $productos = $stmtProductos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtProductos->close();

    echo json_encode([
        'success' => true,
        'turno' => $turno,
        'resumen' => $resumenVentas,
        'ventas' => $ventas,
        'productos' => $productos
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo obtener el detalle del arqueo.',
        'debug' => $e->getMessage()
    ]);
}

$conexion->close();
