<?php
require_once __DIR__ . '/../../config/database.php';

function tablaExisteCaja(mysqli $conexion, string $tabla): bool
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

if (!isset($_SESSION['id_usuario']) || !tienePermiso('caja_gestionar')) {
    redirigir('/mi_sistema/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_turno = (int)$_POST['id_turno'];
    $monto_final_real = (float)$_POST['monto_final_real'];
    $tieneVentaPagos = tablaExisteCaja($conexion, 'venta_pagos');

    $conexion->begin_transaction();
    try {
        // 1. Obtener datos del turno
        $stmt_turno = $conexion->prepare("SELECT monto_inicial, fecha_apertura FROM turnos_caja WHERE id_turno = ? AND estado = 'abierto' FOR UPDATE");
        $stmt_turno->bind_param("i", $id_turno);
        $stmt_turno->execute();
        $turno = $stmt_turno->get_result()->fetch_assoc();
        $monto_final_sistema = $turno['monto_inicial'];
        $fecha_apertura = $turno['fecha_apertura'];
        $stmt_turno->close();

        // 2. Sumar ventas en efectivo del turno
        if ($tieneVentaPagos) {
            $stmt_ventas = $conexion->prepare("SELECT COALESCE(SUM(vp.monto), 0) AS total
                                               FROM venta_pagos vp
                                               JOIN ventas v ON v.id_venta = vp.id_venta
                                               WHERE v.id_usuario = ?
                                                 AND v.fecha_venta >= ?
                                                 AND v.estado = 'completada'
                                                 AND LOWER(vp.metodo_pago) = 'efectivo'");
        } else {
            $stmt_ventas = $conexion->prepare("SELECT COALESCE(SUM(total_venta), 0) as total
                                               FROM ventas
                                               WHERE id_usuario = ?
                                                 AND metodo_pago = 'efectivo'
                                                 AND fecha_venta >= ?
                                                 AND estado = 'completada'");
        }
        $stmt_ventas->bind_param("is", $_SESSION['id_usuario'], $fecha_apertura);
        $stmt_ventas->execute();
        $ventas_efectivo = $stmt_ventas->get_result()->fetch_assoc()['total'] ?? 0;
        $monto_final_sistema += $ventas_efectivo;
        $stmt_ventas->close();

        // 3. Sumar/Restar movimientos de caja del turno
        $stmt_mov = $conexion->prepare("SELECT tipo_movimiento, SUM(monto) as total FROM movimientos_caja WHERE id_turno = ? GROUP BY tipo_movimiento");
        $stmt_mov->bind_param("i", $id_turno);
        $stmt_mov->execute();
        $movimientos = $stmt_mov->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($movimientos as $mov) {
            if ($mov['tipo_movimiento'] == 'ingreso') {
                $monto_final_sistema += $mov['total'];
            } else {
                $monto_final_sistema -= $mov['total'];
            }
        }
        $stmt_mov->close();

        // 4. Calcular diferencia y cerrar el turno
        $diferencia = $monto_final_real - $monto_final_sistema;
        $fecha_cierre = date('Y-m-d H:i:s');
        
        $stmt_update = $conexion->prepare("UPDATE turnos_caja SET fecha_cierre = ?, monto_final_sistema = ?, monto_final_real = ?, diferencia = ?, estado = 'cerrado' WHERE id_turno = ?");
        $stmt_update->bind_param("sdddi", $fecha_cierre, $monto_final_sistema, $monto_final_real, $diferencia, $id_turno);
        $stmt_update->execute();
        
        $conexion->commit();

        // --- SECCIÓN MODIFICADA: Mensaje final con moneda dinámica ---
        $moneda = getMoneda();
        $_SESSION['mensaje'] = "<strong>Turno cerrado con éxito.</strong><br>
                                Monto esperado por el sistema: " . $moneda . number_format($monto_final_sistema, 2) . "<br>
                                Monto contado en caja: " . $moneda . number_format($monto_final_real, 2) . "<br>
                                <strong>Diferencia: " . $moneda . number_format($diferencia, 2) . "</strong>";
        $_SESSION['mensaje_tipo'] = ($diferencia == 0) ? 'success' : (($diferencia > 0) ? 'warning' : 'danger');

    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['mensaje'] = 'Error al cerrar la caja: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
    redirigir('index.php');
}
