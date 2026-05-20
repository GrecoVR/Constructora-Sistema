<?php
require_once '../TriggerBase.php';

class StockMinimoTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {

        $id = $datos['id_material'];

        $sql = "SELECT stock_actual, stock_minimo
                FROM materiales
                WHERE id_material = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        $material = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($material['stock_actual'] <= $material['stock_minimo']) {

            echo "ALERTA: Stock mínimo alcanzado";
        }
    }
}