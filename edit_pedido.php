<?php
header('Content-Type: application/json');
require_once 'config/session_config.php';
require_once 'conexion.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['id_pedido'])) {
            throw new Exception('ID del pedido no proporcionado');
        }

        $cnn = conectar();
        
        $id_pedido = $_POST['id_pedido'];
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $fecha_entrega = $_POST['fecha-entrega'];
        $prioridad_id = $_POST['prioridad'];
        $usuario_id = $_POST['responsable-id'];
        
        $sql = "UPDATE pedido SET 
                titulo = ?,
                descripcion = ?,
                fecha_entrega = ?,
                prioridad_id = ?,
                usuario_id = ?
                WHERE id_pedido = ?";
                
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("sssiii", 
            $titulo, 
            $descripcion, 
            $fecha_entrega,
            $prioridad_id,
            $usuario_id,
            $id_pedido
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Error al actualizar el pedido');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>