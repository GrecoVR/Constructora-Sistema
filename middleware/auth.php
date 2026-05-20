<?php
/**
 * middleware/auth.php
 * 
 * Middleware de autenticación
 * Verifica que el usuario tenga una sesión activa
 * Si no la tiene, redirige al login
 * 
 * @author Backend Senior
 * @version 1.0
 */

// Cargar las dependencias necesarias
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

/**
 * Clase AuthMiddleware
 * Gestiona la verificación de autenticación y redirecciones
 */
class AuthMiddleware {
    
    /**
     * Página de login a la que redirigir si no está autenticado
     */
    const LOGIN_PAGE = '/index.php?page=login';
    
    /**
     * Tiempo máximo de inactividad en minutos
     */
    const MAX_INACTIVITY_TIME = 60; // 1 hora

    /**
     * Verifica que el usuario esté autenticado
     * Si no está autenticado, lo redirige a login
     * 
     * @return void
     */
    public static function require_login() {
        // Verificar si hay sesión activa
        if (!SessionManager::isActive()) {
            self::redirectToLogin('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
        }

        // Validar que la sesión no sea demasiado antigua
        self::validateSessionExpiry();

        // Actualizar última actividad
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Verifica que el usuario tenga un rol específico
     * Si no lo tiene, redirige a acceso denegado
     * 
     * @param string|array $requiredRoles Rol o array de roles requeridos
     * @return void
     */
    public static function require_role($requiredRoles) {
        // Primero verificar que esté autenticado
        self::require_login();

        // Si es un string, convertir a array
        if (is_string($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }

        // Obtener los roles del usuario de la sesión
        $userRoles = SessionManager::getUserRoles();

        // Verificar si el usuario tiene al menos uno de los roles requeridos
        $hasRequiredRole = false;
        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles)) {
                $hasRequiredRole = true;
                break;
            }
        }

        if (!$hasRequiredRole) {
            self::redirectToAccessDenied(
                'No tienes permiso para acceder a este recurso. Roles requeridos: ' . 
                implode(', ', $requiredRoles)
            );
        }
    }

    /**
     * Verifica que el usuario tenga un permiso específico
     * Para esto, debe consultarse la tabla roles_permisos
     * 
     * @param string $permission Permiso requerido
     * @return bool
     */
    public static function hasPermission($permission) {
        // Verificar que esté autenticado
        if (!SessionManager::isActive()) {
            return false;
        }

        // Obtener permiso de sesión o de base de datos
        $permissions = SessionManager::get('permissions', []);
        
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Consultar base de datos si no está en sesión
        $db = DatabaseConnection::getInstance();
        
        $sql = "
            SELECT COUNT(*) as count
            FROM roles_permisos rp
            INNER JOIN usuarios_roles ur ON ur.id_rol = rp.id_rol
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE ur.id_usuario = :usuario_id 
            AND p.nombre_permiso = :permiso
        ";

        $result = $db->fetch($sql, [
            ':usuario_id' => SessionManager::getUserId(),
            ':permiso' => $permission
        ]);

        return $result && $result['count'] > 0;
    }

    /**
     * Obtiene todos los permisos del usuario
     * 
     * @return array
     */
    public static function getUserPermissions() {
        if (!SessionManager::isActive()) {
            return [];
        }

        // Intentar obtener de sesión primero
        if (SessionManager::has('permissions')) {
            return SessionManager::get('permissions', []);
        }

        // Consultar de base de datos
        $db = DatabaseConnection::getInstance();
        
        $sql = "
            SELECT DISTINCT p.nombre_permiso
            FROM roles_permisos rp
            INNER JOIN usuarios_roles ur ON ur.id_rol = rp.id_rol
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE ur.id_usuario = :usuario_id
        ";

        $results = $db->fetchAll($sql, [
            ':usuario_id' => SessionManager::getUserId()
        ]);

        $permissions = array_column($results, 'nombre_permiso');
        
        // Guardar en sesión para futuras consultas
        SessionManager::set('permissions', $permissions);

        return $permissions;
    }

    /**
     * Valida que la sesión no haya expirado por inactividad
     * 
     * @return void
     */
    private static function validateSessionExpiry() {
        $currentTime = time();
        $lastActivity = SessionManager::get('_last_activity', $currentTime);
        $inactivitySeconds = ($currentTime - $lastActivity);
        $maxInactivitySeconds = self::MAX_INACTIVITY_TIME * 60;

        if ($inactivitySeconds > $maxInactivitySeconds) {
            SessionManager::destroy();
            self::redirectToLogin(
                'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.'
            );
        }
    }

    /**
     * Redirige al usuario al login
     * 
     * @param string $message Mensaje a mostrar
     * @return void
     */
    private static function redirectToLogin($message = '') {
        // Guardar mensaje en sesión si existe
        if ($message) {
            session_start();
            $_SESSION['login_message'] = $message;
            session_write_close();
        }

        // Redirigir
        header('Location: ' . self::LOGIN_PAGE);
        exit;
    }

    /**
     * Redirige a página de acceso denegado
     * 
     * @param string $message Mensaje a mostrar
     * @return void
     */
    private static function redirectToAccessDenied($message = '') {
        // Registrar intento de acceso no autorizado
        $userId = SessionManager::getUserId();
        error_log(
            "Acceso denegado para usuario {$userId}: {$message} desde {$_SERVER['REQUEST_URI']}"
        );

        // Guardar mensaje en sesión
        if ($message) {
            session_start();
            $_SESSION['error_message'] = $message;
            session_write_close();
        }

        // Redirigir a página de error
        header('Location: /index.php?page=access-denied');
        exit;
    }

    /**
     * Obtiene información del usuario actual
     * 
     * @return array|null
     */
    public static function getCurrentUser() {
        if (!SessionManager::isActive()) {
            return null;
        }

        $userId = SessionManager::getUserId();
        
        // Intentar obtener de sesión
        if (SessionManager::has('usuario')) {
            return SessionManager::get('usuario');
        }

        // Consultar de base de datos
        $db = DatabaseConnection::getInstance();
        
        $sql = "
            SELECT 
                id_usuario,
                nombre_usuario,
                email_usuario,
                estado_usuario
            FROM usuarios_sistema
            WHERE id_usuario = :usuario_id
            AND estado_usuario = 1
        ";

        $usuario = $db->fetch($sql, [':usuario_id' => $userId]);

        if ($usuario) {
            SessionManager::set('usuario', $usuario);
        }

        return $usuario;
    }

    /**
     * Verifica si el usuario es administrador
     * 
     * @return bool
     */
    public static function isAdmin() {
        $roles = SessionManager::getUserRoles();
        return in_array('administrador', $roles) || in_array('admin', $roles);
    }

    /**
     * Registra una acción en el sistema para auditoría
     * 
     * @param string $accion Descripción de la acción
     * @param string $tabla Tabla afectada
     * @param int|null $registroId ID del registro afectado
     * @return bool
     */
    public static function logAction($accion, $tabla, $registroId = null) {
        if (!SessionManager::isActive()) {
            return false;
        }

        $db = DatabaseConnection::getInstance();
        
        $sql = "
            INSERT INTO registros_sistema 
            (id_usuario, accion, tabla, registro_id, ip_usuario, user_agent, fecha_registro)
            VALUES 
            (:usuario_id, :accion, :tabla, :registro_id, :ip, :user_agent, NOW())
        ";

        return $db->execute($sql, [
            ':usuario_id' => SessionManager::getUserId(),
            ':accion' => $accion,
            ':tabla' => $tabla,
            ':registro_id' => $registroId,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}

?>
