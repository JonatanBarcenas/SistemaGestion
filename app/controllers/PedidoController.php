<?php
class PedidoController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO pedido (titulo, descripcion, fecha_creacion, fecha_entrega, estado_id, prioridad_id, cliente_id, usuario_id, proyecto_id) 
                    VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssiiiii", 
                $data['titulo'],
                $data['descripcion'],
                $data['fecha_entrega'],
                $data['estado_id'],
                $data['prioridad_id'],
                $data['cliente_id'],
                $data['usuario_id'],
                $data['proyecto_id']
            );

            if ($stmt->execute()) {
                return ['success' => true, 'id' => $this->conn->insert_id];
            }
            throw new Exception("Error al crear el pedido");
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data) {
        try {
            $sql = "UPDATE pedido SET 
                    titulo = ?,
                    descripcion = ?,
                    fecha_entrega = ?,
                    estado_id = ?,
                    prioridad_id = ?,
                    usuario_id = ?
                    WHERE id_pedido = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssiiii", 
                $data['titulo'],
                $data['descripcion'],
                $data['fecha_entrega'],
                $data['estado_id'],
                $data['prioridad_id'],
                $data['usuario_id'],
                $id
            );

            if ($stmt->execute()) {
                return ['success' => true];
            }
            throw new Exception("Error al actualizar el pedido");
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id) {
        try {
            $this->conn->begin_transaction();

            // Eliminar comentarios relacionados
            $sql = "DELETE FROM comentario WHERE pedido_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Eliminar archivos relacionados
            $sql = "DELETE FROM archivo WHERE pedido_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Eliminar el pedido
            $sql = "DELETE FROM pedido WHERE id_pedido = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $this->conn->commit();
                return ['success' => true];
            }

            throw new Exception("Error al eliminar el pedido");
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function get($id) {
        try {
            $sql = "SELECT p.*, u.nombre as responsable
                    FROM pedido p
                    JOIN usuario u ON p.usuario_id = u.id_usuario
                    WHERE p.id_pedido = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => true, 'data' => $result->fetch_assoc()];
            }
            
            throw new Exception("Pedido no encontrado");
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>