<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

$error = '';
$exito = '';
$pdo   = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = intval($_POST['id_cliente'] ?? 0);
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if ($id_cliente && $usuario && $contrasena) {
        $stmt = $pdo->prepare("
            SELECT id_usuario_cliente FROM usuarios_clientes WHERE nombre_usuario = ?
        ");
        $stmt->execute([$usuario]);

        if ($stmt->fetch()) {
            $error = 'Ese nombre de usuario ya existe';
        } else {
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("
                INSERT INTO usuarios_clientes (id_cliente, nombre_usuario, password_hash, estado)
                VALUES (?, ?, ?, 'activo')
            ");
            $stmt2->execute([$id_cliente, $usuario, $hash]);
            $exito = 'Cuenta creada. Ya puedes iniciar sesión.';
        }
    } else {
        $error = 'Completa todos los campos';
    }
}

// Solo clientes que no tienen cuenta aún
$clientes = $pdo->query("
    SELECT c.id_cliente, c.nombre
    FROM clientes c
    WHERE c.id_cliente NOT IN (SELECT id_cliente FROM usuarios_clientes)
    ORDER BY c.nombre ASC
")->fetchAll();
?>


<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container-fluid bg-light">
    <div class="wrapper d-flex flex-column align-items-center vh-100">
   <div  class="w-100 pt-2" style="height:80px;">
    <?php if ($error): ?>
        <div class="toast fade show align-items-center text-bg-danger border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">
              <?= $error ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($exito): ?>
        <div class="toast fade show align-items-center text-bg-success border-0 w-100" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">
              <?= $exito ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (empty($clientes)): ?>
        <p style="color:orange">
            Todos los clientes ya tienen cuenta o no hay clientes registrados.
            Contacta a Vértice para registrarte.
        </p>
    <?php else: ?>

    <div class="card mt-4" style="width:400px;">
        <div class="card-body">
          <h5 class="card-title text-center">Crear cuenta Cliente</h5>
        <form method="POST">
          <div class="mb-3">
          <label class="form-label" for="id_cliente">Selecciona tu empresa/nombre: *</label>
          <select class="form-select" id="id_cliente" name="id_cliente" required>
              <option value="">-- Selecciona --</option>
              <?php foreach ($clientes as $c): ?>
                  <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
          </select>
          </div>
          <div class="mb-3">
          <label class="form-label" for="usuario">Nombre de usuario: *</label>
          <input class="form-control" type="text" id="usuario" name="usuario" required>
          </div>
          <div class="mb-3">
          <label class="form-label" for="contrasena">Contraseña: *</label>
          <input class="form-control" type="password" id="contrasena" name="contrasena" required>
          </div>
          <button class="btn btn-primary" type="submit">Crear cuenta</button>
        </form>
      </div>
    </div> <!-- end card -->
    <a class="mt-3" href="login_cliente.php">← Volver al login</a>
<?php endif; ?>
</div>
</div>
</body>
</html>