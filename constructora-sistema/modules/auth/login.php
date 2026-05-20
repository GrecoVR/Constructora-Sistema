<?php

require_once '../../config/database.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $sql = "SELECT * 
            FROM usuarios_sistema 
            WHERE nombre_usuario = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {

        $_SESSION['usuario'] = $user;

        header('Location: ../proyectos/index.php');
        exit;

    } else {

        $error = 'Usuario o contraseña incorrectos';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Iniciar Sesión</h2>

<form method="POST">

<input type="text" name="usuario" placeholder="Usuario" required>
<br><br>

<input type="password" name="password" placeholder="Contraseña" required>
<br><br>

<button type="submit">Ingresar</button>

</form>

<p><?= $error ?></p>

</body>
</html>