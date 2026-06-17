<?php
date_default_timezone_set('America/La_Paz');

require_once __DIR__ . '/../config/database.php';

function registrarAccion(string $accion): void {
    if (!isset($_SESSION['id_usuario'])) return;
    $accion_esp = explode(" ", $accion)[0];
    $pdo  = conectar();
    $stmt = $pdo->prepare("
        INSERT INTO registros_sistema (id_usuario_sistema, accion, descripcion)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['id_usuario'], $accion_esp, $accion]);
}