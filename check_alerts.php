<?php
require_once 'conexion.php';

function checkDates() {
    $cnn = conectar();
    
    // Verificar pedidos próximos a vencer (3 días antes)
    $sql = "SELECT p.id_pedido, p.titulo, p.fecha_entrega, u.nombre as responsable
            FROM pedido p
            INNER JOIN usuario u ON p.usuario_id = u.id_usuario
            WHERE p.fecha_entrega BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
            AND p.id_pedido NOT IN (
                SELECT pedido_id FROM alerta 
                WHERE tipo = 'fecha' 
                AND DATE(fecha_generacion) = CURDATE()
            )";
            
    $result = $cnn->query($sql);
    
    while($row = $result->fetch_assoc()) {
        // Generar alerta
        $mensaje = "El pedido '{$row['titulo']}' asignado a {$row['responsable']} vence el {$row['fecha_entrega']}";
        
        $insertSql = "INSERT INTO alerta (pedido_id, tipo, mensaje, fecha_generacion, estado) 
                      VALUES (?, 'fecha', ?, NOW(), 'no_leida')";
        $stmt = $cnn->prepare($insertSql);
        $stmt->bind_param("is", $row['id_pedido'], $mensaje);
        $stmt->execute();
    }
    
    // Verificar pedidos atrasados
    $sql = "SELECT p.id_pedido, p.titulo, p.fecha_entrega, u.nombre as responsable
            FROM pedido p
            INNER JOIN usuario u ON p.usuario_id = u.id_usuario
            WHERE p.fecha_entrega < NOW()
            AND p.id_pedido NOT IN (
                SELECT pedido_id FROM alerta 
                WHERE tipo = 'atraso'
                AND DATE(fecha_generacion) = CURDATE()
            )";
            
    $result = $cnn->query($sql);
    
    while($row = $result->fetch_assoc()) {
        $mensaje = "¡ATRASADO! El pedido '{$row['titulo']}' asignado a {$row['responsable']} venció el {$row['fecha_entrega']}";
        
        $insertSql = "INSERT INTO alerta (pedido_id, tipo, mensaje, fecha_generacion, estado) 
                      VALUES (?, 'atraso', ?, NOW(), 'no_leida')";
        $stmt = $cnn->prepare($insertSql);
        $stmt->bind_param("is", $row['id_pedido'], $mensaje);
        $stmt->execute();
    }
}

// Ejecutar verificación
checkDates();
?>