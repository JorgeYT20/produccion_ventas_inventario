<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario']) || (!tienePermiso('ventas_ver_listado') && !usuarioTieneRol([1, 2]))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

function columnaExisteAuditoria(mysqli $conexion, string $tabla, string $columna): bool
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

$idVenta = isset($_GET['id_venta']) ? (int)$_GET['id_venta'] : 0;

if ($idVenta <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Venta no válida']);
    exit;
}

try {
    $tieneAuditoria = columnaExisteAuditoria($conexion, 'detalle_ventas', 'tipo_item')
        && columnaExisteAuditoria($conexion, 'detalle_ventas', 'descripcion_item')
        && columnaExisteAuditoria($conexion, 'detalle_ventas', 'referencia_combo')
        && columnaExisteAuditoria($conexion, 'detalle_ventas', 'tipo_presentacion_audit')
        && columnaExisteAuditoria($conexion, 'detalle_ventas', 'cantidad_presentaciones_audit');

    if ($tieneAuditoria) {
        $sql = "SELECT d.id_producto,
                       d.cantidad,
                       d.precio_unitario,
                       d.descuento,
                       d.tipo_item,
                       d.tipo_presentacion_audit,
                       d.descripcion_item,
                       d.referencia_combo,
                       d.cantidad_presentaciones_audit,
                       p.nombre AS producto_nombre
                FROM detalle_ventas d
                JOIN productos p ON p.id_producto = d.id_producto
                WHERE d.id_venta = ?
                ORDER BY d.id_producto ASC, d.cantidad DESC";
    } else {
        $sql = "SELECT d.id_producto,
                       d.cantidad,
                       d.precio_unitario,
                       d.descuento,
                       'producto' AS tipo_item,
                       'Unidad' AS tipo_presentacion_audit,
                       p.nombre AS descripcion_item,
                       NULL AS referencia_combo,
                       d.cantidad AS cantidad_presentaciones_audit,
                       p.nombre AS producto_nombre
                FROM detalle_ventas d
                JOIN productos p ON p.id_producto = d.id_producto
                WHERE d.id_venta = ?
                ORDER BY d.id_producto ASC, d.cantidad DESC";
    }

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idVenta);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $items = [];
    $combosAgrupados = [];

    while ($fila = $resultado->fetch_assoc()) {
        $subtotalBruto = ((float)$fila['cantidad'] * (float)$fila['precio_unitario']);
        $subtotalNeto = max(0, $subtotalBruto - (float)$fila['descuento']);
        $tipoItem = $fila['tipo_item'] ?? 'producto';
        $referenciaCombo = $fila['referencia_combo'] !== null ? (int)$fila['referencia_combo'] : null;

        if ($tipoItem === 'combo' && $referenciaCombo) {
            $claveCombo = $referenciaCombo . '|' . ($fila['descripcion_item'] ?? 'Combo');

            if (!isset($combosAgrupados[$claveCombo])) {
                $combosAgrupados[$claveCombo] = [
                    'tipo_item' => 'combo',
                    'nombre' => $fila['descripcion_item'] ?: 'Combo',
                    'cantidad' => (int)($fila['cantidad_presentaciones_audit'] ?: 1),
                    'precio_unitario' => 0,
                    'subtotal' => 0,
                    'descuento' => 0,
                    'componentes' => []
                ];
            }

            $combosAgrupados[$claveCombo]['subtotal'] += $subtotalNeto;
            $combosAgrupados[$claveCombo]['descuento'] += (float)$fila['descuento'];
            $combosAgrupados[$claveCombo]['componentes'][] = [
                'producto' => $fila['producto_nombre'],
                'cantidad' => (int)$fila['cantidad']
            ];

            continue;
        }

        $items[] = [
            'tipo_item' => 'producto',
            'nombre' => $fila['descripcion_item'] ?: $fila['producto_nombre'],
            'cantidad' => (int)($fila['cantidad_presentaciones_audit'] ?: $fila['cantidad']),
            'precio_unitario' => round((float)$fila['precio_unitario'] * max(1, (int)($fila['cantidad'] / max(1, (int)($fila['cantidad_presentaciones_audit'] ?: 1)))), 2),
            'subtotal' => $subtotalNeto,
            'descuento' => (float)$fila['descuento'],
            'detalle' => $fila['tipo_presentacion_audit'] ?: 'Unidad'
        ];
    }

    foreach ($combosAgrupados as &$combo) {
        $combo['subtotal'] = round($combo['subtotal'], 2);
        $combo['descuento'] = round($combo['descuento'], 2);
        $combo['precio_unitario'] = $combo['cantidad'] > 0 ? round($combo['subtotal'] / $combo['cantidad'], 2) : 0;
    }
    unset($combo);

    $items = array_merge(array_values($combosAgrupados), $items);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo cargar el detalle de la venta.',
        'debug' => $e->getMessage()
    ]);
}

$conexion->close();
