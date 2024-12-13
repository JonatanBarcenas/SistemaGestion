<?php
class ProyectoController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO proyecto (
                    nombre, 
                    descripcion, 
                    fecha_inicio, 
                    fecha_fin, 
                    estado_id, 
                    cliente_id
                ) VALUES (?, ?, CURRENT_TIMESTAMP, ?, 2, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssi", 
                $data['nombre_proyecto'],
                $data['descripcion'],
                $data['fecha_fin'],
                $data['cliente_id']
            );

            if ($stmt->execute()) {
                return ['success' => true, 'id' => $this->conn->insert_id];
            }
            throw new Exception("Error al crear el proyecto");
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAll() {
        try {
            $sql = "SELECT p.*, e.nombre as estado, c.nombre as cliente
                    FROM proyecto p
                    JOIN estado e ON p.estado_id = e.id_estado
                    JOIN cliente c ON p.cliente_id = c.id_cliente
                    ORDER BY p.fecha_inicio DESC";
            
            $result = $this->conn->query($sql);
            $proyectos = [];
            
            while($row = $result->fetch_assoc()) {
                $proyectos[] = $row;
            }
            
            return ['success' => true, 'data' => $proyectos];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>