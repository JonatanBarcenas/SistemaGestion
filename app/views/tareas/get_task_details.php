<?php
//get_task_details.php
header('Content-Type: application/json'); // Especifica que la respuesta será JSON

include '../../config/conexion.php';

if (isset($_GET['id'])) {
    $taskId = $_GET['id'];

    try {
        $cnn = conectar();
        
        $sql = "SELECT p.titulo, u.nombre AS responsable, DATE_FORMAT(p.fecha_entrega, '%d - %M') AS fecha_entrega, p.descripcion
                FROM pedido p
                INNER JOIN usuario u ON p.usuario_id = u.id_usuario
                WHERE p.id_pedido = ?";
        
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $taskId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $task = $result->fetch_assoc();
            echo json_encode($task); // Devuelve los detalles de la tarea como JSON
        } else {
            echo json_encode(["error" => "Tarea no encontrada"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error en la conexión: " . $e->getMessage()]);
    }
}
?>
