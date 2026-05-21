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
            $stmt2 = $pdo->prepare("
                INSERT INTO usuarios_clientes (id_cliente, nombre_usuario, password_hash, estado)
                VALUES (?, ?, ?, 'activo')
            ");
            $stmt2->execute([$id_cliente, $usuario, $contrasena]);
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cuenta Cliente — Vértice</title>
</head>
<body>

<h2>Crear cuenta de cliente</h2>
<a href="login_cliente.php">← Volver al login</a>

<br><br>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>
<?php if ($exito): ?>
    <p style="color:green"><?= $exito ?></p>
<?php endif; ?>

<?php if (empty($clientes)): ?>
    <p style="color:orange">
        Todos los clientes ya tienen cuenta o no hay clientes registrados.
        Contacta a Vértice para registrarte.
    </p>
<?php else: ?>

<form method="POST">
    <label>Selecciona tu empresa/nombre: *</label><br>
    <select name="id_cliente" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Nombre de usuario: *</label><br>
    <input type="text" name="usuario" required><br><br>

    <label>Contraseña: *</label><br>
    <input type="password" name="contrasena" required><br><br>

    <button type="submit">Crear cuenta</button>
</form>

<?php endif; ?>

</body>
</html>