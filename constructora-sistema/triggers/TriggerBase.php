<?php

abstract class TriggerBase {

    protected PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    abstract public function ejecutar(array $datos): void;
}