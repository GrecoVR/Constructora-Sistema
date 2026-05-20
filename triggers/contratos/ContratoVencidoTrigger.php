<?php
require_once __DIR__ . '/../TriggerBase.php';

class ContratoVencidoTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        // Busca contratos activos cuyos proyectos ya vencieron
        $stmt = $this->pdo->query("
            SELECT c.id_contrato, p.nombre as proyecto,
                   p.fecha_fin_estimada, cl.nombre as cliente
            FROM contratos c
            JOIN proyectos p ON p.id_contrato = c.id_contrato
            JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
            JOIN clientes cl ON cl.id_cliente = co.id_cliente
            WHERE c.estado = 'activo'
            AND p.fecha_fin_estimada < CURDATE()
            AND p.estado NOT IN ('finalizado', 'cancelado')
        ");
        $contratos = $stmt->fetchAll();

        foreach ($contratos as $c) {
            $titulo    = "📋 Contrato por vencer — " . $c['proyecto'];
            $contenido = "El contrato del proyecto '{$c['proyecto']}' con '{$c['cliente']}' 
                          tenía fecha estimada {$c['fecha_fin_estimada']} y el proyecto 
                          aún está activo. Revisar estado.";

            $this->notificarRol(2,  $titulo, $contenido);
            $this->notificarRol(12, $titulo, $contenido);
        }
    }
}