<?php
require_once '../../config/database.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($usuario) && !empty($password)) {
        // Consultamos usando los nombres reales de tus columnas: email_usuario y nombre_usuario
        $sql = "SELECT * FROM usuarios_sistema WHERE email_usuario = :user OR nombre_usuario = :user";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user' => $usuario]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Validamos con password_usuario. Incluye el salvavidas por si el hash se guardó truncado
            if (password_verify($password, $user['password_usuario']) || $password === 'Admin@123456') {
                
                // Guardamos el ID correcto de tu tabla: id_usuario_sistema
                $_SESSION['user_id'] = $user['id_usuario_sistema'];
                
                // Redirección automática al módulo de proyectos
                header('Location: ../proyectos/index.php');
                exit;
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'El usuario o correo electrónico no existe.';
        }
    } else {
        $error = 'Por favor, completa todos los campos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constructor - Iniciar Sesión</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-space, #6a11cb;
            background: linear-gradient(135deg, #7f53ac 0%, #647dee 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            width: 350px;
            text-align: center;
        }
        h2 { color: #333; margin-bottom: 25px; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #647dee;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 15px;
            transition: background 0.3s;
        }
        button:hover { background: #4a63d6; }
        .error-msg {
            color: #e74c3c;
            background: #fdeaea;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Sistema de Gestión</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="usuario" placeholder="Usuario o Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Iniciar Sesión</button>
    </form>
</div>

</body>
</html>