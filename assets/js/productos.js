// Espera a que todo el contenido del DOM (la página) esté cargado
document.addEventListener('DOMContentLoaded', function() {
    
    const productoModal = document.getElementById('productoModal');
    
    // Solo ejecutar si el modal de productos realmente existe en esta página
    if (productoModal) {
        // 'show.bs.modal' se dispara cada vez que se intenta abrir el modal
        productoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Botón que activó el modal (+ Agregar o Editar)
            const modalTitle = productoModal.querySelector('.modal-title');
            const form = productoModal.querySelector('form');
            
            // Siempre reseteamos el formulario al abrir
            form.reset();
            document.getElementById('id_producto').value = '';
            modalTitle.textContent = 'Agregar Nuevo Producto';

            // --- LÓGICA PARA EDITAR (esto es lo que faltaba) ---
            // Si el botón que abrió el modal es un botón de editar (tiene la clase 'edit-btn')
            if (button.classList.contains('edit-btn')) {
                modalTitle.textContent = 'Editar Producto';
                
                // Llenar el formulario con los datos guardados en los atributos 'data-*' del botón
                document.getElementById('id_producto').value = button.dataset.id;
                document.getElementById('nombre').value = button.dataset.nombre;
                document.getElementById('descripcion').value = button.dataset.descripcion;
                document.getElementById('id_categoria').value = button.dataset.categoria;
                document.getElementById('precio_venta').value = button.dataset.precioventa;
                document.getElementById('precio_compra').value = button.dataset.preciocompra;
                document.getElementById('stock').value = button.dataset.stock;
                document.getElementById('stock_minimo').value = button.dataset.stockminimo;
            }
        });
    }
});



// nueva funccion subir imagen
// Previsualización de imagen en tiempo real
const imagenInput = document.getElementById('imagenInput');
const imgPreview = document.getElementById('img-preview');
const fileNameDisplay = document.getElementById('file-name');

if (imagenInput) {
    imagenInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            // Mostrar nombre del archivo
            fileNameDisplay.textContent = file.name;
            
            // Crear URL para mostrar la imagen
            const reader = new FileReader();
            reader.onload = function(e) {
                imgPreview.setAttribute('src', e.target.result);
            }
            reader.readAsDataURL(file);
        } else {
            imgPreview.setAttribute('src', '../../assets/img/productos/default_producto.webp');
            fileNameDisplay.textContent = "Ningún archivo seleccionado";
        }
    });
}

// Limpiar la imagen cuando se cierre el modal para un producto nuevo
const modalProducto = document.getElementById('productoModal');
if (modalProducto) {
    modalProducto.addEventListener('hidden.bs.modal', function () {
        imgPreview.setAttribute('src', '../../assets/img/productos/default_producto.webp');
        fileNameDisplay.textContent = "Ningún archivo seleccionado";
        if(imagenInput) imagenInput.value = "";
    });
}
// Resetear la imagen cuando se cierre el modal o sea un producto nuevo
const productoModal = document.getElementById('productoModal');
if (productoModal) {
    productoModal.addEventListener('hidden.bs.modal', function () {
        imgPreview.src = "../../assets/img/productos/default_producto.webp";
        fileNameDisplay.textContent = "Ningún archivo seleccionado";
    });
}