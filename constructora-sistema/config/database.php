<?php

$host = 'localhost';
$dbname = 'constructora52';
$user = 'root';
$password = '';

try {
    $pdo = new PDO(
    "mysql:host=localhost;port=3307;dbname=constructora52;charset=utf8",
    "root",
    ""
);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}