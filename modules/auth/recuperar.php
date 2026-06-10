<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

$pdo   = conectar();
$error = '';
$user  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Cambiar contraseña
    if (isset($_POST['cambiar'])) {
        $id           = intval($_POST['id_usuario'] ?? 0);
        $nueva        = trim($_POST['nueva_contrasena'] ?? '');
        $confirmar    = trim($_POST['confirmar_contrasena'] ?? '');

        if ($nueva && $confirmar) {
            if ($nueva === $confirmar) {
                $hash = password_hash($nueva, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios_sistema SET password_hash = ? WHERE id_usuario_sistema = ?
                ");
                $stmt->execute([$hash, $id]);

                // Recarga datos actualizados
                $stmt2 = $pdo->prepare("
                    SELECT us.id_usuario_sistema, us.nombre_usuario,
                           us.password_hash, us.estado, e.nombre
                    FROM usuarios_sistema us
                    JOIN empleados e ON e.id_empleado = us.id_empleado
                    WHERE us.id_usuario_sistema = ?
                ");
                $stmt2->execute([$id]);
                $user = $stmt2->fetch();
                $user['mensaje'] = 'Contraseña actualizada correctamente';
            } else {
                $error = 'Las contraseñas no coinciden';
                $stmt3 = $pdo->prepare("
                    SELECT us.id_usuario_sistema, us.nombre_usuario,
                           us.password_hash, us.estado, e.nombre
                    FROM usuarios_sistema us
                    JOIN empleados e ON e.id_empleado = us.id_empleado
                    WHERE us.id_usuario_sistema = ?
                ");
                $stmt3->execute([$id]);
                $user = $stmt3->fetch();
            }
        } else {
            $error = 'Completa ambos campos';
        }

    // Buscar usuario
    } elseif (isset($_POST['buscar'])) {
        $usuario = trim($_POST['usuario'] ?? '');

        if ($usuario) {
            $stmt = $pdo->prepare("
                SELECT us.id_usuario_sistema, us.nombre_usuario,
                       us.password_hash, us.estado, e.nombre
                FROM usuarios_sistema us
                JOIN empleados e ON e.id_empleado = us.id_empleado
                WHERE us.nombre_usuario = ?
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
    <title>Recuperar Cuenta — Vértice</title>
</head>
<body>

<h2>🔑 Recuperar Cuenta — Empleados</h2>
<a href="login.php">← Volver al login</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>

<?php if (!$user): ?>
    <!-- PASO 1 — buscar usuario -->
    <form method="POST">
        <label>Ingresa tu nombre de usuario:</label><br>
        <input type="text" name="usuario" required><br><br>
        <button type="submit" name="buscar">Buscar</button>
    </form>

<?php else: ?>
    <!-- PASO 2 — mostrar datos y opción de cambiar -->

    <?php if (isset($user['mensaje'])): ?>
        <p style="color:green"><?= $user['mensaje'] ?></p>
    <?php endif; ?>

    <h3>Tus datos de acceso</h3>
    <table border="1" cellpadding="10">
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

    <h3>Cambiar contraseña</h3>
    <form method="POST">
        <input type="hidden" name="id_usuario" value="<?= $user['id_usuario_sistema'] ?>">

        <label>Nueva contraseña:</label><br>
        <input type="text" name="nueva_contrasena" required><br><br>

        <label>Confirmar contraseña:</label><br>
        <input type="text" name="confirmar_contrasena" required><br><br>

        <button type="submit" name="cambiar">Cambiar contraseña</button>
    </form>

    <br>
    <a href="login.php"><button>Ir al login</button></a>

<?php endif; ?>

</body>
</html>