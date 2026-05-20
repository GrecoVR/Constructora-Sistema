<?php
require_once '../../config/database.php';

$sql = "SELECT *
        FROM pagos_empleados
        WHERE estado = 'pendiente'";

$stmt = $pdo->query($sql);

$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($pagos);