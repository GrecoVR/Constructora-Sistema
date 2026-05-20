<?php
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../modules/auth/login.php');
    exit;
}