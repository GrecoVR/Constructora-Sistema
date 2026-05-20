<?php

session_start();

require_once 'config/database.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $usuario = $_POST['usuario'];

    $sql = "SELECT *
            FROM usuarios_sistema
            WHERE nombre_usuario = ?
            AND estado = 'activo'";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $usuario
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user){

        if(password_verify($_POST['password'], $user['password_hash'])){

            $_SESSION['usuario'] = $user['nombre_usuario'];

            header('Location: modules/dashboard/index.php');

            exit;

        }else{

            $error = 'Usuario o contraseña incorrectos';

        }

    }else{

        $error = 'Usuario o contraseña incorrectos';

    }

}
?>


<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-dark">

<div class="container">

<div class="row justify-content-center align-items-center vh-100">

<div class="col-md-4">

<div class="card shadow-lg">

<div class="card-header bg-primary text-white text-center">

<h2>Sistema Constructora</h2>

</div>

<div class="card-body">

<?php if($error): ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php endif; ?>

<form method="POST">

<div class="mb-3">

<label class="form-label">

Usuario

</label>

<input
type="text"
name="usuario"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

Contraseña

</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

<button
type="submit"
class="btn btn-primary w-100">

Ingresar

</button>

</form>

</div>

</div>

</div>

</div>

</div>

</body>

</html>