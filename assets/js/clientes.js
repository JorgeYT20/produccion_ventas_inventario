document.addEventListener('DOMContentLoaded', function() {
    const clienteModal = document.getElementById('clienteModal');
    if (clienteModal) {
        clienteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modalTitle = clienteModal.querySelector('.modal-title');
            const form = clienteModal.querySelector('form');
            form.reset();
            
            document.getElementById('id_cliente').value = '';
            modalTitle.textContent = 'Agregar Nuevo Cliente';
            
            if (button.classList.contains('edit-cliente-btn')) {
                modalTitle.textContent = 'Editar Cliente';
                document.getElementById('id_cliente').value = button.dataset.id;
                document.getElementById('nombre_cliente').value = button.dataset.nombre;
                document.getElementById('documento_identidad').value = button.dataset.documento;
                document.getElementById('telefono').value = button.dataset.telefono;
                document.getElementById('email').value = button.dataset.email;
                document.getElementById('direccion').value = button.dataset.direccion;
            }
        });
    }
});