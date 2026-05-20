<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require_once '../../config/database.php';
require_once '../../includes/auth.php';
include '../../includes/sidebar.php';

$id = $_GET['id'];

$sql = "SELECT *
        FROM empleados
        WHERE id_empleado = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([$id]);

$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$empleado){
    die("Empleado no encontrado");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $sql = "UPDATE empleados
            SET
                nombre = ?,
                ci = ?,
                direccion = ?,
                telefono = ?,
                email = ?
            WHERE id_empleado = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $_POST['nombre'],
        $_POST['ci'],
        $_POST['direccion'],
        $_POST['telefono'],
        $_POST['email'],
        $id
    ]);

    header('Location: index.php');

    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<title>Editar Empleado</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">
<div class="card-custom">

<div class="card-header-custom">

<h3>Editar Empleado</h3>
</div>

<div class="card-body">


<form method="POST">

<div class="mb-3">
<label>Nombre</label>

<input
type="text"
name="nombre"
class="form-control"
value="<?= $empleado['nombre'] ?>"
required>
</div>

<div class="mb-3">
<label>CI</label>

<input
type="text"
name="ci"
class="form-control"
value="<?= $empleado['ci'] ?>"
required>
</div>

<div class="mb-3">
<label>Dirección</label>

<input
type="text"
name="direccion"
class="form-control"
value="<?= $empleado['direccion'] ?>">
</div>

<div class="mb-3">
<label>Teléfono</label>

<input
type="text"
name="telefono"
class="form-control"
value="<?= $empleado['telefono'] ?>">
</div>

<div class="mb-3">
<label>Email</label>

<input
type="email"
name="email"
class="form-control"
value="<?= $empleado['email'] ?>">
</div>

<button type="submit" class="btn btn-primary">
Actualizar
</button>

<a href="index.php" class="btn btn-secondary">
Volver
</a>

</form>
</div>
</div>
</div>
</div>
</body>
</html>