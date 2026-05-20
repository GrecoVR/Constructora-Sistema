<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$totalEmpleados = $pdo->query("SELECT COUNT(*) FROM empleados")
                       ->fetchColumn();

$pagosPendientes = $pdo->query("SELECT COUNT(*)
                                FROM pagos_empleados
                                WHERE estado = 'pendiente'")
                        ->fetchColumn();

$totalPagos = $pdo->query("SELECT SUM(monto)
                           FROM pagos_empleados")
                    ->fetchColumn();

$asistenciaHoy = $pdo->query("SELECT COUNT(*)
                              FROM asistencia
                              WHERE fecha = CURDATE()")
                      ->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<title>Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">

<?php include '../../includes/sidebar.php'; ?>

<div style="margin-left:270px; padding:20px;">

<h2>Dashboard Recursos Humanos</h2>

<div class="row mt-4">

<div class="col-md-3">
<div class="card bg-primary text-white shadow">
<div class="card-body">
<h5>Total Empleados</h5>
<h2><?= $totalEmpleados ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card bg-warning shadow">
<div class="card-body">
<h5>Pagos Pendientes</h5>
<h2><?= $pagosPendientes ?></h2>
</html>