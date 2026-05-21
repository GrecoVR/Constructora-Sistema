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

        if ($user && $user['estado'] === 'activo' && $contrasena === $user['password_hash']) {
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
    <title>Iniciar Sesión como Cliente — Empresa Constructora</title>
</head>
<body>

<h2>🏢 Iniciar Sesión como Cliente — Empresa Constructora</h2>
<a href="inicio.php">← Volver</a>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    <label>Usuario:</label><br>
    <input type="text" name="usuario" required><br><br>

    <label>Contraseña:</label><br>
    <input type="password" name="contrasena" required><br><br>

    <button type="submit">Entrar al portal</button>
</form>

<br>
<p>¿No tienes cuenta? <a href="register_cliente.php">Crear cuenta</a></p>
<p>¿Olvidaste tu contraseña? <a href="recuperar_cliente.php">Recuperar aquí</a></p>

</body>
</html>