<?php

require_once '../../config/database.php';

$id = $_GET['id'];

$sql = "DELETE FROM pagos_empleados
        WHERE id_pago_empleado = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([$id]);

header('Location: index.php');

exit;