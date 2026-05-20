<?php
/**
 * middleware/roles.php
 * 
 * Middleware de gestión de roles y permisos
 * Funciones para verificar si un usuario tiene un rol o permiso específico
 * 
 * @author Backend Senior
 * @version 1.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

/**
 * Clase RoleMiddleware
 * Gestiona la verificación de roles y permisos
 */
class RoleMiddleware {
    
    /**
     * Caché de permisos de usuario para optimizar consultas
     */
    private static $permissionCache = [];

    /**
     * Verifica si el usuario actual tiene un rol específico
     * 
     * @param string $roleName Nombre del rol (ej: 'administrador', 'supervisor', 'operario')
     * @return bool
     */
    public static function hasRole($roleName) {
        if (!SessionManager::isActive()) {
            return false;
        }

        // Obtener roles de la sesión
        $userRoles = SessionManager::getUserRoles();

        // Búsqueda rápida en array
        return in_array(strtolower($roleName), array_map('strtolower', $userRoles));
    }

    /**
     * Verifica si el usuario tiene ALGUNO de los roles especificados
     * 
     * @param array $roleNames Array de nombres de roles
     * @return bool
     */
    public static function hasAnyRole($roleNames) {
        if (!is_array($roleNames)) {
            return self::hasRole($roleNames);
        }

        foreach ($roleNames as $role) {
            if (self::hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene TODOS los roles especificados
     * 
     * @param array $roleNames Array de nombres de roles
     * @return bool
     */
    public static function hasAllRoles($roleNames) {
        if (!is_array($roleNames)) {
            return self::hasRole($roleNames);
        }

        foreach ($roleNames as $role) {
            if (!self::hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     * 
     * @param string $permissionName Nombre del permiso (ej: 'crear_usuario', 'editar_proyecto')
     * @return bool
     */
    public static function hasPermission($permissionName) {
        if (!SessionManager::isActive()) {
            return false;
        }

        $userId = SessionManager::getUserId();
        $cacheKey = "{$userId}_{$permissionName}";

        // Verificar caché
        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }

        // Consultar base de datos
        $db = DatabaseConnection::getInstance();
        
        $sql = "
            SELECT COUNT(*) as total
            FROM roles_permisos rp
            INNER JOIN usuarios_roles ur ON ur.id_rol = rp.id_rol
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE ur.id_usuario = :usuario_id 
            AND p.nombre_permiso = :permiso
            AND p.estado_permiso = 1
        ";

        $result = $db->fetch($sql, [
            ':usuario_id' => $userId,
            ':permiso' => $permissionName
        ]);

        $hasPermission = $result && $result['total'] > 0;

        // Guardar en caché
        self::$permissionCache[$cacheKey] = $hasPermission;

        return $hasPermission;
    }

    /**
     * Verifica si el usuario tiene ALGUNO de los permisos especificados
     * 
     * @param array $permissionNames Array de nombres de permisos
     * @return bool
     */
    public static function hasAnyPermission($permissionNames) {
        if (!is_array($permissionNames)) {
            return self::hasPermission($permissionNames);
        }

        foreach ($permissionNames as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene TODOS los permisos especificados
     * 
     * @param array $permissionNames Array de nombres de permisos
     * @return bool
     */
    public static function hasAllPermissions($permissionNames) {
        if (!is_array($permissionNames)) {
            return self::hasPermission($permissionNames);
        }

        foreach ($permissionNames as $permission) {
            if (!self::hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene todos los roles del usuario
     * 
     * @return array
     */
    public static function getUserRoles() {
        return SessionManager::getUserRoles();
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

        $userId = SessionManager::getUserId();
        $db = DatabaseConnection::getInstance();

        $sql = "
            SELECT DISTINCT 
                p.id_permiso,
                p.nombre_permiso,
                p.descripcion_permiso
            FROM roles_permisos rp
            INNER JOIN usuarios_roles ur ON ur.id_rol = rp.id_rol
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE ur.id_usuario = :usuario_id
            AND p.estado_permiso = 1
            ORDER BY p.nombre_permiso
        ";

        return $db->fetchAll($sql, [':usuario_id' => $userId]) ?: [];
    }

    /**
     * Obtiene todos los permisos asociados a un rol
     * 
     * @param int $roleId ID del rol
     * @return array
     */
    public static function getRolePermissions($roleId) {
        $db = DatabaseConnection::getInstance();

        $sql = "
            SELECT DISTINCT 
                p.id_permiso,
                p.nombre_permiso,
                p.descripcion_permiso
            FROM roles_permisos rp
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE rp.id_rol = :rol_id
            AND p.estado_permiso = 1
            ORDER BY p.nombre_permiso
        ";

        return $db->fetchAll($sql, [':rol_id' => $roleId]) ?: [];
    }

    /**
     * Obtiene todos los roles disponibles en el sistema
     * 
     * @param bool $onlyActive Si solo incluir roles activos
     * @return array
     */
    public static function getAllRoles($onlyActive = true) {
        $db = DatabaseConnection::getInstance();

        $sql = "SELECT id_rol, nombre_rol, descripcion_rol FROM roles";
        
        if ($onlyActive) {
            $sql .= " WHERE estado_rol = 1";
        }
        
        $sql .= " ORDER BY nombre_rol";

        return $db->fetchAll($sql) ?: [];
    }

    /**
     * Obtiene todos los permisos disponibles en el sistema
     * 
     * @param bool $onlyActive Si solo incluir permisos activos
     * @return array
     */
    public static function getAllPermissions($onlyActive = true) {
        $db = DatabaseConnection::getInstance();

        $sql = "SELECT id_permiso, nombre_permiso, descripcion_permiso FROM permisos";
        
        if ($onlyActive) {
            $sql .= " WHERE estado_permiso = 1";
        }
        
        $sql .= " ORDER BY nombre_permiso";

        return $db->fetchAll($sql) ?: [];
    }

    /**
     * Obtiene información detallada de un rol
     * 
     * @param int $roleId ID del rol
     * @return array|null
     */
    public static function getRoleInfo($roleId) {
        $db = DatabaseConnection::getInstance();

        $sql = "
            SELECT 
                id_rol,
                nombre_rol,
                descripcion_rol,
                estado_rol
            FROM roles
            WHERE id_rol = :rol_id
        ";

        return $db->fetch($sql, [':rol_id' => $roleId]);
    }

    /**
     * Asigna un rol a un usuario
     * 
     * @param int $userId ID del usuario
     * @param int $roleId ID del rol
     * @return bool
     */
    public static function assignRoleToUser($userId, $roleId) {
        $db = DatabaseConnection::getInstance();

        // Verificar que el rol no esté ya asignado
        $existingSql = "
            SELECT COUNT(*) as count
            FROM usuarios_roles
            WHERE id_usuario = :usuario_id AND id_rol = :rol_id
        ";

        $existing = $db->fetch($existingSql, [
            ':usuario_id' => $userId,
            ':rol_id' => $roleId
        ]);

        if ($existing && $existing['count'] > 0) {
            return true; // Ya está asignado
        }

        // Asignar rol
        $sql = "
            INSERT INTO usuarios_roles (id_usuario, id_rol)
            VALUES (:usuario_id, :rol_id)
        ";

        $result = $db->execute($sql, [
            ':usuario_id' => $userId,
            ':rol_id' => $roleId
        ]);

        // Limpiar caché de permisos del usuario
        self::clearUserPermissionCache($userId);

        return $result !== false;
    }

    /**
     * Elimina un rol de un usuario
     * 
     * @param int $userId ID del usuario
     * @param int $roleId ID del rol
     * @return bool
     */
    public static function removeRoleFromUser($userId, $roleId) {
        $db = DatabaseConnection::getInstance();

        $sql = "
            DELETE FROM usuarios_roles
            WHERE id_usuario = :usuario_id AND id_rol = :rol_id
        ";

        $result = $db->execute($sql, [
            ':usuario_id' => $userId,
            ':rol_id' => $roleId
        ]);

        // Limpiar caché de permisos del usuario
        self::clearUserPermissionCache($userId);

        return $result !== false;
    }

    /**
     * Limpia el caché de permisos de un usuario
     * Se ejecuta cuando cambian los roles
     * 
     * @param int $userId ID del usuario
     * @return void
     */
    private static function clearUserPermissionCache($userId) {
        // Limpiar caché local
        foreach (array_keys(self::$permissionCache) as $key) {
            if (strpos($key, "{$userId}_") === 0) {
                unset(self::$permissionCache[$key]);
            }
        }

        // Limpiar sesión si es el usuario actual
        if (SessionManager::isActive() && SessionManager::getUserId() === $userId) {
            SessionManager::remove('permissions');
            SessionManager::remove('roles');
        }
    }

    /**
     * Middleware para requerir un rol específico
     * Útil para usar en rutas
     * 
     * @param string|array $roles Rol o array de roles requeridos
     * @return void
     */
    public static function requireRole($roles) {
        if (!SessionManager::isActive()) {
            header('Location: /index.php?page=login');
            exit;
        }

        if (!self::hasAnyRole($roles)) {
            header('Location: /index.php?page=access-denied');
            exit;
        }
    }

    /**
     * Middleware para requerir un permiso específico
     * Útil para usar en rutas
     * 
     * @param string|array $permissions Permiso o array de permisos requeridos
     * @return void
     */
    public static function requirePermission($permissions) {
        if (!SessionManager::isActive()) {
            header('Location: /index.php?page=login');
            exit;
        }

        if (!self::hasAnyPermission($permissions)) {
            header('Location: /index.php?page=access-denied');
            exit;
        }
    }
}

?>
