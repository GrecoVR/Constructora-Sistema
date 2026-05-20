<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$busqueda = $_GET['busqueda'] ?? '';
$estadoFiltro = $_GET['estado'] ?? '';

$sql = "SELECT

            e.*,
            c.nombre AS cargo

        FROM empleados e

        LEFT JOIN asignaciones a
            ON e.id_empleado = a.id_empleado

        LEFT JOIN cargos c
            ON a.id_cargo = c.id_cargo

        WHERE e.nombre LIKE ?

        GROUP BY e.id_empleado";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "%$busqueda%"
]);

$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
<link rel="stylesheet" href="../../includes/styles.css">
<meta charset="UTF-8">

<title>Empleados</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4">

<h2>Lista de Empleados</h2>

<a
href="crear.php"
class="btn btn-success">

Nuevo Empleado

</a>

</div>

<form method="GET" class="mb-4 d-flex">

<input
type="text"
name="busqueda"
class="form-control me-2"
placeholder="Buscar empleado"
value="<?= $busqueda ?>">

<button class="btn btn-primary">

Buscar

</button>

</form>

<div class="card-custom">

<div class="card-body">

<table class="table table-hover table-bordered">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Nombre</th>
<th>Cargo</th>
<th>CI</th>
<th>Acciones</th>

</tr>

</thead>

<tbody>

<?php foreach($empleados as $e): ?>

<tr>

<td><?= $e['id_empleado'] ?></td>

<td><?= $e['nombre'] ?></td>

<td><?= $e['cargo'] ?? 'Sin cargo' ?></td>

<td><?= $e['ci'] ?></td>

<td>

<a
href="editar.php?id=<?= $e['id_empleado'] ?>"
class="btn btn-warning btn-sm">

Editar

</a>

<a
href="eliminar.php?id=<?= $e['id_empleado'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Eliminar empleado?')">

Eliminar

</a>

<a
href="asignar.php?id=<?= $e['id_empleado'] ?>"
class="btn btn-info btn-sm text-white">

Asignar

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