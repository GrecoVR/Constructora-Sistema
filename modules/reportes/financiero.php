<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_reportes_financieros');
registrarAccion('Vio reporte financiero');

$pdo = conectar();

// Filtro por proyecto
$id_proyecto = intval($_GET['id_proyecto'] ?? 0);

// Lista proyectos para el filtro
$proyectos = $pdo->query("
    SELECT id_proyecto, nombre FROM proyectos ORDER BY nombre ASC
")->fetchAll();

// Ingresos por proyecto
if ($id_proyecto) {
    $stmt_ingresos = $pdo->prepare("
        SELECT p.nombre as proyecto, 
               COALESCE(SUM(pc.monto), 0) as total_ingresos,
               COUNT(pc.id_pago_cliente) as total_pagos
        FROM proyectos p
        LEFT JOIN contratos c ON c.id_contrato = p.id_contrato
        LEFT JOIN pagos_cliente pc ON pc.id_contrato = c.id_contrato
            AND pc.estado = 'completado'
        WHERE p.id_proyecto = ?
        GROUP BY p.id_proyecto, p.nombre
    ");
    $stmt_ingresos->execute([$id_proyecto]);

    $stmt_gastos = $pdo->prepare("
        SELECT COALESCE(SUM(monto), 0) as total_gastos
        FROM gastos WHERE id_proyecto = ?
    ");
    $stmt_gastos->execute([$id_proyecto]);

} else {
    $stmt_ingresos = $pdo->query("
        SELECT p.nombre as proyecto,
               COALESCE(SUM(pc.monto), 0) as total_ingresos,
               COUNT(pc.id_pago_cliente) as total_pagos
        FROM proyectos p
        LEFT JOIN contratos c ON c.id_contrato = p.id_contrato
        LEFT JOIN pagos_cliente pc ON pc.id_contrato = c.id_contrato
            AND pc.estado = 'completado'
        GROUP BY p.id_proyecto, p.nombre
        ORDER BY total_ingresos DESC
    ");

    $stmt_gastos = $pdo->query("
        SELECT COALESCE(SUM(monto), 0) as total_gastos FROM gastos
    ");
}

$ingresos = $stmt_ingresos->fetchAll();
$gastos   = $stmt_gastos->fetch();

// Pagos empleados por mes
$pagos_mes = $pdo->query("
    SELECT DATE_FORMAT(fecha_pago, '%Y-%m') as mes,
           SUM(monto) as total
    FROM pagos_empleados
    WHERE estado = 'completado'
    GROUP BY mes
    ORDER BY mes DESC
    LIMIT 6
")->fetchAll();

// Pagos pendientes clientes
$pendientes_clientes = $pdo->query("
    SELECT c.id_contrato, cl.nombre as cliente,
           pc.monto, pc.fecha_pago
    FROM pagos_cliente pc
    JOIN contratos c ON c.id_contrato = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE pc.estado = 'pendiente'
    ORDER BY pc.fecha_pago ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero — Vértice</title>
</head>
<body>

<h2>💰 Reporte Financiero</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="dashboard.php">Ver reporte general</a>

<br><br>

<!-- FILTRO -->
<form method="GET">
    <label>Filtrar por proyecto:</label>
    <select name="id_proyecto">
        <option value="">-- Todos --</option>
        <?php foreach ($proyectos as $p): ?>
            <option value="<?= $p['id_proyecto'] ?>"
                <?= $p['id_proyecto'] == $id_proyecto ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
    <?php if ($id_proyecto): ?>
        <a href="financiero.php">Limpiar filtro</a>
    <?php endif; ?>
</form>

<hr>

<!-- INGRESOS POR PROYECTO -->
<h3>✅ Ingresos por proyecto</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Proyecto</th>
        <th>Total pagos</th>
        <th>Ingresos (Bs)</th>
    </tr>
    <?php foreach ($ingresos as $ing): ?>
        <tr>
            <td><?= htmlspecialchars($ing['proyecto']) ?></td>
            <td><?= $ing['total_pagos'] ?></td>
            <td><?= number_format($ing['total_ingresos'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<!-- GASTOS -->
<h3>🏗️ Total gastos de obra</h3>
<p><strong>Bs <?= number_format($gastos['total_gastos'], 2) ?></strong></p>

<hr>

<!-- PAGOS EMPLEADOS POR MES -->
<h3>👷 Pagos a empleados por mes</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Mes</th>
        <th>Total pagado (Bs)</th>
    </tr>
    <?php foreach ($pagos_mes as $pm): ?>
        <tr>
            <td><?= $pm['mes'] ?></td>
            <td><?= number_format($pm['total'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<!-- PAGOS PENDIENTES CLIENTES -->
<h3>⚠️ Pagos pendientes de clientes</h3>
<?php if ($pendientes_clientes): ?>
    <table border="1" cellpadding="8">
        <tr>
            <th>Cliente</th>
            <th>Monto (Bs)</th>
            <th>Fecha esperada</th>
        </tr>
        <?php foreach ($pendientes_clientes as $pc): ?>
            <tr>
                <td><?= htmlspecialchars($pc['cliente']) ?></td>
                <td style="color:red"><?= number_format($pc['monto'], 2) ?></td>
                <td><?= $pc['fecha_pago'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="color:green">No hay pagos pendientes de clientes.</p>
<?php endif; ?>

</body>
</html>