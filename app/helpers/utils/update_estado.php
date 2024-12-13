<?php
//update_estado.php
require_once 'SistemaGestion\app\config\session_config.php';
checkLogin();
include '../../config/conexion.php';

header('Content-Type: application/json');


try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pedido_id']) || !isset($data['estado_id'])) {
        throw new Exception('Datos incompletos');
    }
    
    $cnn = conectar();
    $sql = "UPDATE pedido SET estado_id = ? WHERE id_pedido = ?";
    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("ii", $data['estado_id'], $data['pedido_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error al actualizar el estado');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>