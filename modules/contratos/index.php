<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';
require_once '../../utils/fecha.php';

requierePermiso('ver_contratos');
registrarAccion('Vio lista de contratos');

$pdo = conectar();

$contratos = $pdo->query("
    SELECT c.id_contrato, c.fecha_firma, c.estado,
           cl.nombre as cliente,
           co.monto_total,
           p.nombre as proyecto,
           p.fecha_fin_estimada
    FROM contratos c
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    LEFT JOIN proyectos p ON p.id_contrato = c.id_contrato
    ORDER BY c.fecha_firma DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contratos — Vértice</title>
</head>
<body>

<h2>📄 Contratos</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
<?php if (in_array('gestionar_contratos', $_SESSION['permisos'])): ?>
    &nbsp;&nbsp;
    <a href="crear.php"><button>+ Nuevo contrato</button></a>
    &nbsp;&nbsp;
    <a href="cotizaciones.php"><button>Ver cotizaciones</button></a>
<?php endif; ?>

<br><br>

<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>Proyecto</th>
        <th>Monto (Bs)</th>
        <th>Fecha firma</th>
        <th>Fecha fin</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($contratos as $c): ?>
        <tr>
            <td><?= $c['id_contrato'] ?></td>
            <td><?= htmlspecialchars($c['cliente']) ?></td>
            <td><?= $c['proyecto'] ? htmlspecialchars($c['proyecto']) : '—' ?></td>
            <td><?= number_format($c['monto_total'], 2) ?></td>
            <td><?= formatoFechaCorta($c['fecha_firma']) ?></td>
            <td><?= $c['fecha_fin_estimada'] ? estadoFecha($c['fecha_fin_estimada']) : '—' ?></td>
            <td><?= ucfirst($c['estado']) ?></td>
            <td>
                <a href="pagos_cliente.php?id=<?= $c['id_contrato'] ?>">Pagos</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($contratos)): ?>
    <p>No hay contratos registrados.</p>
<?php endif; ?>

</body>
</html>