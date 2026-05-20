<?php

require_once '../../config/database.php';
require_once '../../includes/auth.php';

$sql = "

SELECT

    asi.*,
    e.nombre AS empleado,
    c.nombre AS cargo

FROM asistencia asi

INNER JOIN asignaciones a
    ON asi.id_asignacion = a.id_asignacion

INNER JOIN empleados e
    ON a.id_empleado = e.id_empleado

INNER JOIN cargos c
    ON a.id_cargo = c.id_cargo

ORDER BY asi.fecha DESC

";

$stmt = $pdo->query($sql);

$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Lista Asistencia</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../includes/styles.css">

</head>

<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">

<div class="card-custom">

<div class="card-header-custom d-flex justify-content-between align-items-center">

<h2>Lista de Asistencia</h2>

<a href="asistencia.php" class="btn btn-success">
Nueva Asistencia
</a>

</div>

<div class="card-body p-4">

<table class="table table-bordered table-hover">

<thead>

<tr>

<th>ID</th>
<th>Empleado</th>
<th>Cargo</th>
<th>Fecha</th>
<th>Entrada</th>
<th>Salida</th>

</tr>

</thead>

<tbody>

<?php foreach($asistencias as $a): ?>

<tr>

<td><?= $a['id_asistencia'] ?></td>

<td><?= $a['empleado'] ?></td>

<td><?= $a['cargo'] ?></td>

<td><?= $a['fecha'] ?></td>

<td><?= $a['hora_entrada'] ?></td>

<td><?= $a['hora_salida'] ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</body>

</html>