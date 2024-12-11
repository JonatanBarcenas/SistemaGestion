<?php
header('Content-Type: application/json');
require_once 'config/session_config.php';
require_once 'conexion.php';
checkLogin();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }

    if (!isset($_GET['id'])) {
        throw new Exception('ID no proporcionado');
    }

    $id = $_GET['id'];
    $cnn = conectar();
    
    // Obtener los datos del pedido
    $sql = "SELECT 
                p.id_pedido,
                p.titulo,
                p.descripcion,
                p.fecha_entrega,
                p.estado_id,
                p.prioridad_id,
                p.cliente_id,
                p.usuario_id,
                u.nombre as responsable,
                pr.nivel as prioridad
            FROM pedido p
            INNER JOIN usuario u ON p.usuario_id = u.id_usuario
            INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
            WHERE p.id_pedido = ?";
            
    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Pedido no encontrado');
    }
    
    $pedido = $result->fetch_assoc();
    
    // Formatear fecha para el input type="date"
    $pedido['fecha_entrega'] = date('Y-m-d', strtotime($pedido['fecha_entrega']));
    
    echo json_encode([
        'success' => true,
        'data' => $pedido
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>