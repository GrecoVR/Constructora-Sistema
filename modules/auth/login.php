<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: ../../dashboard.php');
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

            header('Location: ../../dashboard.php');
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
    <title>Iniciar Sesión como Empleado — Empresa Constructora</title>
</head>
<body>

<h2>👷 Iniciar Sesión como Empleado — Empresa Constructora</h2>
<a href="inicio.php">← Volver</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    <label>Usuario:</label><br>
    <input type="text" name="usuario" required><br><br>

    <label>Contraseña:</label><br>
    <input type="password" name="contrasena" required><br><br>

    <button type="submit">Entrar</button>
</form>

    <p>¿No tienes cuenta? <a href="register.php">Crear cuenta</a></p>
    <p>¿Olvidaste tu contraseña? <a href="recuperar.php">Recuperar aquí</a></p>
</body>
</html>