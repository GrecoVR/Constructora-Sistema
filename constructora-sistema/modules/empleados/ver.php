<?php
require_once '../../config/database.php';

$id = $_GET['id'];

$sql = "SELECT e.*, c.nombre_cargo
        FROM empleados e
        INNER JOIN cargos c
            ON e.id_cargo = c.id_cargo
        WHERE e.id_empleado = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($empleado);