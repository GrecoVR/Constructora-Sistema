<?php
require_once '../../config/database.php';

$id = $_GET['id'];

$sql = "SELECT pe.*, e.nombre
        FROM pagos_empleados pe
        INNER JOIN empleados e
            ON pe.id_empleado = e.id_empleado
        WHERE pe.id_pago_empleado = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$pago = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<h2>Comprobante de Pago</h2>

<p>Empleado: <?= $pago['nombre'] ?></p>
<p>Monto: <?= $pago['monto'] ?></p>
<p>Fecha: <?= $pago['fecha_pago'] ?></p>