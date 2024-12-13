<?php
require_once(__DIR__ . '/../config/conexion.php');

class Pedido {
    private $id;
    private $descripcion;
    private $fecha_creacion;
    private $estado_id;

    public function __construct($id = null, $descripcion = null, $fecha_creacion = null, $estado_id = null) {
        $this->id = $id;
        $this->descripcion = $descripcion;
        $this->fecha_creacion = $fecha_creacion;
        $this->estado_id = $estado_id;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;
    }

    public function getFechaCreacion() {
        return $this->fecha_creacion;
    }

    public function setFechaCreacion($fecha_creacion) {
        $this->fecha_creacion = $fecha_creacion;
    }

    public function getEstadoId() {
        return $this->estado_id;
    }

    public function setEstadoId($estado_id) {
        $this->estado_id = $estado_id;
    }

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE pedido SET descripcion = ?, fecha_creacion = ?, estado_id = ? WHERE id = ?");
            $stmt->execute([$this->descripcion, $this->fecha_creacion, $this->estado_id, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO pedido (descripcion, fecha_creacion, estado_id) VALUES (?, ?, ?)");
            $stmt->execute([$this->descripcion, $this->fecha_creacion, $this->estado_id]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM pedido WHERE id = ?");
        $stmt->execute([$this->id]);
    }

    public static function updateEstado($pedido_id, $estado_id) {
        $cnn = conectar();
        $sql = "UPDATE pedido SET estado_id = ? WHERE id_pedido = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("ii", $estado_id, $pedido_id);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el estado.");
        }
    }

    public static function findByUsuarioId($usuario_id) {
        $cnn = conectar();
        $sql = "SELECT p.titulo AS nombre_tarea, p.id_pedido, pr.nivel AS prioridad, e.nombre AS estado, e.color AS estado_color
                FROM pedido p
                INNER JOIN usuario u ON p.usuario_id = u.id_usuario
                INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
                INNER JOIN estado e ON p.estado_id = e.id_estado
                WHERE p.usuario_id = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getPedidosByProyecto($proyectoId) {
        $cnn = conectar();
        $query = "
            SELECT 
                p.id_pedido, p.titulo AS nombre_tarea, u.nombre AS responsable,
                DATE_FORMAT(p.fecha_entrega, '%d - %M') AS fecha_entrega,
                pr.nivel AS prioridad, e.nombre AS estado, e.color AS estado_color
            FROM pedido p
            INNER JOIN usuario u ON p.usuario_id = u.id_usuario
            INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
            INNER JOIN estado e ON p.estado_id = e.id_estado
            WHERE p.proyecto_id = ?
        ";
        $stmt = $cnn->prepare($query);
        $stmt->bind_param("i", $proyectoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
