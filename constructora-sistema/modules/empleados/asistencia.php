<?php

require_once '../../config/database.php';
require_once '../../includes/auth.php';

$asignaciones = $pdo->query("

    SELECT
        a.id_asignacion,
        e.nombre AS empleado,
        c.nombre AS cargo

    FROM asignaciones a

    INNER JOIN empleados e
        ON a.id_empleado = e.id_empleado

    INNER JOIN cargos c
        ON a.id_cargo = c.id_cargo

")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $sql = "INSERT INTO asistencia(
                id_asignacion,
                fecha,
                hora_entrada,
                hora_salida
            )
            VALUES(?,?,?,?)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $_POST['id_asignacion'],
        $_POST['fecha'],
        $_POST['hora_entrada'],
        $_POST['hora_salida']

    ]);

    header('Location: lista_asistencia.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Registrar Asistencia</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../../includes/styles.css">

</head>

<body>

<?php include '../../includes/sidebar.php'; ?>

<div class="main-content">

<div class="card-custom">

<div class="card-header-custom">

<h2>Registro de Asistencia</h2>

</div>

<div class="card-body p-4">

<form method="POST">

<div class="mb-3">

<label class="form-label">Empleado</label>

<select
name="id_asignacion"
class="form-select"
required>

<option value="">
Seleccione empleado
</option>

<?php foreach($asignaciones as $a): ?>

<option value="<?= $a['id_asignacion'] ?>">

<?= $a['empleado'] ?> - <?= $a['cargo'] ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">Fecha</label>

<input
type="date"
name="fecha"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">Hora Entrada</label>

<input
type="time"
name="hora_entrada"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">Hora Salida</label>

<input
type="time"
name="hora_salida"
class="form-control"
required>

</div>

<button type="submit" class="btn btn-success">
Guardar
</button>

<a href="lista_asistencia.php" class="btn btn-secondary">
Volver
</a>

</form>

</div>

</div>

</div>

</body>

</html>