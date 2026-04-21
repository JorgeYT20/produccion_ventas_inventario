document.addEventListener('DOMContentLoaded', function() {
    
    // --- Lógica para la página de CREACIÓN de compras ---
    const formCrearCompra = document.getElementById('form_guardar_compra');
    if (formCrearCompra) {
        // ... (toda la lógica para la página de crear_compra.php que ya funciona)
    }

    // --- Lógica para la página de LISTADO de compras ---
    const confirmReceiveButtons = document.querySelectorAll('.confirm-receive-btn');
    if (confirmReceiveButtons.length > 0) {
        confirmReceiveButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                // Muestra la ventana de confirmación del navegador
                if (!confirm('¿Estás seguro de que quieres marcar esta orden como recibida? Esta acción actualizará el stock y no se puede deshacer.')) {
                    // Si el usuario hace clic en "Cancelar", previene que el enlace se siga
                    event.preventDefault();
                }
            });
        });
    }
});