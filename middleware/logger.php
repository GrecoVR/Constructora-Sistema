<?php
require_once __DIR__ . '/../config/database.php';

function registrarAccion(string $accion): void {
    if (!isset($_SESSION['id_usuario'])) return;

    $pdo  = conectar();
    $stmt = $pdo->prepare("
        INSERT INTO registros_sistema (id_usuario_sistema, accion)
        VALUES (?, ?)
    ");
    $stmt->execute([$_SESSION['id_usuario'], $accion]);
}