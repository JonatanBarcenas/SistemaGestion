<?php
class TareaController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserTasks($usuario_id) {
        try {
            $sql = "SELECT 
                    p.id_pedido,
                    p.titulo,
                    p.descripcion,
                    p.fecha_creacion,
                    p.fecha_entrega,
                    e.nombre as estado,
                    pr.nivel as prioridad,
                    proy.nombre as proyecto
                    FROM pedido p
                    JOIN estado e ON p.estado_id = e.id_estado
                    JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
                    JOIN proyecto proy ON p.proyecto_id = proy.id_proyecto
                    WHERE p.usuario_id = ?
                    ORDER BY p.fecha_entrega ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $tareas = [];
            while($row = $result->fetch_assoc()) {
                $tareas[] = $row;
            }
            
            return ['success' => true, 'data' => $tareas];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getTaskDetails($id) {
        try {
            $sql = "SELECT 
                    p.*,
                    u.nombre as responsable,
                    e.nombre as estado,
                    pr.nivel as prioridad,
                    proy.nombre as proyecto,
                    c.nombre as cliente
                    FROM pedido p
                    JOIN usuario u ON p.usuario_id = u.id_usuario
                    JOIN estado e ON p.estado_id = e.id_estado
                    JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
                    JOIN proyecto proy ON p.proyecto_id = proy.id_proyecto
                    JOIN cliente c ON p.cliente_id = c.id_cliente
                    WHERE p.id_pedido = ?";
                    
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => true, 'data' => $result->fetch_assoc()];
            }
            throw new Exception("Tarea no encontrada");
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>