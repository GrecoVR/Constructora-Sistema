<?php
require_once __DIR__ . '/../TriggerBase.php';

class PagoEmpleadoFallidoTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_pago_empleado = $datos['id_pago_empleado'];
        $id_empleado      = $datos['id_empleado'];

        $stmt = $this->pdo->prepare("
            SELECT pe.monto, pe.fecha_pago, e.nombre as empleado
            FROM pagos_empleados pe
            JOIN empleados e ON e.id_empleado = pe.id_empleado
            WHERE pe.id_pago_empleado = ?
        ");
        $stmt->execute([$id_pago_empleado]);
        $info = $stmt->fetch();

        if ($info) {
            // Notifica al empleado
            $this->notificarEmpleado(
                $id_empleado,
                "❌ Pago fallido",
                "El pago de Bs {$info['monto']} con fecha {$info['fecha_pago']} 
                 no pudo procesarse. Contacta al área de RRHH para resolverlo."
            );

            // Notifica al contador (rol 6)
            $this->notificarRol(
                6,
                "❌ Pago fallido — " . $info['empleado'],
                "El pago de Bs {$info['monto']} al empleado '{$info['empleado']}' 
                 falló. Requiere revisión urgente."
            );
        }
    }
}   