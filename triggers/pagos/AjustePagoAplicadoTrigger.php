<?php
require_once __DIR__ . '/../TriggerBase.php';

class AjustePagoAplicadoTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_pago_empleado = $datos['id_pago_empleado'];
        $tipo             = $datos['tipo_ajuste'];
        $concepto         = $datos['concepto'];
        $monto            = $datos['monto'];

        $stmt = $this->pdo->prepare("
            SELECT pe.id_empleado
            FROM pagos_empleados pe
            WHERE pe.id_pago_empleado = ?
        ");
        $stmt->execute([$id_pago_empleado]);
        $info = $stmt->fetch();

        if ($info) {
            $tipo_texto = $tipo === 'percepcion' ? '✅ Bono aplicado' : '⚠️ Descuento aplicado';
            $this->notificarEmpleado(
                $info['id_empleado'],
                $tipo_texto,
                "Se aplicó un ajuste a tu pago: '$concepto' por Bs " . abs($monto) . "."
            );
        }
    }
}