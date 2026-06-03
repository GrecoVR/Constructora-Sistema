<?php
require_once 'config/session.php';

if (isset($_SESSION['tipo_sesion'])) {
    if ($_SESSION['tipo_sesion'] === 'cliente') {
        header('Location: client/index.php');
    } else {
        header('Location: modules/dashboard/dashboard.php');
    }
} else {
    header('Location: modules/auth/inicio.php');
}
exit;