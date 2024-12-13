<?php
require_once '../../config/session_config.php';
require_once '../../config/conexion.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $cnn = conectar();
        $id = $_POST['id'];
        
        // Verificar que la alerta existe y pertenece al usuario actual
        $sql = "SELECT a.* 
                FROM alerta a
                JOIN pedido p ON a.pedido_id = p.id_pedido
                WHERE a.id_alerta = ? 
                AND p.usuario_id = ?";
                
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("ii", $id, $_SESSION['usuario_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Alerta no encontrada o no autorizada');
        }
        
        // Marcar la alerta como leída
        $sql = "UPDATE alerta SET estado = 'leida' WHERE id_alerta = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Error al actualizar la alerta');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido o ID no proporcionado'
    ]);
}
?>