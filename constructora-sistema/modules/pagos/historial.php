<?php
require_once '../../config/database.php';

$sql = "SELECT * FROM pagos_empleados
        ORDER BY fecha_pago DESC";

$stmt = $pdo->query($sql);

$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($historial);