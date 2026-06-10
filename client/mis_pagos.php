<?php
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';
require_once '../utils/fecha.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

$pagos = $pdo->prepare("
    SELECT pc.monto, pc.fecha_pago, pc.estado,
           mp.nombre as metodo, p.nombre as proyecto
    FROM pagos_cliente pc
    JOIN contratos c ON c.id_contrato = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN metodos_pago mp ON mp.id_metodo_pago = pc.id_metodo_pago
    LEFT JOIN proyectos p ON p.id_contrato = c.id_contrato
    WHERE co.id_cliente = ?
    ORDER BY pc.fecha_pago DESC
");
$pagos->execute([$id_cliente]);
$pagos = $pagos->fetchAll();

$total_pagado   = array_sum(array_column(
    array_filter($pagos, fn($p) => $p['estado'] === 'completado'), 'monto'
));
$total_pendiente = array_sum(array_column(
    array_filter($pagos, fn($p) => $p['estado'] === 'pendiente'), 'monto'
));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pagos — Portal Cliente</title>
</head>
<body>

<h2>💳 Mis Pagos</h2>
<a href="index.php">← Volver al portal</a>

<br><br>

<table border="1" cellpadding="8">
    <tr>
        <td><strong>Total pagado</strong></td>
        <td style="color:green">Bs <?= number_format($total_pagado, 2) ?></td>
    </tr>
    <tr>
        <td><strong>Total pendiente</strong></td>
        <td style="color:red">Bs <?= number_format($total_pendiente, 2) ?></td>
    </tr>
</table>

<br>

<table border="1" cellpadding="8">
    <tr>
        <th>Proyecto</th>
        <th>Fecha</th>
        <th>Método</th>
        <th>Monto (Bs)</th>
        <th>Estado</th>
    </tr>
    <?php foreach ($pagos as $p): ?>
        <tr>
            <td><?= $p['proyecto'] ? htmlspecialchars($p['proyecto']) : '—' ?></td>
            <td><?= formatoFechaCorta($p['fecha_pago']) ?></td>
            <td><?= htmlspecialchars($p['metodo']) ?></td>
            <td><?= number_format($p['monto'], 2) ?></td>
            <td style="color:<?= $p['estado'] === 'completado' ? 'green' : ($p['estado'] === 'fallido' ? 'red' : 'orange') ?>">
                <?= ucfirst($p['estado']) ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($pagos)): ?>
    <p>No tienes pagos registrados.</p>
<?php endif; ?>
</body>
</html>