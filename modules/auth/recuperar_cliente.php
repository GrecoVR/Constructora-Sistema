<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

$pdo   = conectar();
$error = '';
$user  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['cambiar'])) {
        $id        = intval($_POST['id_usuario'] ?? 0);
        $nueva     = trim($_POST['nueva_contrasena'] ?? '');
        $confirmar = trim($_POST['confirmar_contrasena'] ?? '');

        if ($nueva && $confirmar) {
            if ($nueva === $confirmar) {
                $hash = password_hash($nueva, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios_clientes SET password_hash = ? WHERE id_usuario_cliente = ?
                ");
                $stmt->execute([$hash, $id]);

                $stmt2 = $pdo->prepare("
                    SELECT uc.id_usuario_cliente, uc.nombre_usuario,
                           uc.password_hash, uc.estado, c.nombre
                    FROM usuarios_clientes uc
                    JOIN clientes c ON c.id_cliente = uc.id_cliente
                    WHERE uc.id_usuario_cliente = ?
                ");
                $stmt2->execute([$id]);
                $user = $stmt2->fetch();
                $user['mensaje'] = 'Contraseña actualizada correctamente';
            } else {
                $error = 'Las contraseñas no coinciden';
                $stmt3 = $pdo->prepare("
                    SELECT uc.id_usuario_cliente, uc.nombre_usuario,
                           uc.password_hash, uc.estado, c.nombre
                    FROM usuarios_clientes uc
                    JOIN clientes c ON c.id_cliente = uc.id_cliente
                    WHERE uc.id_usuario_cliente = ?
                ");
                $stmt3->execute([$id]);
                $user = $stmt3->fetch();
            }
        } else {
            $error = 'Completa ambos campos';
        }

    } elseif (isset($_POST['buscar'])) {
        $usuario = trim($_POST['usuario'] ?? '');

        if ($usuario) {
            $stmt = $pdo->prepare("
                SELECT uc.id_usuario_cliente, uc.nombre_usuario,
                       uc.password_hash, uc.estado, c.nombre
                FROM usuarios_clientes uc
                JOIN clientes c ON c.id_cliente = uc.id_cliente
                WHERE uc.nombre_usuario = ?
            ");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'No se encontró ese usuario';
            }
        } else {
            $error = 'Ingresa tu nombre de usuario';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Cuenta — Portal Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
  <div class="container-fluid bg-light">
  <div class="wrapper d-flex flex-column align-items-center vh-100">
  <div class="p-4">

<h2 class="my-4 fw-semibold">🔑 Recuperar Cuenta — Clientes</h2>
<a href="login_cliente.php">← Volver al login</a>

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

<?php if (!$user): ?>
    <form method="POST">
        <div class="my-3">
        <label class="form-label" for="usuario">Ingresa tu nombre de usuario:</label>
        <input class="form-control" type="text" id="usuario" name="usuario" required>
        </div>
        <button class="btn btn-success" type="submit" name="buscar">Buscar</button>
    </form>

<?php else: ?>

    <?php if (isset($user['mensaje'])): ?>
        <p style="color:green"><?= $user['mensaje'] ?></p>
    <?php endif; ?>

    <h3>Tus datos de acceso</h3>
    <table class="table table-striped table-bordered">
        <tr>
            <td><strong>Nombre</strong></td>
            <td><?= htmlspecialchars($user['nombre']) ?></td>
        </tr>
        <tr>
            <td><strong>Usuario</strong></td>
            <td><?= htmlspecialchars($user['nombre_usuario']) ?></td>
        </tr>
        <tr>
            <td><strong>Contraseña actual</strong></td>
            <td><?= htmlspecialchars($user['password_hash']) ?></td>
        </tr>
        <tr>
            <td><strong>Estado</strong></td>
            <td><?= ucfirst($user['estado']) ?></td>
        </tr>
    </table>

    <br>

    <h3 class="mb-4">Cambiar contraseña</h3>
    <form method="POST">
        <input type="hidden" name="id_usuario" value="<?= $user['id_usuario_cliente'] ?>">
        <div class="mb-3">
        <label class="form-label" for="nueva_contrasena">Nueva contraseña:</label>
        <input class="form-control" type="text" name="nueva_contrasena" name="nueva_contrasena" required>
        </div>
        <div class="mb-3">
        <label class="form-label" for="confirmar_contrasena">Confirmar contraseña:</label>
        <input class="form-control" type="text" id="confirmar_contrasena" name="confirmar_contrasena" required>
        </div>
        <button class="btn btn-primary" type="submit" name="cambiar">Cambiar contraseña</button>
    </form>

    <br>
    <a href="login_cliente.php">Ir al login</a>

<?php endif; ?>
</div>
</div>
</div>
</body>
</html>