<?php
require_once __DIR__ . '/../TriggerBase.php';

class StockAgotadoTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_material = $datos['id_material'];
        $id_almacen  = $datos['id_almacen'];

        $stmt = $this->pdo->prepare("
            SELECT i.stock, m.nombre as material, a.nombre as almacen
            FROM inventarios i
            JOIN materiales m ON m.id_material = i.id_material
            JOIN almacenes a ON a.id_almacen = i.id_almacen
            WHERE i.id_material = ? AND i.id_almacen = ?
        ");
        $stmt->execute([$id_material, $id_almacen]);
        $inv = $stmt->fetch();

        if ($inv && $inv['stock'] <= 0) {
            $titulo    = "🚨 Stock agotado: " . $inv['material'];
            $contenido = "El material '{$inv['material']}' en '{$inv['almacen']}' "
                       . "está completamente agotado. Se requiere pedido urgente.";

            // Notifica a encargado almacén, director y gerente
            $this->notificarRol(8,  $titulo, $contenido);
            $this->notificarRol(2,  $titulo, $contenido);
            $this->notificarRol(12, $titulo, $contenido);
        }
    }
}