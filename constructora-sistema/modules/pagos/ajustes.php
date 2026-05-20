<?php
require_once '../../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {

$sql = "INSERT INTO ajustes_pago(
            id_pago_empleado,
            tipo_ajuste,
            concepto,
            monto
        )
        VALUES(?,?,?,?)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $_POST['id_pago_empleado'],
    $_POST['tipo_ajuste'],
    $_POST['concepto'],
    $_POST['monto']
]);

header('Location: index.php');
}