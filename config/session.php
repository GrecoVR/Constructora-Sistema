<?php
/**
 * config/session.php
 * 
 * Configuración segura de sesiones PHP
 * Implementa mejores prácticas de seguridad para sesiones
 * 
 * @author Backend Senior
 * @version 1.0
 */

// Evitar que este archivo sea ejecutado directamente
if (!defined('DB_NAME')) {
    define('ENVIRONMENT', 'development');
}

/**
 * Clase SessionManager
 * Gestiona todas las operaciones de sesión de forma segura
 */
class SessionManager {
    
    /**
     * Parámetros de configuración de sesión
     */
    const SESSION_CONFIG = [
        'name' => 'CONSTRUCTORA_SESSION',           // Nombre identificable pero no descriptivo
        'lifetime' => 3600,                         // 1 hora de vida útil
        'path' => '/',
        'domain' => null,                           // Usar el dominio actual
        'secure' => false,                          // Cambiar a true en producción con HTTPS
        'httponly' => true,                         // Cookie no accesible desde JavaScript
        'samesite' => 'Strict',                     // Prevenir CSRF
    ];

    private static $initialized = false;

    /**
     * Inicializa la sesión con configuración segura
     * Debe llamarse una sola vez al inicio del script
     * 
     * @return void
     */
    public static function initialize() {
        // Evitar inicialización múltiple
        if (self::$initialized) {
            return;
        }

        // No iniciar sesión si ya existe una activa
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$initialized = true;
            return;
        }

        // Configurar parámetros de sesión antes de session_start()
        session_name(self::SESSION_CONFIG['name']);
        
        session_set_cookie_params([
            'lifetime' => self::SESSION_CONFIG['lifetime'],
            'path' => self::SESSION_CONFIG['path'],
            'domain' => self::SESSION_CONFIG['domain'],
            'secure' => self::SESSION_CONFIG['secure'],
            'httponly' => self::SESSION_CONFIG['httponly'],
            'samesite' => self::SESSION_CONFIG['samesite'],
        ]);

        // Configuraciones adicionales de seguridad
        ini_set('session.use_strict_mode', 1);           // No aceptar IDs de sesión de fuentes externas
        ini_set('session.use_only_cookies', 1);          // No permitir sessionid en URL
        ini_set('session.use_trans_sid', 0);             // No agregar ID de sesión a URLs
        ini_set('session.cookie_httponly', 1);           // Redundancia: solo HTTP
        ini_set('session.cookie_secure', 0);             // Cambiar a 1 en producción con HTTPS

        // Iniciar sesión
        session_start();

        // Regenerar ID de sesión para prevenir session fixation
        self::regenerateSessionId();

        // Validar integridad de sesión
        self::validateSessionIntegrity();

        self::$initialized = true;
    }

    /**
     * Regenera el ID de sesión (previene Session Fixation Attack)
     * Se ejecuta en login y cambios críticos
     * 
     * @return void
     */
    public static function regenerateSessionId() {
        session_regenerate_id(true); // true elimina la sesión antigua
    }

    /**
     * Valida la integridad de la sesión
     * Comprueba que el User-Agent y IP no cambien (detecta hijacking)
     * 
     * @return bool
     */
    private static function validateSessionIntegrity() {
        $ip = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Primera visita de sesión
        if (!isset($_SESSION['_ip']) || !isset($_SESSION['_user_agent'])) {
            $_SESSION['_ip'] = $ip;
            $_SESSION['_user_agent'] = $userAgent;
            return true;
        }

        // Validar que IP no haya cambiado
        if ($_SESSION['_ip'] !== $ip) {
            error_log('Session hijacking attempt detected: IP changed from ' . $_SESSION['_ip'] . ' to ' . $ip);
            self::destroy();
            return false;
        }

        // Validar que User-Agent no haya cambiado (menos confiable, pero es una capa extra)
        if ($_SESSION['_user_agent'] !== $userAgent) {
            error_log('Potential session hijacking: User-Agent changed');
            // En producción, puedes destruir la sesión aquí si lo consideras necesario
            // self::destroy();
            // return false;
        }

        return true;
    }

    /**
     * Obtiene la IP del cliente (intenta detectar IP real detrás de proxies)
     * 
     * @return string
     */
    private static function getClientIp() {
        // Si viene a través de proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Si viene a través de un servidor proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Puede haber múltiples IPs, tomar la primera
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        // IP estándar
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Validar que sea una IP válida
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * Establece un valor en la sesión
     * 
     * @param string $key Clave
     * @param mixed $value Valor
     * @return void
     */
    public static function set($key, $value) {
        self::ensureInitialized();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene un valor de la sesión
     * 
     * @param string $key Clave
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::ensureInitialized();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica si existe una clave en la sesión
     * 
     * @param string $key Clave
     * @return bool
     */
    public static function has($key) {
        self::ensureInitialized();
        return isset($_SESSION[$key]);
    }

    /**
     * Elimina una clave de la sesión
     * 
     * @param string $key Clave
     * @return void
     */
    public static function remove($key) {
        self::ensureInitialized();
        unset($_SESSION[$key]);
    }

    /**
     * Destruye completamente la sesión (logout)
     * 
     * @return void
     */
    public static function destroy() {
        self::ensureInitialized();
        
        // Eliminar toda la información de sesión
        $_SESSION = [];

        // Eliminar la cookie de sesión
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destruir la sesión del servidor
        session_destroy();
        self::$initialized = false;
    }

    /**
     * Verifica si hay una sesión activa y válida
     * 
     * @return bool
     */
    public static function isActive() {
        return session_status() === PHP_SESSION_ACTIVE && self::has('usuario_id');
    }

    /**
     * Asegura que la sesión esté inicializada
     * 
     * @return void
     */
    private static function ensureInitialized() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::initialize();
        }
    }

    /**
     * Obtiene toda la información del usuario en sesión
     * 
     * @return array|null
     */
    public static function getUser() {
        self::ensureInitialized();
        return self::has('usuario') ? $_SESSION['usuario'] : null;
    }

    /**
     * Obtiene el ID del usuario en sesión
     * 
     * @return int|null
     */
    public static function getUserId() {
        self::ensureInitialized();
        return self::get('usuario_id');
    }

    /**
     * Obtiene los roles del usuario en sesión
     * 
     * @return array
     */
    public static function getUserRoles() {
        self::ensureInitialized();
        return self::get('roles', []);
    }

    /**
     * Crea token CSRF y lo almacena en sesión
     * Debe usarse en cada formulario POST
     * 
     * @return string Token CSRF
     */
    public static function generateCSRFToken() {
        self::ensureInitialized();
        
        if (!self::has('_csrf_token')) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Valida el token CSRF
     * 
     * @param string $token Token a validar
     * @return bool
     */
    public static function validateCSRFToken($token) {
        self::ensureInitialized();
        
        if (!self::has('_csrf_token')) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

// Inicializar sesión automáticamente
SessionManager::initialize();

?>
