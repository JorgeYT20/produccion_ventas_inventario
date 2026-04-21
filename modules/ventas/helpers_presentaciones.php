<?php

function tablaProductoPreciosDisponible(mysqli $conexion): bool
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    try {
        $sql = "SELECT 1
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'producto_precios'
                LIMIT 1";
        $resultado = $conexion->query($sql);
        $cache = $resultado && $resultado->num_rows > 0;
    } catch (Throwable $e) {
        error_log('No se pudo verificar la tabla producto_precios: ' . $e->getMessage());
        $cache = false;
    }

    return $cache;
}

function obtenerPresentacionesPorProducto(mysqli $conexion, array $productoIds): array
{
    if (!tablaProductoPreciosDisponible($conexion)) {
        return [];
    }

    $productoIds = array_values(array_unique(array_map('intval', $productoIds)));
    $productoIds = array_filter($productoIds, fn($id) => $id > 0);

    if (empty($productoIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($productoIds), '?'));
    $types = str_repeat('i', count($productoIds));

    $sql = "SELECT id_precio, id_producto, tipo, cantidad, precio
            FROM producto_precios
            WHERE id_producto IN ($placeholders)
            ORDER BY cantidad ASC, id_precio ASC";

    try {
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception($conexion->error);
        }

        $stmt->bind_param($types, ...$productoIds);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $presentaciones = [];
        while ($fila = $resultado->fetch_assoc()) {
            $idProducto = (int)$fila['id_producto'];
            if (!isset($presentaciones[$idProducto])) {
                $presentaciones[$idProducto] = [];
            }

            $presentaciones[$idProducto][] = [
                'id_presentacion' => (int)$fila['id_precio'],
                'tipo' => $fila['tipo'],
                'cantidad' => (int)$fila['cantidad'],
                'precio' => (float)$fila['precio']
            ];
        }

        $stmt->close();
        return $presentaciones;
    } catch (Throwable $e) {
        error_log('Fallo al cargar presentaciones: ' . $e->getMessage());
        return [];
    }
}

function construirPresentacionesProducto(array $producto, array $presentacionesDb = []): array
{
    $presentaciones = [[
        'id_presentacion' => null,
        'tipo' => 'Unidad',
        'cantidad' => 1,
        'precio' => (float)$producto['precio_venta']
    ]];

    foreach ($presentacionesDb as $presentacion) {
        $presentaciones[] = [
            'id_presentacion' => (int)$presentacion['id_presentacion'],
            'tipo' => $presentacion['tipo'],
            'cantidad' => (int)$presentacion['cantidad'],
            'precio' => (float)$presentacion['precio']
        ];
    }

    return $presentaciones;
}

function anexarPresentacionesAProductos(mysqli $conexion, array $productos): array
{
    $productoIds = array_map(fn($producto) => (int)$producto['id_producto'], $productos);
    $presentacionesMap = obtenerPresentacionesPorProducto($conexion, $productoIds);

    foreach ($productos as &$producto) {
        $idProducto = (int)$producto['id_producto'];
        $presentacionesDb = $presentacionesMap[$idProducto] ?? [];
        $producto['presentaciones'] = construirPresentacionesProducto($producto, $presentacionesDb);
        $producto['tiene_presentaciones'] = !empty($presentacionesDb);
    }
    unset($producto);

    return $productos;
}

function resolverPresentacionVenta(mysqli $conexion, int $idProducto, ?int $idPresentacion): array
{
    $sqlProducto = "SELECT id_producto, nombre, precio_venta, stock
                    FROM productos
                    WHERE id_producto = ? AND activo = 1
                    LIMIT 1";
    $stmtProducto = $conexion->prepare($sqlProducto);
    $stmtProducto->bind_param('i', $idProducto);
    $stmtProducto->execute();
    $producto = $stmtProducto->get_result()->fetch_assoc();
    $stmtProducto->close();

    if (!$producto) {
        throw new Exception('El producto solicitado no existe o está inactivo.');
    }

    if ($idPresentacion === null || $idPresentacion <= 0 || !tablaProductoPreciosDisponible($conexion)) {
        return [
            'id_producto' => (int)$producto['id_producto'],
            'nombre' => $producto['nombre'],
            'tipo' => 'Unidad',
            'cantidad_unidades' => 1,
            'precio_presentacion' => (float)$producto['precio_venta'],
            'stock' => (int)$producto['stock']
        ];
    }

    $sqlPresentacion = "SELECT id_precio, tipo, cantidad, precio
                        FROM producto_precios
                        WHERE id_precio = ? AND id_producto = ?
                        LIMIT 1";
    $stmtPresentacion = $conexion->prepare($sqlPresentacion);
    $stmtPresentacion->bind_param('ii', $idPresentacion, $idProducto);
    $stmtPresentacion->execute();
    $presentacion = $stmtPresentacion->get_result()->fetch_assoc();
    $stmtPresentacion->close();

    if (!$presentacion) {
        throw new Exception('La presentación seleccionada no es válida para este producto.');
    }

    return [
        'id_producto' => (int)$producto['id_producto'],
        'nombre' => $producto['nombre'],
        'tipo' => $presentacion['tipo'],
        'cantidad_unidades' => (int)$presentacion['cantidad'],
        'precio_presentacion' => (float)$presentacion['precio'],
        'stock' => (int)$producto['stock']
    ];
}
