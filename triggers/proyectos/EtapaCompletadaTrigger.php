<?php
require_once __DIR__ . '/../TriggerBase.php';

class EtapaCompletadaTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_etapa   = $datos['id_etapa'];
        $porcentaje = $datos['porcentaje_avance'];

        if ($porcentaje < 100) return;

        // Obtiene info de la etapa y proyecto
        $stmt = $this->pdo->prepare("
            SELECT e.nombre as etapa, p.nombre as proyecto,
                   p.id_proyecto, co.id_cliente
            FROM etapas_proyecto e
            JOIN proyectos p ON p.id_proyecto = e.id_proyecto
            JOIN contratos c ON c.id_contrato = p.id_contrato
            JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
            WHERE e.id_etapa_proyecto = ?
        ");
        $stmt->execute([$id_etapa]);
        $info = $stmt->fetch();

        if (!$info) return;

        // Notifica al cliente
        $this->notificarCliente(
            $info['id_cliente'],
            "✅ Etapa completada: " . $info['etapa'],
            "La etapa '{$info['etapa']}' del proyecto '{$info['proyecto']}' 
             fue completada al 100%. Nuestro equipo continúa con la siguiente fase."
        );

        // Notifica al director de proyectos (rol 2)
        $this->notificarRol(
            2,
            "✅ Etapa completada: " . $info['etapa'],
            "La etapa '{$info['etapa']}' del proyecto '{$info['proyecto']}' llegó al 100%."
        );

        // Verifica si TODAS las etapas del proyecto están al 100%
        $stmt2 = $this->pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN porcentaje_avance = 100 THEN 1 ELSE 0 END) as completadas
            FROM etapas_proyecto
            WHERE id_proyecto = ?
        ");
        $stmt2->execute([$info['id_proyecto']]);
        $avance = $stmt2->fetch();

        if ($avance['total'] > 0 && $avance['total'] == $avance['completadas']) {
            // Cambia el proyecto a finalizado
            $stmt3 = $this->pdo->prepare("
                UPDATE proyectos SET estado = 'finalizado'
                WHERE id_proyecto = ?
            ");
            $stmt3->execute([$info['id_proyecto']]);

            // Notifica al cliente que el proyecto terminó
            $this->notificarCliente(
                $info['id_cliente'],
                "🎉 Proyecto finalizado: " . $info['proyecto'],
                "Todas las etapas del proyecto '{$info['proyecto']}' han sido completadas. 
                 Nos pondremos en contacto para coordinar la entrega formal."
            );

            // Notifica al gerente (rol 12)
            $this->notificarRol(
                12,
                "🎉 Proyecto finalizado: " . $info['proyecto'],
                "El proyecto '{$info['proyecto']}' fue marcado como finalizado automáticamente."
            );
        }
    }
}