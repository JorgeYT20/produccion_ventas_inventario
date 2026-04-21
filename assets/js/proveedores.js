// Espera a que todo el contenido del DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {

    const proveedorModal = document.getElementById('proveedorModal');

    // El getElementById puede devolver 'null' si el modal no está en la página actual.
    // Por eso, solo agregamos el 'listener' si el modal existe.
    if (proveedorModal) {
        proveedorModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modalTitle = proveedorModal.querySelector('.modal-title');
            const form = proveedorModal.querySelector('form');

            // Limpiar el formulario para modo "Agregar"
            form.reset();
            document.getElementById('id_proveedor').value = '';
            modalTitle.textContent = 'Agregar Nuevo Proveedor';

            // Si el botón es de "Editar"
            if (button.classList.contains('edit-proveedor-btn')) {
                modalTitle.textContent = 'Editar Proveedor';

                // Rellenar el formulario
                document.getElementById('id_proveedor').value = button.dataset.id;
                document.getElementById('nombre_proveedor').value = button.dataset.nombre;
                document.getElementById('ruc').value = button.dataset.ruc;
                document.getElementById('telefono').value = button.dataset.telefono;
                document.getElementById('email').value = button.dataset.email;
                document.getElementById('direccion').value = button.dataset.direccion;
            }
        });
    }

});