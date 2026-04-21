document.addEventListener('DOMContentLoaded', function() {
    // Estado de la venta
    let carrito = [];

    // Elementos del DOM
    const buscarInput = document.getElementById('buscar_producto');
    const resultadosBusqueda = document.getElementById('resultados_busqueda');
    const carritoTbody = document.getElementById('carrito_tbody');
    const totalVentaSpan = document.getElementById('total_venta');
    const btnFinalizarVenta = document.getElementById('btn_finalizar_venta');
    const pagoModal = document.getElementById('pagoModal');
    const modalTotalPagar = document.getElementById('modal_total_pagar');
    const montoRecibidoInput = document.getElementById('monto_recibido');
    const cambioClienteSpan = document.getElementById('cambio_cliente');
    const btnConfirmarVenta = document.getElementById('btn_confirmar_venta');
    const metodoPagoSelect = document.getElementById('metodo_pago');
    const pagoEfectivoDiv = document.getElementById('pago_efectivo_div');
    const idClienteSelect = document.getElementById('id_cliente');

    // --- BÚSQUEDA DE PRODUCTOS ---
    buscarInput.addEventListener('keyup', function() {
        const term = this.value;
        if (term.length < 2) {
            resultadosBusqueda.innerHTML = '';
            return;
        }
        // La ruta es relativa al archivo PHP que incluye este script
        fetch(`buscar_productos.php?term=${term}`)
            .then(response => response.json())
            .then(data => {
                resultadosBusqueda.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(producto => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = `${producto.nombre} - Stock: ${producto.stock} - ${MONEDA}${parseFloat(producto.precio_venta).toFixed(2)}`;
                        item.addEventListener('click', (e) => {
                            e.preventDefault();
                            agregarAlCarrito(producto);
                            buscarInput.value = '';
                            resultadosBusqueda.innerHTML = '';
                        });
                        resultadosBusqueda.appendChild(item);
                    });
                } else {
                    resultadosBusqueda.innerHTML = '<span class="list-group-item">No se encontraron productos</span>';
                }
            });
    });

    // --- LÓGICA DEL CARRITO ---
    function agregarAlCarrito(producto) {
        const existente = carrito.find(item => item.id === producto.id_producto);
        if (existente) {
            existente.cantidad++;
        } else {
            carrito.push({
                id: producto.id_producto,
                nombre: producto.nombre,
                precio: parseFloat(producto.precio_venta),
                cantidad: 1
            });
        }
        renderizarCarrito();
    }

    function renderizarCarrito() {
        carritoTbody.innerHTML = '';
        let total = 0;
        carrito.forEach((item, index) => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.nombre}</td>
                <td>${MONEDA}${item.precio.toFixed(2)}</td>
                <td>
                    <input type="number" value="${item.cantidad}" min="1" class="form-control form-control-sm cantidad-input" data-index="${index}">
                </td>
                <td>${MONEDA}${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-danger btn-sm eliminar-btn" data-index="${index}"><i class="fas fa-trash"></i></button>
                </td>
            `;
            carritoTbody.appendChild(tr);
        });
        totalVentaSpan.textContent = `${MONEDA}${total.toFixed(2)}`;
        btnFinalizarVenta.disabled = carrito.length === 0;
    }

    carritoTbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('cantidad-input')) {
            const index = e.target.dataset.index;
            const nuevaCantidad = parseInt(e.target.value);
            if (nuevaCantidad > 0) {
                carrito[index].cantidad = nuevaCantidad;
                renderizarCarrito();
            }
        }
    });

    carritoTbody.addEventListener('click', function(e) {
        const targetButton = e.target.closest('.eliminar-btn');
        if (targetButton) {
            const index = targetButton.dataset.index;
            carrito.splice(index, 1);
            renderizarCarrito();
        }
    });
    
    // --- LÓGICA DEL PAGO Y FINALIZACIÓN ---
    btnFinalizarVenta.addEventListener('click', () => {
        const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        modalTotalPagar.textContent = `${MONEDA}${total.toFixed(2)}`;
        montoRecibidoInput.value = '';
        cambioClienteSpan.textContent = `${MONEDA}0.00`;
    });

    metodoPagoSelect.addEventListener('change', () => {
        pagoEfectivoDiv.style.display = metodoPagoSelect.value === 'efectivo' ? 'block' : 'none';
    });

    montoRecibidoInput.addEventListener('keyup', () => {
        const total = carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const recibido = parseFloat(montoRecibidoInput.value) || 0;
        const cambio = recibido - total;
        cambioClienteSpan.textContent = `${MONEDA}${cambio.toFixed(2)}`;
    });

    btnConfirmarVenta.addEventListener('click', function() {
        this.disabled = true;
        const ventaData = {
            id_cliente: idClienteSelect.value,
            metodo_pago: metodoPagoSelect.value,
            carrito: carrito.map(item => ({ id: item.id, cantidad: item.cantidad }))
        };

        fetch('procesar_venta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(ventaData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.mensaje);
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
                this.disabled = false;
            }
        })
        .catch(error => {
            alert('Hubo un error de conexión.');
            this.disabled = false;
        });
    });
});