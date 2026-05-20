<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require_once '../../config/database.php';

if(!isset($_GET['id'])){

    die("ID no recibido");

}

$id = $_GET['id'];

$sql = "UPDATE empleados
        SET estado = 'inactivo'
        WHERE id_empleado = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([$id]);

header('Location: index.php');

exit;