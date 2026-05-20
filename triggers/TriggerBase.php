<?php
abstract class TriggerBase {
    protected PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    abstract public function ejecutar(array $datos): void;

    // Método auxiliar para insertar notificación a un empleado
    protected function notificarEmpleado(int $id_empleado, string $titulo, string $contenido): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO notificaciones_empleados (id_empleado, titulo, contenido)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$id_empleado, $titulo, $contenido]);
    }

    // Método auxiliar para insertar notificación a un cliente
    protected function notificarCliente(int $id_cliente, string $titulo, string $contenido): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO notificaciones_clientes (id_cliente, titulo, contenido)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$id_cliente, $titulo, $contenido]);
    }

    // Método auxiliar para notificar a todos los usuarios de un rol
    protected function notificarRol(int $id_rol, string $titulo, string $contenido): void {
        $stmt = $this->pdo->prepare("
            SELECT us.id_empleado
            FROM usuarios_roles ur
            JOIN usuarios_sistema us ON us.id_usuario_sistema = ur.id_usuario_sistema
            WHERE ur.id_rol = ? AND us.estado = 'activo'
        ");
        $stmt->execute([$id_rol]);
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($empleados as $emp) {
            $this->notificarEmpleado($emp['id_empleado'], $titulo, $contenido);
        }
    }
}