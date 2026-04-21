<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../ventas/helpers_presentaciones.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario'])) { redirigir('/mi_sistema/modules/auth/login.php'); }
if (!tienePermiso('productos_ver_lista') && !usuarioTieneRol([1, 2])) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$puedeEditar = usuarioTieneRol([1]) || tienePermiso('productos_editar');
$puedeCrear = usuarioTieneRol([1]) || tienePermiso('productos_crear');
$puedeCambiarEstado = usuarioTieneRol([1, 2]) || tienePermiso('productos_cambiar_estado');
$puedeGestionarInventario = $puedeCrear || $puedeEditar || $puedeCambiarEstado;

$ver_inactivos = isset($_GET['ver']) && $_GET['ver'] == 'inactivos';
$filtro_activo = $ver_inactivos ? 0 : 1;

$where = ["p.activo = ?"];
$params = [$filtro_activo];
$types = "i";

// 🔍 Buscador
if (!empty($_GET['buscar'])) {
    $where[] = "p.nombre LIKE ?";
    $params[] = "%" . $_GET['buscar'] . "%";
    $types .= "s";
}

// 🧩 Filtro por categoría
if (!empty($_GET['categoria'])) {
    $where[] = "p.id_categoria = ?";
    $params[] = $_GET['categoria'];
    $types .= "i";
}

// 🧠 SQL dinámico
$sql = "SELECT p.*, c.nombre as nombre_categoria 
        FROM productos p 
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.nombre ASC";

$stmt = $conexion->prepare($sql);

// Bind dinámico
$stmt->bind_param($types, ...$params);

$stmt->execute();
$resultado = $stmt->get_result();

$productos_listado = [];
while ($fila = $resultado->fetch_assoc()) {
    $productos_listado[] = $fila;
}

$productos_presentaciones = anexarPresentacionesAProductos($conexion, $productos_listado);
$presentaciones_por_producto = [];
foreach ($productos_presentaciones as $producto_presentacion) {
    $presentaciones_por_producto[(int)$producto_presentacion['id_producto']] = array_values(array_filter(
        $producto_presentacion['presentaciones'],
        fn($presentacion) => !empty($presentacion['id_presentacion'])
    ));
}

