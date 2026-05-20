<?php

session_start();

if (!isset($_SESSION['usuario'])) {

    header('Location: ../auth/login.php');
    exit;
}
?>

<h1>Sistema Constructora</h1>

<p>
Bienvenido <?= $_SESSION['usuario']['nombre_usuario'] ?>
</p>

<a href="../auth/logout.php">
Cerrar sesión
</a>