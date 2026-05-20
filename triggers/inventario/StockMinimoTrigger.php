<?php
require_once __DIR__ . '/../TriggerBase.php';

class StockMinimoTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_material = $datos['id_material'];
        $id_almacen  = $datos['id_almacen'];

        $stmt = $this->pdo->prepare("
            SELECT i.stock, i.stock_minimo, m.nombre as material, a.nombre as almacen
            FROM inventarios i
            JOIN materiales m ON m.id_material = i.id_material
            JOIN almacenes a ON a.id_almacen = i.id_almacen
            WHERE i.id_material = ? AND i.id_almacen = ?
        ");
        $stmt->execute([$id_material, $id_almacen]);
        $inv = $stmt->fetch();

        if ($inv && $inv['stock'] <= $inv['stock_minimo'] && $inv['stock'] > 0) {
            $titulo    = "⚠️ Stock mínimo: " . $inv['material'];
            $contenido = "El material '{$inv['material']}' en '{$inv['almacen']}' "
                       . "tiene stock {$inv['stock']} (mínimo permitido: {$inv['stock_minimo']}). "
                       . "Se recomienda hacer un pedido.";

            // Notifica al encargado de almacén (rol 8)
            $this->notificarRol(8, $titulo, $contenido);

            // También notifica al director de proyectos (rol 2)
            $this->notificarRol(2, $titulo, $contenido);
        }
    }
}