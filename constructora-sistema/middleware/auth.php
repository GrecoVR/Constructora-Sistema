<?php

require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: /constructora-sistema/modules/auth/login.php');
    exit;
}