<?php
require_once '../../config/session.php';

unset(
    $_SESSION['id_cliente'],
    $_SESSION['id_usuario_cliente'],
    $_SESSION['nombre_cliente'],
    $_SESSION['email_cliente'],
    $_SESSION['tipo_sesion']
);

header('Location: login_cliente.php');
exit;