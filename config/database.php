<?php
/**
 * config/database.php
 * 
 * Configuración de conexión PDO a MySQL
 * Implementa opciones de seguridad y manejo de errores
 * 
 * @author Backend Senior
 * @version 1.0
 */

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'empresa_constructora52');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);

/**
 * Clase DatabaseConnection
 * Gestiona la conexión PDO y proporciona métodos para consultas
 */
class DatabaseConnection {
    private static $instance = null;
    private $connection;
    private $lastError = null;

    /**
     * Constructor privado para patrón Singleton
     */
    private function __construct() {
        try {
            // DSN (Data Source Name) con charset explícito
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            // Opciones de PDO para máxima seguridad
            $options = [
                // Modo de error: lanzar excepciones
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // Convertir tipos de datos automáticamente
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // Preparar sentencias del lado del cliente (más seguro)
                PDO::ATTR_EMULATE_PREPARES => false,
                
                // Persistencia de conexión desactivada en producción
                PDO::ATTR_PERSISTENT => false,
            ];

            // Crear instancia de PDO
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Opcional: Establecer zona horaria
            $this->connection->exec("SET time_zone = '-04:00'"); // Ajusta según tu región
            
        } catch (PDOException $e) {
            // Registrar error sin exponer detalles sensibles
            $this->lastError = 'Error de conexión a base de datos';
            error_log('Database Connection Error: ' . $e->getMessage(), 0);
            
            // En desarrollo, puedes descomentar:
            // die('Error de conexión: ' . $e->getMessage());
            throw new Exception('No se pudo conectar a la base de datos');
        }
    }

    /**
     * Obtiene la instancia única de la conexión (Singleton)
     * 
     * @return DatabaseConnection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión PDO
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Ejecuta una consulta SELECT y retorna todos los resultados
     * 
     * @param string $sql Consulta SQL preparada
     * @param array $params Parámetros para la consulta
     * @return array|false
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Database Error (fetchAll): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta una consulta SELECT y retorna un solo resultado
     * 
     * @param string $sql Consulta SQL preparada
     * @param array $params Parámetros para la consulta
     * @return array|false
     */
    public function fetch($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Database Error (fetch): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta una consulta INSERT, UPDATE o DELETE
     * 
     * @param string $sql Consulta SQL preparada
     * @param array $params Parámetros para la consulta
     * @return int|false Número de filas afectadas
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Database Error (execute): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el ID de la última fila insertada
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Inicia una transacción
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Confirma una transacción
     * 
     * @return bool
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Revierte una transacción
     * 
     * @return bool
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }

    /**
     * Obtiene el último error
     * 
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Evita clonación del Singleton
     */
    private function __clone() {}

    /**
     * Evita deserialización del Singleton
     */
    public function __wakeup() {}
}

// Crear alias para facilitar el uso
$db = DatabaseConnection::getInstance()->getConnection();

?>
