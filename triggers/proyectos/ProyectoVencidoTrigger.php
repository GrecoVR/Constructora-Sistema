<?php
require_once __DIR__ . '/../TriggerBase.php';

class ProyectoVencidoTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        // Busca proyectos cuya fecha fin ya pasó y siguen en ejecucion o planificacion
        $stmt = $this->pdo->query("
            SELECT p.id_proyecto, p.nombre, p.fecha_fin_estimada,
                   co.id_cliente
            FROM proyectos p
            JOIN contratos c ON c.id_contrato = p.id_contrato
            JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
            WHERE p.estado IN ('ejecucion', 'planificacion')
            AND p.fecha_fin_estimada < CURDATE()
        ");
        $proyectos = $stmt->fetchAll();

        foreach ($proyectos as $p) {
            $titulo    = "⚠️ Proyecto vencido: " . $p['nombre'];
            $contenido = "El proyecto '{$p['nombre']}' tenía fecha de entrega 
                          {$p['fecha_fin_estimada']} y aún no ha finalizado.";

            // Notifica al gerente y director
            $this->notificarRol(12, $titulo, $contenido);
            $this->notificarRol(2,  $titulo, $contenido);
        }
    }
}