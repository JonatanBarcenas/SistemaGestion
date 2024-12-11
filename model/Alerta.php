<?php
require_once 'conexion.php';
class Alerta {
    private $id;
    private $mensaje;
    private $fecha_generacion;
    private $pedido_id;
    private $tipo;
    private $estado;

    public function __construct($id = null, $mensaje = null, $fecha_generacion = null) {
        $this->id = $id;
        $this->mensaje = $mensaje;
        $this->fecha_generacion = $fecha_generacion;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getMensaje() {
        return $this->mensaje;
    }

    public function setMensaje($mensaje) {
        $this->mensaje = $mensaje;
    }

    public function getFechaGeneracion() {
        return $this->fecha_generacion;
    }

    public function setFechaGeneracion($fecha_generacion) {
        $this->fecha_generacion = $fecha_generacion;
    }

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE alerta SET mensaje = ?, fecha_generacion = ? WHERE id = ?");
            $stmt->execute([$this->mensaje, $this->fecha_generacion, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO alerta (mensaje, fecha_generacion) VALUES (?, ?)");
            $stmt->execute([$this->mensaje, $this->fecha_generacion]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM alerta WHERE id = ?");
        $stmt->execute([$this->id]);
    }

    public static function countByType($type) {
        $cnn = conectar();
        $sql = "SELECT COUNT(*) as total FROM alerta WHERE estado = 'no_leida' AND tipo = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    public static function findByType($type, $offset, $limit) {
        $cnn = conectar();
        $sql = "SELECT * FROM alerta WHERE tipo = ? LIMIT ?, ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("sii", $type, $offset, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
