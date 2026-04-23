<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/helpers_presentaciones.php';
require_once __DIR__ . '/../combos/helpers_combos.php';
header('Content-Type: application/json');

function columnaExisteEnTabla(mysqli $conexion, string $tabla, string $columna): bool
{
    static $cache = [];
    $cacheKey = $tabla . '.' . $columna;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $sql = "SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND column_name = ?
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ss', $tabla, $columna);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cache[$cacheKey] = $resultado && $resultado->num_rows > 0;
    $stmt->close();

    return $cache[$cacheKey];
}

function tablaExiste(mysqli $conexion, string $tabla): bool
{
    static $cache = [];

    if (array_key_exists($tabla, $cache)) {
        return $cache[$tabla];
    }

    $sql = "SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $tabla);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cache[$tabla] = $resultado && $resultado->num_rows > 0;
    $stmt->close();

    return $cache[$tabla];
}

function normalizarMetodoPagoVenta(string $metodo): string
{
    $metodo = strtolower(trim($metodo));
    $metodoCompacto = str_replace(['_', '-', '/', ' '], '', $metodo);

    return match ($metodoCompacto) {
        'cash', 'efectivo' => 'efectivo',
        'yape', 'plin', 'yapeplin' => 'yape_plin',
        'transferencia', 'transferenciabancaria' => 'transferencia',
        'tarjeta', 'tarjetacredito', 'creditcard' => 'tarjeta_credito',
        'tarjetadebito', 'debitcard' => 'tarjeta_debito',
        'mixto' => 'mixto',
        default => $metodo !== '' ? $metodo : 'efectivo'
    };
}

