document.addEventListener('DOMContentLoaded', function() {
    const usuarioModal = document.getElementById('usuarioModal');
    if (usuarioModal) {
        usuarioModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modalTitle = usuarioModal.querySelector('.modal-title');
            const form = usuarioModal.querySelector('form');
            form.reset();
            
            document.getElementById('id_usuario').value = '';
            modalTitle.textContent = 'Agregar Nuevo Usuario';
            document.getElementById('password').setAttribute('required', 'required');
            
            if (button.classList.contains('edit-user-btn')) {
                modalTitle.textContent = 'Editar Usuario';
                document.getElementById('id_usuario').value = button.dataset.id;
                document.getElementById('nombre_completo').value = button.dataset.nombre;
                document.getElementById('username').value = button.dataset.username;
                document.getElementById('id_rol').value = button.dataset.rol;
                document.getElementById('password').removeAttribute('required');
            }
        });
    }
});