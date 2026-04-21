<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario']) || !tienePermiso('compras_crear')) {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta sección.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$proveedores_sql = "SELECT id_proveedor, nombre_proveedor FROM proveedores WHERE activo = 1 ORDER BY nombre_proveedor ASC";
$proveedores_resultado = $conexion->query($proveedores_sql);
?>

<div class="card">
    <div class="card-header">
        <h4><i class="fas fa-plus-circle"></i> Crear Nueva Orden de Compra</h4>
    </div>
    <div class="card-body">
        <form id="form_guardar_compra" action="guardar_compra.php" method="POST">
            <div class="mb-3">
                <label for="id_proveedor" class="form-label">Proveedor</label>
                <select name="id_proveedor" id="id_proveedor" class="form-select" required>
                    <option value="">Seleccione un proveedor</option>
                    <?php while($proveedor = $proveedores_resultado->fetch_assoc()): ?>
                        <option value="<?php echo $proveedor['id_proveedor']; ?>"><?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <hr>

            <h5>Añadir Productos a la Orden</h5>
            <div class="mb-3">
                <label for="buscar_producto_compra" class="form-label">Buscar Producto</label>
                <input type="text" id="buscar_producto_compra" class="form-control" placeholder="Escribe para buscar...">
                <div id="resultados_busqueda_compra" class="list-group position-absolute" style="z-index: 1000; width: 95%;"></div>
            </div>

            <table class="table table-bordered mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th>Costo Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="compra_tbody"></tbody>
            </table>

            <div class="text-end">
                <h4>Total: <span id="total_compra"><?php echo getMoneda(); ?>0.00</span></h4>
            </div>

            <input type="hidden" name="productos_compra" id="productos_compra_input">
            <input type="hidden" name="total_compra" id="total_compra_input">

            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Orden de Compra</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // La constante MONEDA ahora se define de forma segura
    const MONEDA = '<?php echo getMoneda(); ?>';

    const formCrearCompra = document.getElementById('form_guardar_compra');
    if (formCrearCompra) {
        let ordenCompra = [];
        const buscarInput = document.getElementById('buscar_producto_compra');
        const resultadosBusqueda = document.getElementById('resultados_busqueda_compra');
        const compraTbody = document.getElementById('compra_tbody');
        const totalCompraSpan = document.getElementById('total_compra');
        const productosCompraInput = document.getElementById('productos_compra_input');
        const totalCompraInput = document.getElementById('total_compra_input');

        buscarInput.addEventListener('keyup', function(e) {
            const term = this.value;
            if (term.length < 2) {
                resultadosBusqueda.innerHTML = '';
                return;
            }
            fetch(`buscar_productos.php?term=${term}`)
                .then(response => response.json())
                .then(data => {
                    resultadosBusqueda.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(producto => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.textContent = `${producto.nombre}`;
                            item.addEventListener('click', e => {
                                e.preventDefault();
                                agregarAOrden(producto);
                                buscarInput.value = '';
                                resultadosBusqueda.innerHTML = '';
                            });
                            resultadosBusqueda.appendChild(item);
                        });
                    }
                });
        });

        function agregarAOrden(producto) {
            const existente = ordenCompra.find(item => item.id === producto.id_producto);
            if (existente) {
                existente.cantidad++;
            } else {
                ordenCompra.push({
                    id: producto.id_producto,
                    nombre: producto.nombre,
                    costo: parseFloat(producto.precio_compra),
                    cantidad: 1
                });
            }
            renderizarOrden();
        }

        function renderizarOrden() {
            compraTbody.innerHTML = '';
            let total = 0;
            ordenCompra.forEach((item, index) => {
                const subtotal = item.costo * item.cantidad;
                total += subtotal;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.nombre}</td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-text">${MONEDA}</span>
                            <input type="number" step="0.01" value="${item.costo.toFixed(2)}" class="form-control form-control-sm costo-input" data-index="${index}">
                        </div>
                    </td>
                    <td><input type="number" value="${item.cantidad}" min="1" class="form-control form-control-sm cantidad-input" data-index="${index}"></td>
                    <td>${MONEDA}${subtotal.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-danger btn-sm eliminar-btn" data-index="${index}"><i class="fas fa-trash"></i></button></td>
                `;
                compraTbody.appendChild(tr);
            });
            
            totalCompraSpan.textContent = `${MONEDA}${total.toFixed(2)}`;
            productosCompraInput.value = JSON.stringify(ordenCompra);
            totalCompraInput.value = total.toFixed(2);
        }

        compraTbody.addEventListener('change', function(e) {
            const index = e.target.dataset.index;
            if (e.target.classList.contains('cantidad-input')) {
                ordenCompra[index].cantidad = parseInt(e.target.value);
            }
            if (e.target.classList.contains('costo-input')) {
                ordenCompra[index].costo = parseFloat(e.target.value);
            }
            renderizarOrden();
        });
        
        compraTbody.addEventListener('click', function(e) {
            const targetButton = e.target.closest('.eliminar-btn');
            if (targetButton) {
                ordenCompra.splice(targetButton.dataset.index, 1);
                renderizarOrden();
            }
        });
    }
});
</script>