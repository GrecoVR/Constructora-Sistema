<?php
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';
require_once '../utils/fecha.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

// Sus proyectos
$proyectos = $pdo->prepare("
    SELECT p.id_proyecto, p.nombre, p.estado,
           p.fecha_inicio, p.fecha_fin_estimada,
           tp.nombre as tipo,
           AVG(e.porcentaje_avance) as avance_promedio
    FROM proyectos p
    JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
    JOIN contratos c ON c.id_contrato = p.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    LEFT JOIN etapas_proyecto e ON e.id_proyecto = p.id_proyecto
    WHERE co.id_cliente = ?
    GROUP BY p.id_proyecto, p.nombre, p.estado,
             p.fecha_inicio, p.fecha_fin_estimada, tp.nombre
    ORDER BY p.fecha_inicio DESC
");
$proyectos->execute([$id_cliente]);
$proyectos = $proyectos->fetchAll();

// Sus pagos pendientes
$pagos_pendientes = $pdo->prepare("
    SELECT pc.monto, pc.fecha_pago
    FROM pagos_cliente pc
    JOIN contratos c ON c.id_contrato = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    WHERE co.id_cliente = ? AND pc.estado = 'pendiente'
    ORDER BY pc.fecha_pago ASC
");
$pagos_pendientes->execute([$id_cliente]);
$pagos_pendientes = $pagos_pendientes->fetchAll();

// Sus notificaciones
$notificaciones = $pdo->prepare("
    SELECT titulo, contenido
    FROM notificaciones_clientes
    WHERE id_cliente = ?
    ORDER BY id_notificacion DESC
    LIMIT 5
");
$notificaciones->execute([$id_cliente]);
$notificaciones = $notificaciones->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
  <div class="container-fluid bg-light">
  <div class="wrapper d-flex flex-column align-items-center vh-100">
  <div class="p-4">
  <h2 class="mb-4 fw-semibold">🏗️ Bienvenido, <?= htmlspecialchars($_SESSION['nombre_cliente']) ?></h2>
  <a class="btn btn-primary me-2" href="mis_proyectos.php">Mis proyectos</a>

<a class="btn btn-secondary me-2" href="mis_pagos.php">Mis pagos</a>

<a class="btn btn-success me-2" href="mis_notificaciones.php">Mis notificaciones</a>

<a class="btn btn-light" href="../modules/auth/logout_cliente.php">Cerrar sesión</a>

<hr>

<!-- PROYECTOS -->
<h3 class="mb-4">📁 Mis proyectos</h3>
<?php if ($proyectos): ?>
    <table class="table table-striped table-bordered">
        <tr>
            <th>Proyecto</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Avance</th>
            <th>Fecha fin</th>
        </tr>
        <?php foreach ($proyectos as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['nombre']) ?></td>
                <td><?= htmlspecialchars($p['tipo']) ?></td>
                <td><?= ucfirst($p['estado']) ?></td>
                <td>
                    <?= round($p['avance_promedio'] ?? 0) ?>%
                    <progress value="<?= round($p['avance_promedio'] ?? 0) ?>" max="100"></progress>
                </td>
                <td><?= estadoFecha($p['fecha_fin_estimada']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No tienes proyectos activos.</p>
<?php endif; ?>

<hr>

<!-- PAGOS PENDIENTES -->
<h3 class="mb-4">💳 Pagos pendientes</h3>
<?php if ($pagos_pendientes): ?>
    <table class="table table-striped table-bordered">
        <tr>
            <th>Monto (Bs)</th>
            <th>Fecha esperada</th>
        </tr>
        <?php foreach ($pagos_pendientes as $p): ?>
            <tr>
                <td style="color:red"><?= number_format($p['monto'], 2) ?></td>
                <td><?= formatoFechaCorta($p['fecha_pago']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="color:green">No tienes pagos pendientes. ✅</p>
<?php endif; ?>

<hr>

<!-- NOTIFICACIONES RECIENTES -->
<h3 class="mb-4">🔔 Últimas notificaciones</h3>
<?php if ($notificaciones): ?>
    <?php foreach ($notificaciones as $n): ?>
        <div class="border p-4 mb-4">
            <strong><?= htmlspecialchars($n['titulo']) ?></strong><br>
            <?= htmlspecialchars($n['contenido']) ?>
        </div>
    <?php endforeach; ?>
    <a href="mis_notificaciones.php">Ver todas →</a>
<?php else: ?>
    <p>No tienes notificaciones.</p>
<?php endif; ?>
</div>
</div>
</div>
</body>
</html>