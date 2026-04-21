<?php

function tablaCombosDisponible(mysqli $conexion): bool
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    try {
        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('combos', 'combo_detalle')";
        $resultado = $conexion->query($sql);
        $fila = $resultado ? $resultado->fetch_assoc() : null;
        $cache = ((int)($fila['total'] ?? 0)) === 2;
    } catch (Throwable $e) {
        error_log('No se pudo verificar la estructura de combos: ' . $e->getMessage());
        $cache = false;
    }

    return $cache;
}

function obtenerCombosActivos(mysqli $conexion): array
{
    if (!tablaCombosDisponible($conexion)) {
        return [];
    }

    $sql = "SELECT c.id_combo, c.nombre, c.precio, c.activo,
                   cd.id_producto, cd.cantidad,
                   p.nombre AS producto_nombre
            FROM combos c
            INNER JOIN combo_detalle cd ON c.id_combo = cd.id_combo
            INNER JOIN productos p ON cd.id_producto = p.id_producto
            WHERE c.activo = 1
            ORDER BY c.nombre ASC, cd.id ASC";

    $resultado = $conexion->query($sql);
    if (!$resultado) {
        return [];
    }

    $combos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $idCombo = (int)$fila['id_combo'];
        if (!isset($combos[$idCombo])) {
            $combos[$idCombo] = [
                'id_combo' => $idCombo,
                'nombre' => $fila['nombre'],
                'precio' => (float)$fila['precio'],
                'activo' => (int)$fila['activo'],
                'productos' => []
            ];
        }

        $combos[$idCombo]['productos'][] = [
            'id_producto' => (int)$fila['id_producto'],
            'cantidad' => (int)$fila['cantidad'],
            'nombre' => $fila['producto_nombre']
        ];
    }

    return array_values($combos);
}

function obtenerComboPorId(mysqli $conexion, int $idCombo): ?array
{
    if (!tablaCombosDisponible($conexion) || $idCombo <= 0) {
        return null;
    }

    $sql = "SELECT c.id_combo, c.nombre, c.precio, c.activo,
                   cd.id_producto, cd.cantidad,
                   p.nombre AS producto_nombre,
                   p.precio_venta,
                   p.stock
            FROM combos c
            INNER JOIN combo_detalle cd ON c.id_combo = cd.id_combo
            INNER JOIN productos p ON cd.id_producto = p.id_producto
            WHERE c.id_combo = ?
            ORDER BY cd.id ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idCombo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $combo = null;
    while ($fila = $resultado->fetch_assoc()) {
        if ($combo === null) {
            $combo = [
                'id_combo' => (int)$fila['id_combo'],
                'nombre' => $fila['nombre'],
                'precio' => (float)$fila['precio'],
                'activo' => (int)$fila['activo'],
                'productos' => []
            ];
        }

        $combo['productos'][] = [
            'id_producto' => (int)$fila['id_producto'],
            'cantidad' => (int)$fila['cantidad'],
            'nombre' => $fila['producto_nombre'],
            'precio_venta' => (float)$fila['precio_venta'],
            'stock' => (int)$fila['stock']
        ];
    }

    $stmt->close();

    return $combo;
}
