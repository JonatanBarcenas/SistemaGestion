<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    try {
        $cnn = conectar();
        
        // Verificar permisos
        $sql_permisos = "SELECT r.nombre as rol FROM usuario u 
                        INNER JOIN rol r ON u.rol_id = r.id_rol 
                        WHERE u.id_usuario = ?";
        $stmt = $cnn->prepare($sql_permisos);
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        
        if ($usuario['rol'] !== 'Administrador' && $usuario['rol'] !== 'jefe') {
            echo json_encode(['error' => 'No tienes permisos para eliminar pedidos']);
            exit;
        }
        
        // Eliminar comentarios asociados
        $sql_comentarios = "DELETE FROM comentario WHERE pedido_id = ?";
        $stmt = $cnn->prepare($sql_comentarios);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Eliminar archivos asociados
        $sql_archivos = "DELETE FROM archivo WHERE pedido_id = ?";
        $stmt = $cnn->prepare($sql_archivos);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Eliminar el pedido
        $sql_pedido = "DELETE FROM pedido WHERE id_pedido = ?";
        $stmt = $cnn->prepare($sql_pedido);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Error al eliminar el pedido']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Método no permitido']);
}
?>