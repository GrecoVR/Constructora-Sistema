<?php
function conectar(): PDO {
    $host   = 'localhost';
    $db     = 'empresa_constructora';
    $user   = 'root';
    $pass   = 'greco1752';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage("No se pudo conectar a la base de datos"));
    }
}