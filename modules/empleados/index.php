<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_empleados');
registrarAccion('Vio lista de empleados');

$pdo      = conectar();
$busqueda = trim($_GET['busqueda'] ?? '');

if ($busqueda) {
    $stmt = $pdo->prepare("
        SELECT e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado,
               MAX(c.nombre) as cargo_actual
        FROM empleados e
        LEFT JOIN asignaciones a ON a.id_empleado = e.id_empleado AND a.fecha_fin IS NULL
        LEFT JOIN cargos c ON c.id_cargo = a.id_cargo
        WHERE e.nombre LIKE ? OR e.ci LIKE ?
        GROUP BY e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado
        ORDER BY e.nombre ASC
    ");
    $stmt->execute(["%$busqueda%", "%$busqueda%"]);
} else {
    $stmt = $pdo->query("
        SELECT e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado,
               MAX(c.nombre) as cargo_actual
        FROM empleados e
        LEFT JOIN asignaciones a ON a.id_empleado = e.id_empleado AND a.fecha_fin IS NULL
        LEFT JOIN cargos c ON c.id_cargo = a.id_cargo
        GROUP BY e.id_empleado, e.nombre, e.ci, e.telefono, e.email, e.estado
        ORDER BY e.nombre ASC
    ");
}
$empleados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empleados — Vértice</title>
</head>
<body>

<h2>👷 Empleados</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
<?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
    &nbsp;&nbsp;
    <a href="crear.php"><button>+ Nuevo empleado</button></a>
<?php endif; ?>

<br><br>

<form method="GET">
    <input type="text" name="busqueda" placeholder="Buscar por nombre o CI..."
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
        <th>CI</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>Cargo actual</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($empleados as $e): ?>
        <tr>
            <td><?= $e['id_empleado'] ?></td>
            <td><?= htmlspecialchars($e['nombre']) ?></td>
            <td><?= $e['ci'] ?></td>
            <td><?= $e['telefono'] ?></td>
            <td><?= $e['email'] ?></td>
            <td><?= $e['cargo_actual'] ?? '—' ?></td>
            <td style="color:<?= $e['estado'] === 'activo' ? 'green' : 'red' ?>">
                <?= ucfirst($e['estado']) ?>
            </td>
            <td>
                <a href="asignaciones.php?id=<?= $e['id_empleado'] ?>">Asignaciones</a>
                <?php if (in_array('gestionar_empleados', $_SESSION['permisos'])): ?>
                    &nbsp;|&nbsp;
                    <a href="editar.php?id=<?= $e['id_empleado'] ?>">Editar</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($empleados)): ?>
    <p>No se encontraron empleados.</p>
<?php endif; ?>

</body>
</html>