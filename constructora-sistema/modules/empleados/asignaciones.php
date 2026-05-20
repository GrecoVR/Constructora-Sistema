<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require_once '../../config/database.php';

$id_empleado = $_GET['id'];

$cargos = $pdo->query("
    SELECT *
    FROM cargos
")->fetchAll(PDO::FETCH_ASSOC);

$proyectos = $pdo->query("
    SELECT *
    FROM proyectos
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $sql = "INSERT INTO asignaciones(

                id_proyecto,
                id_empleado,
                id_cargo,
                fecha_inicio

            )
            VALUES(?,?,?,NOW())";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $_POST['id_proyecto'],
        $id_empleado,
        $_POST['id_cargo']

    ]);

    header('Location: index.php');

    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Asignar Empleado</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="container mt-5">

<h2>Asignar Empleado</h2>

<form method="POST">

<div class="mb-3">

<label>Cargo</label>

<select
name="id_cargo"
class="form-control"
required>

<option value="">
Seleccione cargo
</option>

<?php foreach($cargos as $cargo): ?>

<option value="<?= $cargo['id_cargo'] ?>">

<?= $cargo['nombre'] ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Proyecto</label>

<select
name="id_proyecto"
class="form-control"
required>

<option value="">
Seleccione proyecto
</option>

<?php foreach($proyectos as $proyecto): ?>

<option value="<?= $proyecto['id_proyecto'] ?>">

<?= $proyecto['nombre'] ?>

</option>

<?php endforeach; ?>

</select>

</div>

<button
type="submit"
class="btn btn-success">

Guardar Asignación

</button>

</form>

</body>
</html>