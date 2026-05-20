<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_reportes_financieros');
registrarAccion('Vio reporte dashboard');

$pdo = conectar();

// Total proyectos por estado
$proyectos_estado = $pdo->query("
    SELECT estado, COUNT(*) as total
    FROM proyectos
    GROUP BY estado
")->fetchAll();

// Total ingresos vs gastos
$financiero = $pdo->query("
    SELECT
        (SELECT COALESCE(SUM(monto),0) FROM pagos_cliente WHERE estado = 'completado') as ingresos,
        (SELECT COALESCE(SUM(monto),0) FROM pagos_empleados WHERE estado = 'completado') as gastos_personal,
        (SELECT COALESCE(SUM(monto),0) FROM gastos) as gastos_obra,
        (SELECT COALESCE(SUM(monto),0) FROM pagos_pedidos WHERE estado = 'completado') as gastos_pedidos
")->fetch();

$total_gastos  = $financiero['gastos_personal'] + $financiero['gastos_obra'] + $financiero['gastos_pedidos'];
$balance       = $financiero['ingresos'] - $total_gastos;

// Top 5 proyectos con más gastos
$top_proyectos = $pdo->query("
    SELECT p.nombre, COALESCE(SUM(g.monto), 0) as total_gastos
    FROM proyectos p
    LEFT JOIN gastos g ON g.id_proyecto = p.id_proyecto
    GROUP BY p.id_proyecto, p.nombre
    ORDER BY total_gastos DESC
    LIMIT 5
")->fetchAll();

// Materiales más usados
$top_materiales = $pdo->query("
    SELECT m.nombre, SUM(um.cantidad) as total_usado
    FROM uso_materiales um
    JOIN materiales m ON m.id_material = um.id_material
    GROUP BY um.id_material, m.nombre
    ORDER BY total_usado DESC
    LIMIT 5
")->fetchAll();

// Pagos pendientes
$pagos_pendientes = $pdo->query("
    SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total
    FROM pagos_empleados
    WHERE estado = 'pendiente'
")->fetch();

// Cotizaciones por estado
$cotizaciones_estado = $pdo->query("
    SELECT estado, COUNT(*) as total, COALESCE(SUM(monto_total), 0) as monto
    FROM cotizaciones
    GROUP BY estado
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Dashboard — Vértice</title>
</head>
<body>

<h2>📊 Reporte General</h2>
<a href="../../dashboard.php">← Volver al dashboard</a>
&nbsp;&nbsp;
<a href="financiero.php">Ver reporte financiero</a>
&nbsp;&nbsp;
<a href="inventario.php">Ver reporte inventario</a>

<hr>

<!-- PROYECTOS POR ESTADO -->
<h3>📁 Proyectos por estado</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Estado</th>
        <th>Total</th>
    </tr>
    <?php foreach ($proyectos_estado as $pe): ?>
        <tr>
            <td><?= ucfirst($pe['estado']) ?></td>
            <td><?= $pe['total'] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<!-- RESUMEN FINANCIERO -->
<h3>💰 Resumen Financiero</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Concepto</th>
        <th>Monto (Bs)</th>
    </tr>
    <tr>
        <td>✅ Ingresos recibidos</td>
        <td><?= number_format($financiero['ingresos'], 2) ?></td>
    </tr>
    <tr>
        <td>👷 Gastos personal</td>
        <td><?= number_format($financiero['gastos_personal'], 2) ?></td>
    </tr>
    <tr>
        <td>🏗️ Gastos de obra</td>
        <td><?= number_format($financiero['gastos_obra'], 2) ?></td>
    </tr>
    <tr>
        <td>🛒 Gastos pedidos</td>
        <td><?= number_format($financiero['gastos_pedidos'], 2) ?></td>
    </tr>
    <tr>
        <td><strong>📊 Balance</strong></td>
        <td style="color: <?= $balance >= 0 ? 'green' : 'red' ?>">
            <strong><?= number_format($balance, 2) ?></strong>
        </td>
    </tr>
</table>

<hr>

<!-- TOP PROYECTOS -->
<h3>🏆 Top 5 proyectos con más gastos</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Proyecto</th>
        <th>Total gastos (Bs)</th>
    </tr>
    <?php foreach ($top_proyectos as $tp): ?>
        <tr>
            <td><?= htmlspecialchars($tp['nombre']) ?></td>
            <td><?= number_format($tp['total_gastos'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<!-- TOP MATERIALES -->
<h3>🧱 Top 5 materiales más usados</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Material</th>
        <th>Cantidad total usada</th>
    </tr>
    <?php foreach ($top_materiales as $tm): ?>
        <tr>
            <td><?= htmlspecialchars($tm['nombre']) ?></td>
            <td><?= number_format($tm['total_usado'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<hr>

<!-- PAGOS PENDIENTES -->
<h3>⏳ Pagos empleados pendientes</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Total pagos pendientes</th>
        <th>Monto total (Bs)</th>
    </tr>
    <tr>
        <td><?= $pagos_pendientes['total'] ?></td>
        <td style="color:red"><?= number_format($pagos_pendientes['monto_total'], 2) ?></td>
    </tr>
</table>

<hr>

<!-- COTIZACIONES -->
<h3>📋 Cotizaciones por estado</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>Estado</th>
        <th>Total</th>
        <th>Monto (Bs)</th>
    </tr>
    <?php foreach ($cotizaciones_estado as $ce): ?>
        <tr>
            <td><?= ucfirst($ce['estado']) ?></td>
            <td><?= $ce['total'] ?></td>
            <td><?= number_format($ce['monto'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>