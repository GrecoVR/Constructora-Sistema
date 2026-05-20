<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require_once '../../config/database.php';

echo "<pre>";

print_r($_POST);

echo "</pre>";

$empleados = $pdo->query("
    SELECT *
    FROM empleados
    WHERE estado = 'activo'
")->fetchAll(PDO::FETCH_ASSOC);

$metodos = $pdo->query("
    SELECT *
    FROM metodos_pago
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    try{

        $sql = "INSERT INTO pagos_empleados(

                    id_empleado,
                    id_metodo_pago,
                    fecha_pago,
                    monto,
                    estado

                )
                VALUES(?,?,?,?,?)";

        $stmt = $pdo->prepare($sql);

        $resultado = $stmt->execute([

            $_POST['id_empleado'],
            $_POST['id_metodo_pago'],
            $_POST['fecha_pago'],
            $_POST['monto'],
            $_POST['estado']

        ]);

        var_dump($resultado);

        echo "<h2>PAGO INSERTADO</h2>";

        exit;

    }catch(PDOException $e){

        die($e->getMessage());

    }

}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Nuevo Pago</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="container mt-5">

<h2>Registrar Pago</h2>

<form method="POST">

<div class="mb-3">

<label>Empleado</label>

<select
name="id_empleado"
class="form-control"
required>

<option value="">
Seleccione empleado
</option>

<?php foreach($empleados as $empleado): ?>

<option value="<?= $empleado['id_empleado'] ?>">

<?= $empleado['nombre'] ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Método de Pago</label>

<select
name="id_metodo_pago"
class="form-control"
required>

<option value="">
Seleccione método
</option>

<?php foreach($metodos as $metodo): ?>

<option value="<?= $metodo['id_metodo_pago'] ?>">

<?= $metodo['nombre'] ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Fecha</label>

<input
type="date"
name="fecha_pago"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Monto</label>

<input
type="number"
step="0.01"
name="monto"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Estado</label>

<select
name="estado"
class="form-control">

<option value="pendiente">
Pendiente
</option>

<option value="pagado">
Pagado
</option>

<option value="fallido">
Fallido
</option>

</select>

</div>

<button
type="submit"
class="btn btn-success">

Guardar Pago

</button>

</form>

</body>
</html>