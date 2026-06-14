<?php

require_once '../../config/database.php';
$pdo   = conectar();

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT nombre, enlace
    FROM menus_sistema
    WHERE nombre LIKE ?
    ORDER BY nombre
    LIMIT 10
");

$stmt->execute(["%{$q}%"]);

header('Content-Type: application/json');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));