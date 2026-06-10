<?php
require_once '../../config/session.php';

// Si ya hay sesión activa redirige directo
if (isset($_SESSION['tipo_sesion'])) {
    if ($_SESSION['tipo_sesion'] === 'cliente') {
        header('Location: ../../client/index.php');
    } else {
        header('Location: ../../dashboard.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empresa Constructora</title>
</head>
<body style="text-align:center; margin-top:100px">

    <h1>🏗️ Empresa Constructora</h1>
    <p>Selecciona cómo deseas ingresar al sistema</p>

    <br><br>

    <a href="login.php">
        <button style="padding:20px 40px; font-size:18px; margin:10px">
            👷 Soy Empleado
        </button>
    </a>
    <a href="login_cliente.php">
        <button style="padding:20px 40px; font-size:18px; margin:10px">
            🏢 Soy Cliente
        </button>
    </a>

</body>
</html>