<?php
header('Content-Type: application/json');

require_once '../../config/session_config.php';
require_once '../../config/conexion.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    try {
        $cnn = conectar();
        $cnn->begin_transaction();
        
        // 1. Eliminar comentarios relacionados
        $sql = "DELETE FROM comentario WHERE pedido_id = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // 2. Eliminar archivos relacionados
        $sql = "DELETE FROM archivo WHERE pedido_id = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // 3. Eliminar el pedido
        $sql = "DELETE FROM pedido WHERE id_pedido = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $cnn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception($cnn->error);
        }
        
    } catch (Exception $e) {
        $cnn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido o ID no proporcionado']);
}
?>