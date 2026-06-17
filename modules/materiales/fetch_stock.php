<?php
require_once '../../config/database.php';
$pdo   = conectar();
// Validate that the request contains the necessary ID
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $materialId = (int)$_POST['id'];

    // Prepared Statement execution using PDO
    $stmt = $pdo->prepare("SELECT i.id_material, m.nombre, i.stock, i.stock_minimo
                           FROM inventarios i
                           JOIN materiales m ON m.id_material = i.id_material
                           WHERE i.id_material = :id");
    $stmt->execute(['id' => $materialId]);
    $material = $stmt->fetch();

    if ($material) {
        // Output clean HTML template back to the AJAX success container
        ?>
        <table class="table table-bordered mb-0">
            <tr>
                <th>Id</th>
                <td><?= htmlspecialchars($material['id_material']); ?></td>
            </tr>
            <tr>
                <th>Nombre</th>
                <td><?= htmlspecialchars($material['nombre']); ?></td>
            </tr>
            <tr>
                <th>Stock Total</th>
                <td><?= htmlspecialchars($material['stock']); ?></td>
            </tr>
            <tr>
                <th>Stock Minimo</th>
                <td><?= htmlspecialchars($material['stock_minimo']); ?></td>
            </tr>
        </table>
        <?php
    } else {
        echo '<div class="alert alert-warning">No se encontraron registros para este material.</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID invalido.</div>';
}
