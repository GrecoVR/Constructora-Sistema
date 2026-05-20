<?php
require_once __DIR__ . '/../TriggerBase.php';

class CotizacionAprobadaTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_cotizacion = $datos['id_cotizacion'];

        // Verifica que no tenga ya un contrato
        $stmt = $this->pdo->prepare("
            SELECT id_contrato FROM contratos
            WHERE id_cotizacion = ?
        ");
        $stmt->execute([$id_cotizacion]);

        if ($stmt->fetch()) return; // Ya tiene contrato

        // Obtiene info de la cotización
        $stmt2 = $this->pdo->prepare("
            SELECT co.monto_total, cl.nombre as cliente, co.id_cliente
            FROM cotizaciones co
            JOIN clientes cl ON cl.id_cliente = co.id_cliente
            WHERE co.id_cotizacion = ?
        ");
        $stmt2->execute([$id_cotizacion]);
        $cot = $stmt2->fetch();

        if (!$cot) return;

        // Crea contrato en borrador automáticamente
        $stmt3 = $this->pdo->prepare("
            INSERT INTO contratos (id_cotizacion, fecha_firma, clausulas, estado)
            VALUES (?, CURDATE(), 'Contrato generado automáticamente al aprobar cotización. Pendiente de revisión.', 'borrador')
        ");
        $stmt3->execute([$id_cotizacion]);

        // Notifica al director de proyectos
        $this->notificarRol(
            2,
            "📄 Nuevo contrato en borrador",
            "La cotización para '{$cot['cliente']}' fue aprobada. 
             Se generó un contrato en borrador por Bs {$cot['monto_total']}. 
             Favor revisar y activar."
        );

        // Notifica al gerente
        $this->notificarRol(
            12,
            "📄 Cotización aprobada — " . $cot['cliente'],
            "Se aprobó cotización por Bs {$cot['monto_total']} para '{$cot['cliente']}'. 
             Contrato en borrador generado."
        );
    }
}