// Espera a que todo el contenido del DOM (la página) esté cargado
document.addEventListener('DOMContentLoaded', function() {

    // Lógica para el modal de productos (si la tienes aquí)
    // ...

    // Lógica para confirmar antes de eliminar
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            if (!confirm('¿Estás seguro de que quieres eliminar este registro?')) {
                event.preventDefault(); // Cancela la acción si el usuario dice "No"
            }
        });
    });

});