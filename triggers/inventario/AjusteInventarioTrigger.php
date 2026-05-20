<?php
require_once __DIR__ . '/../TriggerBase.php';

class AjusteInventarioTrigger extends TriggerBase {

    public function ejecutar(array $datos): void {
        $id_material = $datos['id_material'];
        $id_almacen  = $datos['id_almacen'];
        $cantidad    = $datos['cantidad'];
        $id_usuario  = $datos['id_usuario'];

        // Aplica el ajuste en inventarios
        $stmt = $this->pdo->prepare("
            UPDATE inventarios SET stock = stock + ?
            WHERE id_material = ? AND id_almacen = ?
        ");
        $stmt->execute([$cantidad, $id_material, $id_almacen]);

        // Obtiene info para el log
        $stmt2 = $this->pdo->prepare("
            SELECT m.nombre as material, a.nombre as almacen,
                   us.nombre_usuario
            FROM materiales m
            JOIN almacenes a ON a.id_almacen = ?
            JOIN usuarios_sistema us ON us.id_usuario_sistema = ?
            WHERE m.id_material = ?
        ");
        $stmt2->execute([$id_almacen, $id_usuario, $id_material]);
        $info = $stmt2->fetch();

        if ($info) {
            $tipo    = $cantidad >= 0 ? 'positivo' : 'negativo';
            $titulo  = "📝 Ajuste de inventario registrado";
            $contenido = "El usuario '{$info['nombre_usuario']}' realizó un ajuste $tipo "
                       . "de $cantidad unidades en '{$info['material']}' "
                       . "almacén '{$info['almacen']}'.";

            // Notifica al gerente y director
            $this->notificarRol(12, $titulo, $contenido);
            $this->notificarRol(2,  $titulo, $contenido);
        }

        // Si después del ajuste el stock quedó bajo, dispara ese trigger también
        $manager = new TriggerManager($this->pdo);
        $manager->ejecutar('inventario.stock_minimo', $datos);
        $manager->ejecutar('inventario.stock_agotado', $datos);
    }
}