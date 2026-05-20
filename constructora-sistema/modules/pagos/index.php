<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require_once '../../config/database.php';
require_once '../../includes/auth.php';

$estadoFiltro = $_GET['estado'] ?? '';

$sql = "SELECT
            pe.*,
            e.nombre AS empleado,
            mp.nombre AS metodo_pago

        FROM pagos_empleados pe

        LEFT JOIN empleados e
            ON pe.id_empleado = e.id_empleado

        LEFT JOIN metodos_pago mp
            ON pe.id_metodo_pago = mp.id_metodo_pago";

if($estadoFiltro != ''){
    $sql .= " WHERE pe.estado = :estado";
}

$sql .= " ORDER BY pe.fecha_pago DESC";

$stmt = $pdo->prepare($sql);

if($estadoFiltro != ''){
    $stmt->bindParam(':estado',$estadoFiltro);
}

$stmt->execute();

$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Pagos</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../includes/styles.css">

</head>

<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">

<div class="card-custom">

<div class="card-header-custom d-flex justify-content-between align-items-center">

<h2>Módulo de Pagos</h2>

<a href="crear.php" class="btn btn-success">
Nuevo Pago
</a>

</div>

<div class="card-body p-4">

<form method="GET" class="row mb-4">

<div class="col-md-4">

<select name="estado" class="form-select">

<option value="">Todos</option>

<option value="pendiente">Pendiente</option>

<option value="pagado">Pagado</option>

<option value="fallido">Fallido</option>

</select>

</div>

<div class="col-md-2">

<button class="btn btn-primary w-100">
Filtrar
</button>

</div>

</form>

<table class="table table-bordered table-hover">

<thead>

<tr>

<th>ID</th>
<th>Empleado</th>
<th>Método</th>
<th>Fecha</th>
<th>Monto</th>
<th>Estado</th>
<th>Acciones</th>

</tr>

</thead>

<tbody>

<?php foreach($pagos as $pago): ?>

<tr>

<td><?= $pago['id_pago_empleado'] ?></td>

<td><?= $pago['empleado'] ?></td>

<td><?= $pago['metodo_pago'] ?></td>

<td><?= $pago['fecha_pago'] ?></td>

<td>Bs. <?= number_format($pago['monto'],2) ?></td>

<td>

<?php if($pago['estado'] == 'pagado'): ?>

<span class="badge bg-success">
Pagado
</span>

<?php elseif($pago['estado'] == 'pendiente'): ?>

<span class="badge bg-warning text-dark">
Pendiente
</span>

<?php else: ?>

<span class="badge bg-danger">
Fallido
</span>

<?php endif; ?>

</td>

<td>

<a
href="editar.php?id=<?= $pago['id_pago_empleado'] ?>"
class="btn btn-warning btn-sm">

Editar

</a>

<a
href="eliminar.php?id=<?= $pago['id_pago_empleado'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Eliminar pago?')">

Eliminar

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</body>

</html>