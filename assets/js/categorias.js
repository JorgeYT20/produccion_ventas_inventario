document.addEventListener('DOMContentLoaded', function() {
    const categoriaModal = document.getElementById('categoriaModal');
    if (categoriaModal) {
        categoriaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modalTitle = categoriaModal.querySelector('.modal-title');
            const form = categoriaModal.querySelector('form');
            form.reset();
            
            document.getElementById('id_categoria').value = '';
            modalTitle.textContent = 'Agregar Nueva Categoría';
            
            if (button.classList.contains('edit-cat-btn')) {
                modalTitle.textContent = 'Editar Categoría';
                document.getElementById('id_categoria').value = button.dataset.id;
                document.getElementById('nombre_categoria').value = button.dataset.nombre;
            }
        });
    }
});