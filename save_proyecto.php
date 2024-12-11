<?php
require_once 'config/session_config.php';
checkLogin();
include 'conexion.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $cnn = conectar();
    
    $nombre = $_POST['nombre_proyecto'];
    $descripcion = $_POST['descripcion'];
    $fecha_fin = $_POST['fecha_fin'];
    $cliente_id = $_POST['cliente_id'];
    
    $sql = "INSERT INTO proyecto (nombre, descripcion, fecha_inicio, fecha_fin, estado_id, cliente_id) 
            VALUES (?, ?, CURRENT_TIMESTAMP, ?, 2, ?)";
            
    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $descripcion, $fecha_fin, $cliente_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Proyecto creado correctamente',
            'id' => $cnn->insert_id
        ]);
    } else {
        throw new Exception('Error al crear el proyecto');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>