if (!isset($_SESSION['id_usuario']) || !tienePermiso('ventas_crear')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_cliente = !empty($data['id_cliente']) ? (int)$data['id_cliente'] : null;
$metodo_pago = normalizarMetodoPagoVenta((string)($data['metodo_pago'] ?? 'efectivo'));
$pagos = is_array($data['pagos'] ?? null) ? $data['pagos'] : [];
$carrito = $data['carrito'] ?? [];
$descuento_global = isset($data['descuento_global']) ? (float)$data['descuento_global'] : 0;
$descuento_global = max(0, $descuento_global);
$subtotal_venta = 0;

if (empty($carrito)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El carrito está vacío.']);
    exit;
}

$conexion->begin_transaction();

try {
    $detalle_productos = [];
    $cantidadesPorProducto = [];
    $ventasTieneArqueo = columnaExisteEnTabla($conexion, 'ventas', 'id_arqueo');
    $detalleTieneAuditoria = columnaExisteEnTabla($conexion, 'detalle_ventas', 'tipo_item')
        && columnaExisteEnTabla($conexion, 'detalle_ventas', 'descripcion_item')
        && columnaExisteEnTabla($conexion, 'detalle_ventas', 'referencia_combo')
        && columnaExisteEnTabla($conexion, 'detalle_ventas', 'tipo_presentacion_audit')
        && columnaExisteEnTabla($conexion, 'detalle_ventas', 'cantidad_presentaciones_audit');
    $ventaPagosDisponible = tablaExiste($conexion, 'venta_pagos');
    $id_arqueo = null;

    if ($ventasTieneArqueo) {
        $stmt_arqueo = $conexion->prepare("SELECT id_turno FROM turnos_caja WHERE id_usuario = ? AND estado = 'abierto' LIMIT 1");
        $stmt_arqueo->bind_param('i', $_SESSION['id_usuario']);
        $stmt_arqueo->execute();
        $arqueoActivo = $stmt_arqueo->get_result()->fetch_assoc();
        $stmt_arqueo->close();

        if (!$arqueoActivo) {
            throw new Exception('No tienes un turno de caja abierto. Abre caja antes de registrar una venta.');
        }

        $id_arqueo = (int)$arqueoActivo['id_turno'];
    }

    foreach ($carrito as $item) {
        $tipo_item = $item['tipo_item'] ?? ($item['tipo'] ?? 'producto');

        if ($tipo_item === 'combo') {
            $id_combo = isset($item['id_combo']) ? (int)$item['id_combo'] : 0;
            if ($id_combo <= 0 && isset($item['id']) && is_string($item['id']) && preg_match('/^combo_(\d+)$/', $item['id'], $matches)) {
                $id_combo = (int)$matches[1];
            }
            $cantidad_combos = isset($item['cantidad']) ? (int)$item['cantidad'] : 0;

            if ($id_combo <= 0 || $cantidad_combos <= 0) {
                continue;
            }

            $combo = obtenerComboPorId($conexion, $id_combo);
            if (!$combo || !$combo['activo'] || empty($combo['productos'])) {
                throw new Exception('El combo seleccionado no es válido.');
            }

            $base_total_combo = 0;
            foreach ($combo['productos'] as $productoCombo) {
                $base_total_combo += $productoCombo['precio_venta'] * $productoCombo['cantidad'] * $cantidad_combos;
            }

            $subtotal_combo = (float)$combo['precio'] * $cantidad_combos;
            $subtotal_venta += $subtotal_combo;
            $restante_combo = round($subtotal_combo, 2);
            $ultima_posicion_combo = count($combo['productos']) - 1;

            foreach ($combo['productos'] as $indexCombo => $productoCombo) {
                $cantidad_real = (int)$productoCombo['cantidad'] * $cantidad_combos;
                $cantidadesPorProducto[$productoCombo['id_producto']] = ($cantidadesPorProducto[$productoCombo['id_producto']] ?? 0) + $cantidad_real;

                $subtotal_base_linea = $productoCombo['precio_venta'] * $cantidad_real;

                if ($indexCombo === $ultima_posicion_combo) {
                    $subtotal_linea = $restante_combo;
                } else {
                    $subtotal_linea = $base_total_combo > 0
                        ? round(($subtotal_base_linea / $base_total_combo) * $subtotal_combo, 2)
                        : 0;
                    $restante_combo = round($restante_combo - $subtotal_linea, 2);
                }

                $precio_unitario = $cantidad_real > 0 ? ($subtotal_linea / $cantidad_real) : 0;

                $detalle_productos[] = [
                    'id_producto' => (int)$productoCombo['id_producto'],
                    'nombre' => $productoCombo['nombre'],
                    'descripcion_item' => $combo['nombre'] . ' 🍹',
                    'tipo_item' => 'combo',
                    'tipo_presentacion' => 'COMBO',
                    'referencia_combo' => $id_combo,
                    'cantidad' => $cantidad_real,
                    'cantidad_presentaciones' => $cantidad_combos,
                    'cantidad_unidades' => (int)$productoCombo['cantidad'],
                    'precio_unitario' => $precio_unitario,
                    'subtotal' => $subtotal_linea,
                    'descuento' => 0,
                    'stock_actual' => (int)$productoCombo['stock']
                ];
            }

            continue;
        }

        $id_producto = isset($item['id_producto']) ? (int)$item['id_producto'] : (isset($item['id']) ? (int)$item['id'] : 0);
        $id_presentacion = isset($item['id_presentacion']) && $item['id_presentacion'] !== '' ? (int)$item['id_presentacion'] : null;
        $precio_unitario_enviado = isset($item['precio_unitario']) ? (float)$item['precio_unitario'] : null;
        $cantidad_presentaciones = isset($item['cantidad_presentaciones']) ? (int)$item['cantidad_presentaciones'] : (isset($item['cantidad']) ? (int)$item['cantidad'] : 0);
        $cantidad_real_enviada = isset($item['cantidad_real']) ? (int)$item['cantidad_real'] : (isset($item['cantidad']) ? (int)$item['cantidad'] : 0);

        if ($id_producto <= 0 || $cantidad_presentaciones <= 0) {
            continue;
        }

        $presentacion = resolverPresentacionVenta($conexion, $id_producto, $id_presentacion);
        $cantidad_unidades = (int)$presentacion['cantidad_unidades'];
        $cantidad_real = $cantidad_presentaciones * $cantidad_unidades;

        if ($cantidad_real !== $cantidad_real_enviada) {
            throw new Exception('La cantidad enviada no coincide con la presentación seleccionada.');
        }

        $precio_presentacion = (float)$presentacion['precio_presentacion'];
        $precio_unitario = $precio_presentacion / $cantidad_unidades;

        if ($cantidad_unidades === 1 && ($id_presentacion === null || $id_presentacion <= 0) && $precio_unitario_enviado !== null && $precio_unitario_enviado > 0) {
            $precio_unitario = $precio_unitario_enviado;
            $precio_presentacion = $precio_unitario_enviado;
        }

        $subtotal_item = $precio_presentacion * $cantidad_presentaciones;
        $subtotal_venta += $subtotal_item;

        $cantidadesPorProducto[$id_producto] = ($cantidadesPorProducto[$id_producto] ?? 0) + $cantidad_real;

        $detalle_productos[] = [
            'id_producto' => $id_producto,
            'nombre' => $presentacion['nombre'],
            'descripcion_item' => $presentacion['nombre'] . ' (' . $presentacion['tipo'] . ')',
            'tipo_item' => 'producto',
            'tipo_presentacion' => $presentacion['tipo'],
            'referencia_combo' => null,
            'cantidad' => $cantidad_real,
            'cantidad_presentaciones' => $cantidad_presentaciones,
            'cantidad_unidades' => $cantidad_unidades,
            'precio_unitario' => $precio_unitario,
            'subtotal' => $subtotal_item,
            'descuento' => 0,
            'stock_actual' => (int)$presentacion['stock']
        ];
    }

    if (empty($detalle_productos)) {
        throw new Exception('No se encontraron productos válidos para la venta.');
    }

    foreach ($cantidadesPorProducto as $idProducto => $cantidadSolicitada) {
        $stockDisponible = null;
        $nombreProducto = 'Producto';

        foreach ($detalle_productos as $detalle) {
            if ((int)$detalle['id_producto'] === (int)$idProducto) {
                $stockDisponible = (int)$detalle['stock_actual'];
                $nombreProducto = $detalle['nombre'];
                break;
            }
        }

        if ($stockDisponible === null) {
            throw new Exception('No se pudo validar el stock del producto ID ' . $idProducto);
        }

        if ($cantidadSolicitada > $stockDisponible) {
            throw new Exception('Stock insuficiente para el producto "' . $nombreProducto . '". Disponible: ' . $stockDisponible);
        }
    }

    $descuento_global = min($descuento_global, $subtotal_venta);
    $total_venta = max(0, $subtotal_venta - $descuento_global);
    $total_venta_redondeado = round($total_venta, 2);
    $pagosNormalizados = [];

    if (!empty($pagos)) {
        foreach ($pagos as $pago) {
            if (!is_array($pago)) {
                throw new Exception('El formato de pagos enviado no es valido.');
            }

            $metodoPagoItem = normalizarMetodoPagoVenta((string)($pago['metodo'] ?? ''));
            $montoPago = round((float)($pago['monto'] ?? 0), 2);

            if ($metodoPagoItem === '') {
                throw new Exception('Todos los pagos deben indicar un metodo.');
            }

            if ($montoPago <= 0) {
                throw new Exception('Todos los pagos deben tener un monto mayor a cero.');
            }

            $pagosNormalizados[] = [
                'metodo' => $metodoPagoItem,
                'monto' => $montoPago
            ];
        }

        if (empty($pagosNormalizados)) {
            throw new Exception('No se recibieron pagos validos para la venta.');
        }

        $totalPagos = round(array_sum(array_column($pagosNormalizados, 'monto')), 2);
        if ($totalPagos + 0.01 < $total_venta_redondeado) {
            throw new Exception('La suma de los pagos no cubre el total de la venta.');
        }

        $montoRestante = $total_venta_redondeado;
        $pagosAplicados = [];

        foreach ($pagosNormalizados as $pago) {
            if ($montoRestante <= 0) {
                break;
            }

            $montoAplicado = round(min($pago['monto'], $montoRestante), 2);
            if ($montoAplicado <= 0) {
                continue;
            }

            $pagosAplicados[] = [
                'metodo' => $pago['metodo'],
                'monto' => $montoAplicado
            ];
            $montoRestante = round($montoRestante - $montoAplicado, 2);
        }

        if ($montoRestante > 0.01 || empty($pagosAplicados)) {
            throw new Exception('No se pudo distribuir el pago sobre el total de la venta.');
        }

        $pagosNormalizados = $pagosAplicados;
        $metodo_pago = count($pagosNormalizados) > 1
            ? 'mixto'
            : $pagosNormalizados[0]['metodo'];
    } else {
        $pagosNormalizados[] = [
            'metodo' => $metodo_pago ?: 'efectivo',
            'monto' => $total_venta_redondeado
        ];
        $metodo_pago = $pagosNormalizados[0]['metodo'];
    }

    $descuento_restante = round($descuento_global, 2);
    $ultima_posicion = count($detalle_productos) - 1;

    foreach ($detalle_productos as $index => &$detalle) {
        if ($descuento_global <= 0) {
            $detalle['descuento'] = 0;
            continue;
        }

        if ($index === $ultima_posicion) {
            $detalle['descuento'] = min($descuento_restante, $detalle['subtotal']);
            continue;
        }

        $descuento_item = round(($detalle['subtotal'] / $subtotal_venta) * $descuento_global, 2);
        $descuento_item = min($descuento_item, $detalle['subtotal']);
        $detalle['descuento'] = $descuento_item;
        $descuento_restante = round($descuento_restante - $descuento_item, 2);
    }
    unset($detalle);

    if ($ventasTieneArqueo) {
        $stmt_venta = $conexion->prepare("INSERT INTO ventas (id_cliente, id_usuario, id_arqueo, total_venta, metodo_pago) VALUES (?, ?, ?, ?, ?)");
        $stmt_venta->bind_param("iiids", $id_cliente, $_SESSION['id_usuario'], $id_arqueo, $total_venta, $metodo_pago);
    } else {
        $stmt_venta = $conexion->prepare("INSERT INTO ventas (id_cliente, id_usuario, total_venta, metodo_pago) VALUES (?, ?, ?, ?)");
        $stmt_venta->bind_param("iids", $id_cliente, $_SESSION['id_usuario'], $total_venta, $metodo_pago);
    }
    $stmt_venta->execute();
    $id_venta = $conexion->insert_id;
    $stmt_venta->close();

    if (!empty($pagos) && !$ventaPagosDisponible) {
        throw new Exception('Debes crear la tabla venta_pagos antes de usar pagos mixtos.');
    }

    if ($ventaPagosDisponible) {
        $stmt_pago = $conexion->prepare("INSERT INTO venta_pagos (id_venta, metodo_pago, monto) VALUES (?, ?, ?)");
        foreach ($pagosNormalizados as $pago) {
            $metodoPagoDetalle = $pago['metodo'];
            $montoPagoDetalle = (float)$pago['monto'];
            $stmt_pago->bind_param("isd", $id_venta, $metodoPagoDetalle, $montoPagoDetalle);
            $stmt_pago->execute();
        }
        $stmt_pago->close();
    }

    if ($detalleTieneAuditoria) {
        $stmt_detalle = $conexion->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, descuento, tipo_item, tipo_presentacion_audit, descripcion_item, referencia_combo, cantidad_presentaciones_audit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    } else {
        $stmt_detalle = $conexion->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, descuento) VALUES (?, ?, ?, ?, ?)");
    }
    $stmt_stock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
    $stmt_kardex = $conexion->prepare("INSERT INTO movimientos_inventario (id_producto, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, id_usuario, referencia_id) VALUES (?, 'venta', ?, ?, ?, ?, ?)");
    $stmt_stock_actual = $conexion->prepare("SELECT stock FROM productos WHERE id_producto = ?");

    foreach ($detalle_productos as $detalle) {
        $id_producto = (int)$detalle['id_producto'];
        $cantidad = (int)$detalle['cantidad'];
        $precio_unitario = (float)$detalle['precio_unitario'];
        $descuento_item = (float)$detalle['descuento'];

        $stmt_stock_actual->bind_param('i', $id_producto);
        $stmt_stock_actual->execute();
        $prod_info = $stmt_stock_actual->get_result()->fetch_assoc();

        if (!$prod_info) {
            throw new Exception('No se pudo obtener el stock del producto ID ' . $id_producto);
        }

        $stock_anterior = (int)$prod_info['stock'];
        if ($cantidad > $stock_anterior) {
            throw new Exception('El stock cambió antes de confirmar la venta para "' . $detalle['nombre'] . '".');
        }

        if ($detalleTieneAuditoria) {
            $tipo_item_auditoria = $detalle['tipo_item'];
            $tipo_presentacion_audit = $detalle['tipo_presentacion'];
            $descripcion_item = $detalle['descripcion_item'];
            $referencia_combo = $detalle['referencia_combo'];
            $cantidad_presentaciones_audit = (int)$detalle['cantidad_presentaciones'];

            $stmt_detalle->bind_param(
                "iiiddsssii",
                $id_venta,
                $id_producto,
                $cantidad,
                $precio_unitario,
                $descuento_item,
                $tipo_item_auditoria,
                $tipo_presentacion_audit,
                $descripcion_item,
                $referencia_combo,
                $cantidad_presentaciones_audit
            );
        } else {
            $stmt_detalle->bind_param("iiidd", $id_venta, $id_producto, $cantidad, $precio_unitario, $descuento_item);
        }
        $stmt_detalle->execute();

        $stmt_stock->bind_param("ii", $cantidad, $id_producto);
        $stmt_stock->execute();

        $stock_nuevo = $stock_anterior - $cantidad;
        $stmt_kardex->bind_param("iiiiii", $id_producto, $cantidad, $stock_anterior, $stock_nuevo, $_SESSION['id_usuario'], $id_venta);
        $stmt_kardex->execute();
    }

    $stmt_detalle->close();
    $stmt_stock->close();
    $stmt_kardex->close();
    $stmt_stock_actual->close();

    $conexion->commit();
    $cambio = !empty($pagos)
        ? max(0, round(array_sum(array_column($pagos, 'monto')) - $total_venta_redondeado, 2))
        : 0;

    echo json_encode(['success' => true, 'id_venta' => $id_venta, 'cambio' => $cambio]);
} catch (Exception $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al procesar la venta: ' . $e->getMessage()]);
}

$conexion->close();
