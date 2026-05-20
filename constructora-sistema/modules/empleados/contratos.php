<?php
require_once '../../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {

$sql = "INSERT INTO contratos_empleados(
            id_empleado,
            tipo_contrato,
            salario_hora,
            fecha_inicio,
            fecha_fin
        )
        VALUES(?,?,?,?,?)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $_POST['id_empleado'],
    $_POST['tipo_contrato'],
    $_POST['salario_hora'],
    $_POST['fecha_inicio'],
    $_POST['fecha_fin']
]);

header('Location: index.php');
}