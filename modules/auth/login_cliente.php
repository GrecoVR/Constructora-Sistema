<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (isset($_SESSION['id_cliente'])) {
    header('Location: ../../client/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if ($usuario && $contrasena) {
        $pdo  = conectar();
        $stmt = $pdo->prepare("
            SELECT uc.id_usuario_cliente, uc.password_hash, uc.estado,
                   c.id_cliente, c.nombre, c.email
            FROM usuarios_clientes uc
            JOIN clientes c ON c.id_cliente = uc.id_cliente
            WHERE uc.nombre_usuario = ?
        ");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && $user['estado'] === 'activo' && password_verify($contrasena, $user['password_hash'])) {
            $_SESSION['id_cliente']         = $user['id_cliente'];
            $_SESSION['id_usuario_cliente'] = $user['id_usuario_cliente'];
            $_SESSION['nombre_cliente']     = $user['nombre'];
            $_SESSION['email_cliente']      = $user['email'];
            $_SESSION['tipo_sesion']        = 'cliente';

            header('Location: ../../client/index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión como Cliente — Empresa Constructora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
     <div class="container-fluid bg-light">
        <div class="wrapper d-flex flex-column align-items-center justify-content-center vh-100">

        <h2 class="mb-4 fw-semibold">Empresa Constructora</h2>
        
        <h4 class="mb-4 fw-semibold">🏢 Iniciar Sesión como Cliente</h4>

        <?php if ($error): ?>
        <div class="p-2">
        <div class="toast fade show align-items-center text-bg-danger border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">
              <?= $error ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
        </div>
        <?php endif; ?>

        <div class="card mt-2" style="width:350px;">
            <div class="card-body">
                <h5 class="card-title text-center">Iniciar Sesion</h5>
                <form method="POST">
                   <div class="mb-3">
                      <label for="user" class="form-label">Usuario:</label><br>
                      <input class="form-control" type="text" name="usuario" id="user" required>
                   </div>
                   <div class="mb-3">
                      <label for="password" class="form-label">Contraseña:</label><br>
                      <input  class="form-control" type="password" name="contrasena" id="password" required>
                   </div>
                   <button class="btn btn-primary w-100" type="submit">Entrar al portal</button>
                   <div class="sign-up mt-3 text-center">
                      ¿No tienes cuenta? <a href="register_cliente.php">Crear cuenta</a>
                   </div>
                   <div class="sign-up mt-3 text-center">
                   ¿Olvidaste tu contraseña? <a href="recuperar_cliente.php">Recuperar aquí</a>
                   </div>
                </form>
           </div>
         </div><!-- end card -->
         <a href="inicio.php" class="mt-3">← Volver al Inicio</a>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
     </script>
     </div>
     </div>
</body>
</html>