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
</head>
<body>

<h2>🔑 Recuperar Cuenta — Clientes</h2>
<a href="login_cliente.php">← Volver al login</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>

<?php if (!$user): ?>
    <form method="POST">
        <label>Ingresa tu nombre de usuario:</label><br>
        <input type="text" name="usuario" required><br><br>
        <button type="submit" name="buscar">Buscar</button>
    </form>

<?php else: ?>

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
        <input type="hidden" name="id_usuario" value="<?= $user['id_usuario_cliente'] ?>">

        <label>Nueva contraseña:</label><br>
        <input type="text" name="nueva_contrasena" required><br><br>

        <label>Confirmar contraseña:</label><br>
        <input type="text" name="confirmar_contrasena" required><br><br>

        <button type="submit" name="cambiar">Cambiar contraseña</button>
    </form>

    <br>
    <a href="login_cliente.php"><button>Ir al login</button></a>

<?php endif; ?>

</body>
</html>