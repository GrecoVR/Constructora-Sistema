<?php

require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = $_GET['id'];

$cargos = $pdo->query("
    SELECT *
    FROM cargos
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $idCargo = $_POST['id_cargo'];

    $sql = "INSERT INTO asignaciones(

                id_proyecto,
                id_empleado,
                id_cargo,
                fecha_inicio

            ) VALUES(

                1,
                ?,
                ?,
                CURDATE()

            )";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $id,
        $idCargo
    ]);

    header('Location: index.php');

    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Asignar Cargo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">

<div class="card-custom">

<div class="card-header-custom">

<h3>Asignar Cargo</h3>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">

<label>Cargo</label>

<select
name="id_cargo"
class="form-select">

<?php foreach($cargos as $c): ?>

<option value="<?= $c['id_cargo'] ?>">

<?= $c['nombre'] ?>

</option>

<?php endforeach; ?>

</select>

</div>

<button
type="submit"
class="btn btn-primary">

Guardar Asignación

</button>

</form>

</div>

</div>

</div>

</body>

</html>