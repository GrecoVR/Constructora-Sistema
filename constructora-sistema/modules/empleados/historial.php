<?php
require_once '../../config/database.php';

$id = $_GET['id'];

$sql = "SELECT *
        FROM asistencia
        WHERE id_asignacion IN (
            SELECT id_asignacion
            FROM asignaciones
            WHERE id_empleado = ?
        )";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($historial);