<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require_once '../../config/database.php';

$id = $_GET['id'];

$sql = "SELECT *
        FROM pagos_empleados
        WHERE id_pago_empleado = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([$id]);

$pago = $stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $sql = "UPDATE pagos_empleados
            SET monto = ?,
                estado = ?
            WHERE id_pago_empleado = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        $_POST['monto'],
        $_POST['estado'],
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

<title>Editar Pago</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="container mt-5">

<h2>Editar Pago</h2>

<form method="POST">

<div class="mb-3">

<label>Monto</label>

<input
type="number"
step="0.01"
name="monto"
class="form-control"
value="<?= $pago['monto'] ?>">

</div>

<select
name="estado"
class="form-control">

<option
value="pendiente"

<?= $pago['estado'] == 'pendiente' ? 'selected' : '' ?>>

Pendiente

</option>

<option
value="pagado"

<?= $pago['estado'] == 'pagado' ? 'selected' : '' ?>>

Pagado

</option>

<option
value="fallido"

<?= $pago['estado'] == 'fallido' ? 'selected' : '' ?>>

Fallido

</option>

</select>

<button
type="submit"
class="btn btn-primary">

Actualizar

</button>

</form>

</body>
</html>