$categorias_sql = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC";
$categorias_resultado = $conexion->query($categorias_sql);
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0"><i class="fas fa-box-open"></i> Gestión de Inventario</h2>
        <div>
            <?php if ($ver_inactivos): ?>
                <a href="index.php" class="btn btn-info">Ver Activos</a>
            <?php else: ?>
                <a href="index.php?ver=inactivos" class="btn btn-secondary">Ver Inactivos</a>
            <?php endif; ?>
            <?php if ($puedeCrear): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productoModal">
                    <i class="fas fa-plus"></i> Agregar Producto
                </button>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" class="row mb-3">
            <div class="col-md-4">
                <input type="text" name="buscar" class="form-control" placeholder="🔍 Buscar producto..."
                    value="<?php echo $_GET['buscar'] ?? ''; ?>">
            </div>

            <div class="col-md-4">
                <select name="categoria" class="form-select" aria-label="Selecciona categoría">
                    <option value="" disabled selected>🌐 Todas las categorías</option>
                    <?php
                    $categorias = $conexion->query("SELECT id_categoria, nombre FROM categorias");
                    while ($cat = $categorias->fetch_assoc()) {
                        $selected = (isset($_GET['categoria']) && $_GET['categoria'] == $cat['id_categoria']) ? 'selected' : '';
                        echo "<option value='{$cat['id_categoria']}' $selected>{$cat['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100">Buscar</button>
            </div>

            <div class="col-md-2">
                <a href="index.php" class="btn btn-secondary w-100">Limpiar</a>
            </div>
    </form>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">Foto</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio Venta</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Herramientas</th>
                        <?php if ($puedeGestionarInventario): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($productos_listado) > 0): ?>
                        <?php foreach($productos_listado as $producto): ?>
                        <tr>
                            <td class="text-center">
                                <?php 
                                $foto = !empty($producto['imagen']) ? $producto['imagen'] : 'default_producto.webp';
                                ?>
                                <img src="../../assets/img/productos/<?php echo $foto; ?>" 
                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                            </td>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($producto['nombre_categoria'] ?? 'Sin categoría'); ?></td>
                            <td><?php echo getMoneda(); ?><?php echo number_format($producto['precio_venta'], 2); ?></td>
                            <td><?php echo $producto['stock']; ?></td>
                            <td>


                            
                                <?php if ($producto['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>


                            </td>
                            <td>
                                <?php if (tienePermiso('productos_ver_detalle')): ?>
                                    <a href="detalle_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-sm btn-info" title="Ver Kardex">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <?php 
                                // Determinamos el color del botón: success (verde) si está vacío, dark (negro) si tiene código
                                $btnColor = (empty($producto['codigo_barra'])) ? 'btn-success' : 'btn-dark';
                                ?>

                                <button type="button" class="btn btn-sm <?php echo $btnColor; ?> barcode-btn" 
                                        data-bs-toggle="modal" data-bs-target="#barcodeModal"
                                        data-id="<?php echo $producto['id_producto']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-codigo="<?php echo htmlspecialchars($producto['codigo_barra'] ?? ''); ?>"
                                        title="Generar/Ver Código de Barras">
                                    <i class="fas fa-barcode"></i>
                                </button>

                            </td>
                            <?php if ($puedeGestionarInventario): ?>
                                <td>
                                    <?php if ($puedeEditar): ?>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                                data-bs-toggle="modal" data-bs-target="#productoModal"
                                                data-id="<?php echo $producto['id_producto']; ?>"
                                                data-imagen="<?php echo $foto; ?>"
                                                data-codigo="<?php echo htmlspecialchars($producto['codigo_barra'] ?? ''); ?>"
                                                data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                data-descripcion="<?php echo htmlspecialchars($producto['descripcion']); ?>"
                                                data-categoria="<?php echo $producto['id_categoria']; ?>"
                                                data-precioventa="<?php echo $producto['precio_venta']; ?>"
                                                data-preciocompra="<?php echo $producto['precio_compra']; ?>"
                                                data-stock="<?php echo $producto['stock']; ?>"
                                                data-stockminimo="<?php echo $producto['stock_minimo']; ?>"
                                                data-presentaciones='<?php echo htmlspecialchars(json_encode($presentaciones_por_producto[(int)$producto['id_producto']] ?? [], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($puedeCambiarEstado): ?>
                                        <?php if ($producto['activo']): ?>
                                            <a href="cambiar_estado.php?id=<?php echo $producto['id_producto']; ?>&estado=0" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-ban"></i></a>
                                        <?php else: ?>
                                            <a href="cambiar_estado.php?id=<?php echo $producto['id_producto']; ?>&estado=1" class="btn btn-sm btn-success" title="Activar"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $puedeGestionarInventario ? 8 : 7; ?>" class="text-center">No hay productos <?php echo $ver_inactivos ? 'inactivos' : 'activos'; ?>.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="productoModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Agregar Nuevo Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="guardar.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_producto" id="id_producto">

            <div class="mb-3 text-center">
                <label class="form-label d-block fw-bold">Imagen Referencial</label>
                <div class="mb-3">
                    <img id="img-preview" src="../../assets/img/productos/default_producto.webp" 
                        class="img-thumbnail shadow-sm" 
                        style="width: 140px; height: 140px; object-fit: cover; border-radius: 10px;">
                </div>
                
                <div class="d-grid gap-2 col-8 mx-auto">
                    <label for="imagenInput" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-upload me-2"></i>Seleccionar Imagen
                    </label>
                    <input type="file" name="imagen" id="imagenInput" class="d-none" accept="image/*">
                </div>
                <small id="file-name" class="text-muted mt-2 d-block">Ningún archivo seleccionado</small>
            </div>        

            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Producto</label>
                <input type="text" class="form-control" name="nombre" id="nombre" required>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" name="descripcion" id="descripcion" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="id_categoria" class="form-label">Categoría</label>
                <select class="form-select" name="id_categoria" id="id_categoria">
                    <option value="">Seleccione una categoría</option>
                    <?php mysqli_data_seek($categorias_resultado, 0); ?>
                    <?php while($categoria = $categorias_resultado->fetch_assoc()): ?>
                        <option value="<?php echo $categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="precio_venta" class="form-label">Precio Venta</label>
                    <input type="number" step="0.01" class="form-control" name="precio_venta" id="precio_venta" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="precio_compra" class="form-label">Precio Compra</label>
                    <input type="number" step="0.01" class="form-control" name="precio_compra" id="precio_compra">
                </div>
            </div>
             <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="stock" class="form-label">Stock Actual</label>
                    <input type="number" class="form-control" name="stock" id="stock" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                    <input type="number" class="form-control" name="stock_minimo" id="stock_minimo">
                </div>
            </div>

            <div class="mb-3">
                <label for="codigo_barras" class="form-label">Código de Barras (Fábrica)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                    <input type="text" name="codigo_barra" id="codigo_barra" class="form-control" placeholder="Escanea el código del producto">
                </div>
                <small class="text-muted">Si el producto no tiene código, puedes dejarlo vacío para generar uno interno después.</small>
            </div>

            <div class="border rounded-3 p-3 mb-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-1">Presentaciones adicionales</h6>
                        <small class="text-muted">MantÃ©n el precio base como unidad y agrega packs o caja.</small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary preset-presentacion-btn" data-tipo="x3" data-cantidad="3">+ x3</button>
                        <button type="button" class="btn btn-outline-primary preset-presentacion-btn" data-tipo="x6" data-cantidad="6">+ x6</button>
                        <button type="button" class="btn btn-outline-primary preset-presentacion-btn" data-tipo="Caja" data-cantidad="12">+ Caja</button>
                    </div>
                </div>
                <div id="presentaciones_container" class="d-grid gap-2"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-3" id="btn_agregar_presentacion">
                    <i class="fas fa-plus me-1"></i> Agregar presentaciÃ³n manual
                </button>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<template id="presentacion_row_template">
    <div class="row g-2 align-items-end presentacion-row border rounded-3 p-2 bg-white">
        <div class="col-md-4">
            <label class="form-label small mb-1">Tipo</label>
            <input type="text" class="form-control form-control-sm presentacion-tipo" name="presentacion_tipo[]" placeholder="Ej. x3, x6, Caja">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Cantidad</label>
            <input type="number" min="2" class="form-control form-control-sm presentacion-cantidad" name="presentacion_cantidad[]" placeholder="3">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Precio</label>
            <input type="number" min="0" step="0.01" class="form-control form-control-sm presentacion-precio" name="presentacion_precio[]" placeholder="0.00">
        </div>
        <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-outline-danger btn-sm remove-presentacion-btn">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<div class="modal fade" id="barcodeModal" tabindex="-1" aria-labelledby="barcodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="barcodeModalLabel">Gestión de Código de Barras</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center p-4">
                <h6 id="barcodeProductName" class="mb-3 text-muted"></h6>
                
                <svg id="barcodeImage"></svg>
                
                <div id="noBarcodeMessage" class="mt-3 d-none">
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Este producto no tiene un código registrado.
                    </p>
                    <button type="button" class="btn btn-success" id="generateBarcodeBtn">
                        <i class="fas fa-magic"></i> Generar Código Interno
                    </button>
                </div>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="printBarcodeBtn">
                    <i class="fas fa-print"></i> Imprimir Etiqueta
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>



<script>

function crearFilaPresentacion(data = {}) {
    const template = document.getElementById('presentacion_row_template');
    const container = document.getElementById('presentaciones_container');
    const fragment = template.content.cloneNode(true);
    const row = fragment.querySelector('.presentacion-row');

    row.querySelector('.presentacion-tipo').value = data.tipo || '';
    row.querySelector('.presentacion-cantidad').value = data.cantidad || '';
    row.querySelector('.presentacion-precio').value = data.precio || '';
    row.querySelector('.remove-presentacion-btn').addEventListener('click', () => row.remove());

    container.appendChild(fragment);
}

function limpiarPresentaciones() {
    document.getElementById('presentaciones_container').innerHTML = '';
}

// Variable global dentro del scope de carga
let idActual = "";

document.addEventListener('DOMContentLoaded', function() {
    let idActual = "";
    let codigoActual = "";
    let nombreActual = "";

    // Al abrir la modal
    document.querySelectorAll('.barcode-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            idActual = this.getAttribute('data-id');
            nombreActual = this.getAttribute('data-nombre');
            codigoActual = this.getAttribute('data-codigo');

            document.getElementById('barcodeProductName').textContent = nombreActual;
            mostrarBarcode();
        });
    });

    function mostrarBarcode() {
        const btnPrint = document.getElementById('printBarcodeBtn');
        const msgNoCode = document.getElementById('noBarcodeMessage');
        const svg = document.getElementById('barcodeImage');

        if (codigoActual && codigoActual.trim() !== "") {
            msgNoCode.classList.add('d-none');
            svg.style.display = 'block';
            btnPrint.disabled = false;
            JsBarcode("#barcodeImage", codigoActual, { format: "CODE128", displayValue: true });
        } else {
            svg.style.display = 'none';
            msgNoCode.classList.remove('d-none');
            btnPrint.disabled = true;
        }
    }

    // Lógica para GENERAR código mediante AJAX
    document.getElementById('generateBarcodeBtn').addEventListener('click', function() {
        const formData = new FormData();
        formData.append('id', idActual);

        fetch('guardar_codigo.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                codigoActual = data.codigo;
                
                // 1. Buscamos el botón específico en la tabla
                const botonTabla = document.querySelector(`.barcode-btn[data-id="${idActual}"]`);
                
                // 2. Actualizamos su atributo data-codigo
                botonTabla.setAttribute('data-codigo', codigoActual);
                
                // 3. CAMBIAMOS EL COLOR: Quitamos verde (success) y ponemos negro (dark)
                botonTabla.classList.remove('btn-success');
                botonTabla.classList.add('btn-dark');

                mostrarBarcode();
                alert("Código generado con éxito: " + codigoActual);
            }
        });
    });

    // Lógica para IMPRIMIR
    document.getElementById('printBarcodeBtn').addEventListener('click', function() {
        if (codigoActual) {
            const url = `imprimir_barcode.php?codigo=${codigoActual}&nombre=${encodeURIComponent(nombreActual)}`;
            window.open(url, '_blank', 'width=500,height=500');
        }
    });
});




    // --- LÓGICA UNIFICADA PARA AGREGAR Y EDITAR PRODUCTO ---
    const productoModal = document.getElementById('productoModal');
        
        productoModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Botón que activó el modal
            
            // 1. Si el botón NO tiene la clase 'edit-btn', es para AGREGAR (limpiamos todo)
            if (!button.classList.contains('edit-btn')) {
                document.getElementById('modalLabel').textContent = 'Agregar Nuevo Producto';
                document.getElementById('id_producto').value = '';
                document.getElementById('nombre').value = '';
                document.getElementById('descripcion').value = '';
                document.getElementById('id_categoria').value = '';
                document.getElementById('precio_venta').value = '';
                document.getElementById('precio_compra').value = '';
                document.getElementById('stock').value = '';
                document.getElementById('stock_minimo').value = '';
                document.getElementById('codigo_barra').value = ''; // Limpiar código
                document.getElementById('img-preview').src = '../../assets/img/productos/default_producto.png';
                document.getElementById('file-name').textContent = 'Ningún archivo seleccionado';
                return;
            }

            // 2. Si es EDICIÓN, extraemos y cargamos todos los datos
            document.getElementById('modalLabel').textContent = 'Editar Producto';
            
            // Cargar IDs y Textos básicos
            document.getElementById('id_producto').value = button.getAttribute('data-id');
            document.getElementById('nombre').value = button.getAttribute('data-nombre');
            document.getElementById('descripcion').value = button.getAttribute('data-descripcion');
            document.getElementById('id_categoria').value = button.getAttribute('data-categoria');
            
            // Cargar Valores Numéricos
            document.getElementById('precio_venta').value = button.getAttribute('data-precioventa');
            document.getElementById('precio_compra').value = button.getAttribute('data-preciocompra');
            document.getElementById('stock').value = button.getAttribute('data-stock');
            document.getElementById('stock_minimo').value = button.getAttribute('data-stockminimo');

            // CARGAR EL CÓDIGO DE BARRA (Lógica que te funcionó)
            const codigo = button.getAttribute('data-codigo');
            const inputCodigo = document.getElementById('codigo_barra');
            if (inputCodigo) {
                inputCodigo.value = codigo || ''; // Si es null o vacío, pone cadena vacía
            }

            // CARGAR LA FOTO ACTUAL
            const foto = button.getAttribute('data-imagen') || 'default_producto.png';
            document.getElementById('img-preview').src = '../../assets/img/productos/' + foto;
            document.getElementById('file-name').textContent = 'Imagen actual: ' + foto;
        });

        // Evento para previsualizar imagen nueva al seleccionar archivo
        document.getElementById('imagenInput').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                document.getElementById('file-name').textContent = file.name;
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('img-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productoModal = document.getElementById('productoModal');
    const btnAgregarPresentacion = document.getElementById('btn_agregar_presentacion');
    const presetPresentacionBtns = document.querySelectorAll('.preset-presentacion-btn');

    btnAgregarPresentacion?.addEventListener('click', function () {
        crearFilaPresentacion();
    });

    presetPresentacionBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            crearFilaPresentacion({
                tipo: this.getAttribute('data-tipo'),
                cantidad: this.getAttribute('data-cantidad')
            });
        });
    });

    productoModal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        if (!button) {
            return;
        }

        limpiarPresentaciones();

        if (!button.classList.contains('edit-btn')) {
            return;
        }

        let presentaciones = [];
        try {
            presentaciones = JSON.parse(button.getAttribute('data-presentaciones') || '[]');
        } catch (error) {
            console.error('No se pudieron leer las presentaciones del producto', error);
        }

        presentaciones.forEach(presentacion => {
            crearFilaPresentacion({
                tipo: presentacion.tipo,
                cantidad: presentacion.cantidad,
                precio: presentacion.precio
            });
        });
    });
});
</script>








<?php 
echo '<script src="/mi_sistema/assets/js/productos.js"></script>';
require_once __DIR__ . '/../../includes/footer.php'; 
?>
