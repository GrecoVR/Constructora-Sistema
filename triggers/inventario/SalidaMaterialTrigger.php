<?php
require_once __DIR__ . '/../TriggerBase.php';

class SalidaMaterialTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_material = $datos['id_material'];
        $id_almacen  = $datos['id_almacen'];
        $cantidad    = $datos['cantidad'];

        // Descuenta del stock
        $stmt = $this->pdo->prepare("
            UPDATE inventarios SET stock = stock - ?
            WHERE id_material = ? AND id_almacen = ?
        ");
        $stmt->execute([$cantidad, $id_material, $id_almacen]);

        // Después de descontar dispara stock mínimo y agotado
        $manager = new TriggerManager($this->pdo);
        $manager->ejecutar('inventario.stock_minimo', $datos);
        $manager->ejecutar('inventario.stock_agotado', $datos);
    }
}