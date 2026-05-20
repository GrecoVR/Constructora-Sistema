<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('gestionar_materiales');
registrarAccion('Vio lista de materiales');

$pdo  = conectar();

// Filtro de búsqueda
$busqueda = trim($_GET['busqueda'] ?? '');

if ($busqueda) {
    $stmt = $pdo->prepare("
        SELECT m.id_material, m.nombre, m.descripcion, m.precio_unitario_base,
               tm.nombre as tipo, um.abreviatura as unidad
        FROM materiales m
        JOIN tipos_materiales tm ON tm.id_tipo_material = m.id_tipo_material
        JOIN unidades_medida um ON um.id_unidad_medida = m.id_unidad_medida
        WHERE m.nombre LIKE ? OR tm.nombre LIKE ?
        ORDER BY m.nombre ASC
    ");
    $stmt->execute(["%$busqueda%", "%$busqueda%"]);
} else {
    $stmt = $pdo->query("
        SELECT m.id_material, m.nombre, m.descripcion, m.precio_unitario_base,
               tm.nombre as tipo, um.descripcion as unidad
        FROM materiales m
        JOIN tipos_materiales tm ON tm.id_tipo_material = m.id_tipo_material
        JOIN unidades_medida um ON um.id_unidad_medida = m.id_unidad_medida
        ORDER BY m.nombre ASC
    ");
}
$materiales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Materiales — Vértice</title>
</head>
<body>

<h2>🧱 Materiales</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="crear.php"><button>+ Nuevo material</button></a>

<br><br>

<form method="GET">
    <input type="text" name="busqueda" placeholder="Buscar material o tipo..." 
           value="<?= htmlspecialchars($busqueda) ?>">
    <button type="submit">Buscar</button>
    <?php if ($busqueda): ?>
        <a href="index.php">Limpiar</a>
    <?php endif; ?>
</form>

<br>

<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Tipo</th>
        <th>Unidad</th>
        <th>Precio base</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($materiales as $m): ?>
        <tr>
            <td><?= $m['id_material'] ?></td>
            <td><?= htmlspecialchars($m['nombre']) ?></td>
            <td><?= htmlspecialchars($m['tipo']) ?></td>
            <td><?= $m['unidad'] ?></td>
            <td>Bs <?= number_format($m['precio_unitario_base'], 2) ?></td>
            <td>
                <a href="editar.php?id=<?= $m['id_material'] ?>">Editar</a>
                &nbsp;|&nbsp;
                <a href="movimientos.php?id_material=<?= $m['id_material'] ?>">Ver stock</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($materiales)): ?>
    <p>No se encontraron materiales.</p>
<?php endif; ?>

</body>
</html>