<?php
include 'conexion.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cnn = conectar();
        
        $id_pedido = $_POST['id_pedido'];
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $fecha_entrega = $_POST['fecha_entrega'];
        $prioridad_id = $_POST['prioridad_id'];
        $usuario_id = $_POST['usuario_id'];
        
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
            echo json_encode(['error' => 'No tienes permisos para modificar pedidos']);
            exit;
        }
        
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
            echo json_encode(['error' => 'Error al actualizar el pedido']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $cnn = conectar();
        $id = $_GET['id'];
        
        $sql = "SELECT p.*, pr.nivel as prioridad, u.nombre as responsable
                FROM pedido p
                INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
                INNER JOIN usuario u ON p.usuario_id = u.id_usuario
                WHERE p.id_pedido = ?";
                
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Pedido no encontrado']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>