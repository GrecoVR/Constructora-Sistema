<?php

class TriggerManager {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function ejecutar($trigger, $datos = []) {

        switch($trigger) {

            case 'inventario.stock_minimo':

                require_once 'inventario/StockMinimoTrigger.php';

                $obj = new StockMinimoTrigger($this->pdo);
                $obj->ejecutar($datos);

            break;
        }
    }
}