<?php
class AlertaController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function checkAlerts() {
        try {
            $this->alertasFechaProxima();
            $this->alertasAtrasados();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function alertasFechaProxima() {
        $sql = "INSERT INTO alerta (pedido_id, tipo, mensaje, fecha_generacion, estado)
                SELECT 
                    p.id_pedido,
                    'fecha',
                    CONCAT('El pedido ''', p.titulo, ''' asignado a ', u.nombre, ' vence el ', DATE_FORMAT(p.fecha_entrega, '%d/%m/%Y')),
                    NOW(),
                    'no_leida'
                FROM pedido p
                JOIN usuario u ON p.usuario_id = u.id_usuario
                WHERE p.fecha_entrega BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
                AND p.id_pedido NOT IN (
                    SELECT pedido_id FROM alerta 
                    WHERE tipo = 'fecha' 
                    AND DATE(fecha_generacion) = CURDATE()
                )";
        
        $this->conn->query($sql);
    }

    private function alertasAtrasados() {
        $sql = "INSERT INTO alerta (pedido_id, tipo, mensaje, fecha_generacion, estado)
                SELECT 
                    p.id_pedido,
                    'atraso',
                    CONCAT('¡ATRASADO! El pedido ''', p.titulo, ''' asignado a ', u.nombre, ' venció el ', DATE_FORMAT(p.fecha_entrega, '%d/%m/%Y')),
                    NOW(),
                    'no_leida'
                FROM pedido p
                JOIN usuario u ON p.usuario_id = u.id_usuario
                WHERE p.fecha_entrega < CURDATE()
                AND p.id_pedido NOT IN (
                    SELECT pedido_id FROM alerta 
                    WHERE tipo = 'atraso'
                    AND DATE(fecha_generacion) = CURDATE()
                )";
        
        $this->conn->query($sql);
    }

    public function getAlertas($usuario_id) {
        try {
            $sql = "SELECT a.*, p.titulo as pedido_titulo
                    FROM alerta a
                    JOIN pedido p ON a.pedido_id = p.id_pedido
                    WHERE p.usuario_id = ?
                    AND a.estado = 'no_leida'
                    ORDER BY a.fecha_generacion DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $alertas = [];
            while($row = $result->fetch_assoc()) {
                $alertas[] = $row;
            }
            
            return ['success' => true, 'data' => $alertas];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function marcarLeida($id_alerta) {
        try {
            $sql = "UPDATE alerta SET estado = 'leida' WHERE id_alerta = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id_alerta);
            
            if ($stmt->execute()) {
                return ['success' => true];
            }
            throw new Exception("Error al marcar la alerta como leída");
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>