<?php
require_once 'config/session.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: modules/dashboard/dashboard.php');
} else {
    header('Location: modules/auth/login.php');
}
exit;