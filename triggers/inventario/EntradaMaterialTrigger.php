<?php
require_once __DIR__ . '/../TriggerBase.php';

class EntradaMaterialTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_material = $datos['id_material'];
        $id_almacen  = $datos['id_almacen'];
        $cantidad    = $datos['cantidad'];

        // Verifica si ya existe el registro en inventarios
        $stmt = $this->pdo->prepare("
            SELECT stock FROM inventarios
            WHERE id_material = ? AND id_almacen = ?
        ");
        $stmt->execute([$id_material, $id_almacen]);
        $inv = $stmt->fetch();

        if ($inv) {
            // Actualiza stock existente
            $stmt2 = $this->pdo->prepare("
                UPDATE inventarios SET stock = stock + ?
                WHERE id_material = ? AND id_almacen = ?
            ");
            $stmt2->execute([$cantidad, $id_material, $id_almacen]);
        } else {
            // Crea registro nuevo con stock mínimo por defecto
            $stmt2 = $this->pdo->prepare("
                INSERT INTO inventarios (id_material, id_almacen, stock, stock_minimo)
                VALUES (?, ?, ?, 10)
            ");
            $stmt2->execute([$id_material, $id_almacen, $cantidad]);
        }
    }
}