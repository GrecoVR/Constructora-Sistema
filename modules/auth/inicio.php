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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresa Constructora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
     <div class="container-fluid bg-light">
        <div class="wrapper d-flex flex-column align-items-center justify-content-center vh-100">

        <h2 class="mb-4 fw-semibold">🏗️ Empresa Constructora</h2>
        
        <h5 class="mb-4 fw-semibold">Selecciona cómo deseas ingresar al sistema</h5>

        <div>
        <a class="btn btn-primary btn-lg me-4" href="login.php">👷 Soy Empleado</a>
        <a class="btn btn-secondary btn-lg" href="login_cliente.php">🏢 Soy Cliente</a>
        </div>
      </div>
   </div>
</body>
</html>