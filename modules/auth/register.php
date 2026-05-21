<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

$error   = '';
$exito   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $id_empleado = intval($_POST['id_empleado'] ?? 0);

    if ($usuario && $contrasena && $id_empleado) {
        $pdo = conectar();

        // Verifica que el usuario no exista
        $stmt = $pdo->prepare("SELECT id_usuario_sistema FROM usuarios_sistema WHERE nombre_usuario = ?");
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("
                INSERT INTO usuarios_sistema (id_empleado, nombre_usuario, password_hash, estado)
                VALUES (?, ?, ?, 'activo')
            ");
            $stmt2->execute([$id_empleado, $usuario, $hash]);
            $exito = 'Cuenta creada correctamente. Ya puedes iniciar sesión.';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}

// Carga empleados disponibles para el dropdown
$pdo       = conectar();
$empleados = $pdo->query("
    SELECT e.id_empleado, e.nombre 
    FROM empleados e
    WHERE e.id_empleado NOT IN (SELECT id_empleado FROM usuarios_sistema)
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear cuenta — Constructora</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid bg-light">
  <div class="wrapper d-flex flex-column align-items-center vh-100">
  <div  class="w-100 pt-2" style="height:80px;">
  <?php if ($error): ?>
    <div class="toast show align-items-center text-bg-error border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $error ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($exito): ?>
    <div class="toast show align-items-center text-bg-success border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $exito ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
  </div>
    <div class="card mt-4" style="width:350px;">
        <div class="card-body">
          <h5 class="card-title text-center">Crear cuenta</h5>
          <form method="POST">
            <div class="mb-3">
              <label for="empleado" class="form-label">Empleado:</label>
              <select class="form-select" name="id_empleado" id="empleado" required>
                  <option value="">-- Selecciona --</option>
                  <?php foreach ($empleados as $emp): ?>
                      <option value="<?= $emp['id_empleado'] ?>"><?= $emp['nombre'] ?></option>
                  <?php endforeach; ?>
              </select>
            </div>  
            <div class="mb-3">
              <label class="form-label" for="user" >Nombre de usuario:</label>
              <input class="form-control" type="text" name="usuario" id="user" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Contraseña:</label>
              <input class="form-control" type="password" name="contrasena" id="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Crear cuenta</button>
            <div class="mt-3">
            <a href="login.php">Volver al login</a>
            </div>
          </form>
        </div>
    </div>
   </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</body>
</html>