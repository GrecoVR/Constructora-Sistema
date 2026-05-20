<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$cargos = $pdo->query("
    SELECT *
    FROM cargos
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $nombre = $_POST['nombre'];
    $ci = $_POST['ci'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $estado = $_POST['estado'];

    $sql = "INSERT INTO empleados(

                nombre,
                ci,
                direccion,
                telefono,
                email,
                estado

            ) VALUES(

                ?,?,?,?,?,?

            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $nombre,
        $ci,
        $direccion,
        $telefono,
        $email,
        $estado
    ]);

    header('Location: index.php');

    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Nuevo Empleado</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">

<div class="card-custom">

<div class="card-header-custom">

<h3>Nuevo Empleado</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">

<label>Nombre</label>

<input
type="text"
name="nombre"
class="form-control"
required>

</div>

<div class="mb-3">

<label>CI</label>

<input
type="text"
name="ci"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Dirección</label>

<input
type="text"
name="direccion"
class="form-control">

</div>

<div class="mb-3">

<label>Teléfono</label>

<input
type="text"
name="telefono"
class="form-control">

</div>

<div class="mb-3">

<label>Email</label>

<input
type="email"
name="email"
class="form-control">

</div>

<div class="mb-3">

<label>Estado</label>

<select
name="estado"
class="form-select">

<option value="activo">Activo</option>
<option value="inactivo">Inactivo</option>

</select>

</div>

<button
type="submit"
class="btn btn-success">

Guardar Empleado

</button>

<a
href="index.php"
class="btn btn-secondary">

Volver

</a>

</form>

</div>

</div>

</div>

</body>

</html>