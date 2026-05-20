<?php
require_once __DIR__ . '/TriggerBase.php';

// Inventario
require_once __DIR__ . '/inventario/StockMinimoTrigger.php';
require_once __DIR__ . '/inventario/StockAgotadoTrigger.php';
require_once __DIR__ . '/inventario/EntradaMaterialTrigger.php';
require_once __DIR__ . '/inventario/SalidaMaterialTrigger.php';
require_once __DIR__ . '/inventario/AjusteInventarioTrigger.php';

// Proyectos
require_once __DIR__ . '/proyectos/EtapaCompletadaTrigger.php';
require_once __DIR__ . '/proyectos/ProyectoVencidoTrigger.php';

// Contratos
require_once __DIR__ . '/contratos/CotizacionAprobadaTrigger.php';
require_once __DIR__ . '/contratos/ContratoVencidoTrigger.php';

// Empleados
require_once __DIR__ . '/empleados/AsignacionNuevaTrigger.php';

// Pagos
require_once __DIR__ . '/pagos/PagoEmpleadoFallidoTrigger.php';
require_once __DIR__ . '/pagos/AjustePagoAplicadoTrigger.php';

class TriggerManager {
    private PDO $pdo;
    private array $triggers = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->registrar();
    }

    private function registrar(): void {
        // Inventario
        $this->triggers['inventario.stock_minimo']    = new StockMinimoTrigger($this->pdo);
        $this->triggers['inventario.stock_agotado']   = new StockAgotadoTrigger($this->pdo);
        $this->triggers['inventario.entrada']         = new EntradaMaterialTrigger($this->pdo);
        $this->triggers['inventario.salida']          = new SalidaMaterialTrigger($this->pdo);
        $this->triggers['inventario.ajuste']          = new AjusteInventarioTrigger($this->pdo);

        // Proyectos
        $this->triggers['proyectos.etapa_completada'] = new EtapaCompletadaTrigger($this->pdo);
        $this->triggers['proyectos.vencido']          = new ProyectoVencidoTrigger($this->pdo);

        // Contratos
        $this->triggers['contratos.cotizacion_aprobada'] = new CotizacionAprobadaTrigger($this->pdo);
        $this->triggers['contratos.vencido']             = new ContratoVencidoTrigger($this->pdo);

        // Empleados
        $this->triggers['empleados.asignacion_nueva']   = new AsignacionNuevaTrigger($this->pdo);
        
        // Pagos
        $this->triggers['pagos.pago_empleado_fallido']  = new PagoEmpleadoFallidoTrigger($this->pdo);
        $this->triggers['pagos.ajuste_aplicado']        = new AjustePagoAplicadoTrigger($this->pdo);
    }

    public function ejecutar(string $nombre, array $datos = []): void {
        if (isset($this->triggers[$nombre])) {
            try {
                $this->triggers[$nombre]->ejecutar($datos);
            } catch (Exception $e) {
                error_log("Error en trigger '$nombre': " . $e->getMessage());
            }
        }
    }
}