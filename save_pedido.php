<?php
header('Content-Type: application/json');
require_once 'config/session_config.php';
require_once 'conexion.php';
checkLogin();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar datos requeridos
    $campos_requeridos = ['titulo', 'descripcion', 'fecha-entrega', 'responsable-id', 'prioridad'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("El campo $campo es requerido");
        }
    }

    $cnn = conectar();
    
    // Preparar los datos
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha-entrega'];
    $usuario_id = $_POST['responsable-id'];
    $prioridad_id = $_POST['prioridad'];
    $estado_id = 1; // Estado inicial
    $cliente_id = 1; // Cliente por defecto, ajustar según necesidad
    
    // Insertar el pedido
    $sql = "INSERT INTO pedido (
                titulo, 
                descripcion, 
                fecha_creacion, 
                fecha_entrega, 
                estado_id, 
                prioridad_id, 
                cliente_id, 
                usuario_id
            ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
            
    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("sssiiis", 
        $titulo, 
        $descripcion, 
        $fecha_entrega,
        $estado_id,
        $prioridad_id,
        $cliente_id,
        $usuario_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Pedido creado correctamente',
            'id' => $cnn->insert_id
        ]);
    } else {
        throw new Exception('Error al crear el pedido');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>