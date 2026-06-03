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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
<div class="container-fluid bg-light">
 <div class="wrapper d-flex flex-column align-items-center vh-100">
  <div class="p-4">

<h2 class="mb-4 fw-semibold">💳 Mis Pagos</h2>
<a href="index.php">← Volver al portal</a>

<table class="table table-striped table-bordered my-4">
    <tr>
        <td><strong>Total pagado</strong></td>
        <td style="color:green">Bs <?= number_format($total_pagado, 2) ?></td>
    </tr>
    <tr>
        <td><strong>Total pendiente</strong></td>
        <td style="color:red">Bs <?= number_format($total_pendiente, 2) ?></td>
    </tr>
</table>

<table class="table table-striped table-bordered mb-4">
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
</div>
</div>
</div>
</body>
</html>