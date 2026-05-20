<?php

require_once 'config/database.php';
require_once 'includes/auth.php';

$totalEmpleados = $pdo->query("
    SELECT COUNT(*) 
    FROM empleados
")->fetchColumn();

$totalPagos = $pdo->query("
    SELECT COUNT(*) 
    FROM pagos_empleados
")->fetchColumn();

$totalAsistencia = $pdo->query("
    SELECT COUNT(*) 
    FROM asistencia
")->fetchColumn();

$totalPagado = $pdo->query("
    SELECT SUM(monto)
    FROM pagos_empleados
    WHERE estado = 'pagado'
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="includes/styles.css">

<style>

.dashboard-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:25px;
}

.dashboard-card{
    border-radius:18px;
    padding:30px;
    color:white;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

.dashboard-card h5{
    font-size:20px;
    margin-bottom:15px;
}

.dashboard-card h2{
    font-size:40px;
    font-weight:bold;
}

.bg-blue{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
}

.bg-green{
    background:linear-gradient(135deg,#16a34a,#15803d);
}

.bg-orange{
    background:linear-gradient(135deg,#ea580c,#c2410c);
}

.bg-purple{
    background:linear-gradient(135deg,#9333ea,#7e22ce);
}

.welcome-box{
    background:white;
    padding:30px;
    border-radius:18px;
    margin-bottom:30px;
    box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

</style>

</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

<div class="welcome-box">

<h1 class="mb-3">
Sistema Constructora
</h1>

<p class="text-muted mb-0">

Panel principal de administración del sistema.

</p>

</div>

<div class="dashboard-grid">

<div class="dashboard-card bg-blue">

<h5>Total Empleados</h5>

<h2>
<?= $totalEmpleados ?>
</h2>

</div>

<div class="dashboard-card bg-green">

<h5>Total Pagos</h5>

<h2>
<?= $totalPagos ?>
</h2>

</div>

<div class="dashboard-card bg-orange">

<h5>Asistencias</h5>

<h2>
<?= $totalAsistencia ?>
</h2>

</div>

<div class="dashboard-card bg-purple">

<h5>Monto Pagado</h5>

<h2>

Bs. <?= number_format($totalPagado ?? 0,2) ?>

</h2>

</div>

</div>

<div class="card-custom mt-4">

<div class="card-header-custom">

<h3>
Resumen del Sistema
</h3>

</div>

<div class="card-body p-4">

<p>

Este sistema permite administrar empleados, pagos y asistencias de la constructora de manera centralizada.

</p>

<ul>

<li>Gestión de empleados</li>

<li>Control de pagos</li>

<li>Registro de asistencia</li>

<li>Asignación de cargos</li>

<li>Dashboard administrativo</li>

</ul>

</div>

</div>

</div>

</body>

</html>