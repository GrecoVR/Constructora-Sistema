<?php
require_once __DIR__ . '/../TriggerBase.php';

class AsignacionNuevaTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_empleado = $datos['id_empleado'];
        $id_proyecto = $datos['id_proyecto'];
        $id_cargo    = $datos['id_cargo'];

        $stmt = $this->pdo->prepare("
            SELECT e.nombre as empleado, p.nombre as proyecto,
                   c.nombre as cargo
            FROM empleados e
            JOIN proyectos p ON p.id_proyecto = ?
            JOIN cargos c ON c.id_cargo = ?
            WHERE e.id_empleado = ?
        ");
        $stmt->execute([$id_proyecto, $id_cargo, $id_empleado]);
        $info = $stmt->fetch();

        if ($info) {
            $this->notificarEmpleado(
                $id_empleado,
                "📋 Nueva asignación de proyecto",
                "Fuiste asignado al proyecto '{$info['proyecto']}' 
                 con el cargo '{$info['cargo']}'. Bienvenido al equipo."
            );
        }
    }
}