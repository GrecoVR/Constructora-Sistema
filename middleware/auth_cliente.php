<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['id_cliente']) || $_SESSION['tipo_sesion'] !== 'cliente') {
    header('Location: ../modules/auth/login_cliente.php');
    exit;
}