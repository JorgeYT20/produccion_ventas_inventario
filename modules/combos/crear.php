<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/header.php';

if (!isset($_SESSION['id_usuario'])) {
    redirigir('/mi_sistema/modules/auth/login.php');
    exit;
}

require_once __DIR__ . '/helpers_combos.php';

if (!tablaCombosDisponible($conexion)) {
    echo "<div class='alert alert-warning'>Debes ejecutar combos.sql antes de crear combos.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$productos = [];
$resultado = $conexion->query("SELECT id_producto, nombre FROM productos WHERE activo = 1 ORDER BY nombre ASC");
while ($fila = $resultado->fetch_assoc()) {
    $productos[] = $fila;
}
?>

<div class="card">
    <div class="card-header">
        <h2 class="m-0"><i class="fas fa-plus-circle"></i> Crear Combo</h2>
    </div>
    <div class="card-body">
        <form action="guardar.php" method="POST" id="comboForm">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Nombre del combo</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio del combo</label>
                    <input type="number" step="0.01" min="0" name="precio" class="form-control" required>
                </div>
            </div>

            <div class="border rounded-3 p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Productos del combo</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarProductoCombo">
                        <i class="fas fa-plus"></i> Agregar producto
                    </button>
                </div>
                <div id="comboProductosContainer" class="d-grid gap-2"></div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar combo</button>
            </div>
        </form>
    </div>
</div>

<template id="comboProductoTemplate">
    <div class="row g-2 align-items-end combo-producto-row border rounded-3 p-2 bg-white">
        <div class="col-md-8">
            <label class="form-label small mb-1">Producto</label>
            <select class="form-select combo-producto-select" name="id_producto[]" required>
                <option value="">Seleccione un producto</option>
                <?php foreach ($productos as $producto): ?>
                    <option value="<?php echo (int)$producto['id_producto']; ?>"><?php echo htmlspecialchars($producto['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Cantidad</label>
            <input type="number" min="1" class="form-control" name="cantidad[]" value="1" required>
        </div>
        <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-outline-danger remove-combo-producto-btn">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
    function crearFilaCombo() {
        const template = document.getElementById('comboProductoTemplate');
        const container = document.getElementById('comboProductosContainer');
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.combo-producto-row');
        row.querySelector('.remove-combo-producto-btn').addEventListener('click', () => row.remove());
        container.appendChild(fragment);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btnAgregarProductoCombo').addEventListener('click', crearFilaCombo);
        crearFilaCombo();

        document.getElementById('comboForm').addEventListener('submit', function (event) {
            const selects = [...document.querySelectorAll('.combo-producto-select')];
            const valores = selects.map(select => select.value).filter(Boolean);

            if (valores.length === 0) {
                alert('Debes agregar al menos un producto al combo.');
                event.preventDefault();
                return;
            }

            if (new Set(valores).size !== valores.length) {
                alert('No se permiten productos duplicados dentro del mismo combo.');
                event.preventDefault();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
