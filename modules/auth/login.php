<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: ../../modules/dashboard/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if ($usuario && $contrasena) {
        $pdo  = conectar();
        $stmt = $pdo->prepare("
            SELECT us.id_usuario_sistema, us.password_hash, us.estado, e.nombre
            FROM usuarios_sistema us
            JOIN empleados e ON e.id_empleado = us.id_empleado
            WHERE us.nombre_usuario = ?
        ");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();
        
        if ($user && $user['estado'] === 'activo' && password_verify($contrasena, $user['password_hash'])) {

            $stmt2 = $pdo->prepare("
                SELECT DISTINCT p.nombre_permiso
                FROM usuarios_roles ur
                JOIN roles_permisos rp ON rp.id_rol = ur.id_rol
                JOIN permisos p ON p.id_permiso = rp.id_permiso
                WHERE ur.id_usuario_sistema = ?
            ");
            $stmt2->execute([$user['id_usuario_sistema']]);
            $permisos = $stmt2->fetchAll(PDO::FETCH_COLUMN);

            $stmt3 = $pdo->prepare("
                SELECT r.nombre_rol
                FROM usuarios_roles ur
                JOIN roles r ON r.id_rol = ur.id_rol
                WHERE ur.id_usuario_sistema = ?
            ");
            $stmt3->execute([$user['id_usuario_sistema']]);
            $roles = $stmt3->fetchAll(PDO::FETCH_COLUMN);

            $_SESSION['id_usuario']  = $user['id_usuario_sistema'];
            $_SESSION['nombre']      = $user['nombre'];
            $_SESSION['permisos']    = $permisos;
            $_SESSION['roles']       = $roles;
            $_SESSION['tipo_sesion'] = 'empleado';

            header('Location: ../../modules/dashboard/dashboard.php');
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
    <title>Iniciar Sesión como Empleado — Empresa Constructora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>    
     <div class="container-fluid bg-light">
        <div class="wrapper d-flex flex-column align-items-center justify-content-center vh-100">
     
        <h2 class="mb-4 fw-semibold">Empresa Constructora</h2>
        <h4 class="mb-4 fw-semibold"> 👷 Iniciar Sesión como Empleado </h4>
        
        
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
                       <button class="btn btn-primary w-100" type="submit">Entrar</button>
                       <div class="sign-up mt-3 text-center">
                          ¿No tienes cuenta? <a href="register.php">Crear cuenta</a>
                       </div>
                       <div class="sign-up mt-3 text-center">
                       ¿Olvidaste tu contraseña? <a href="recuperar.php">Recuperar aquí</a>
                       </div>
                    </form>
               </div>
             </div><!-- end card -->
             <a href="inicio.php" class="mt-3">← Volver al Inicio</a>
          </div>
      </div>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
     </script>
</body>
</html>