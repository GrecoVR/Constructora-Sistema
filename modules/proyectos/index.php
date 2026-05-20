<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('ver_proyectos');
registrarAccion('Vio lista de proyectos');

$pdo      = conectar();
$permisos = $_SESSION['permisos'];

// Gerentes ven todos, operativos solo los suyos
if (in_array('gestionar_contratos', $permisos)) {
    $stmt = $pdo->query("
        SELECT p.id_proyecto, p.nombre, p.estado, p.fecha_inicio,
               p.fecha_fin_estimada, tp.nombre as tipo,
               cl.nombre as cliente
        FROM proyectos p
        JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
        JOIN contratos c ON c.id_contrato = p.id_contrato
        JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
        JOIN clientes cl ON cl.id_cliente = co.id_cliente
        ORDER BY p.fecha_fin_estimada ASC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT p.id_proyecto, p.nombre, p.estado, p.fecha_inicio,
               p.fecha_fin_estimada, tp.nombre as tipo,
               cl.nombre as cliente
        FROM proyectos p
        JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
        JOIN contratos c ON c.id_contrato = p.id_contrato
        JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
        JOIN clientes cl ON cl.id_cliente = co.id_cliente
        JOIN asignaciones a ON a.id_proyecto = p.id_proyecto
        JOIN usuarios_sistema us ON us.id_empleado = a.id_empleado
        WHERE us.id_usuario_sistema = ?
        GROUP BY p.id_proyecto
        ORDER BY p.fecha_fin_estimada ASC
    ");
    $stmt->execute([$_SESSION['id_usuario']]);
}
$proyectos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyectos — Vértice</title>
</head>
<body>

<h2>📁 Proyectos</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
<?php if (in_array('crear_proyectos', $permisos)): ?>
    &nbsp;&nbsp;
    <a href="crear.php"><button>+ Nuevo proyecto</button></a>
<?php endif; ?>

<br><br>

<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Cliente</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th>Fecha inicio</th>
        <th>Fecha fin</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($proyectos as $p): ?>
        <tr>
            <td><?= $p['id_proyecto'] ?></td>
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= htmlspecialchars($p['cliente']) ?></td>
            <td><?= htmlspecialchars($p['tipo']) ?></td>
            <td><?= ucfirst($p['estado']) ?></td>
            <td><?= formatoFechaCorta($p['fecha_inicio']) ?></td>
            <td><?= estadoFecha($p['fecha_fin_estimada']) ?></td>
            <td>
                <a href="detalle.php?id=<?= $p['id_proyecto'] ?>">Ver</a>
                <?php if (in_array('editar_proyectos', $permisos)): ?>
                    &nbsp;|&nbsp;
                    <a href="editar.php?id=<?= $p['id_proyecto'] ?>">Editar</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($proyectos)): ?>
    <p>No tienes proyectos asignados.</p>
<?php endif; ?>

</body>
</html>