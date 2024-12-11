
<?php
require_once 'conexion.php';
class Proyecto {
    private $id;
    private $nombre;
    private $descripcion;
    private $fecha_inicio;
    private $fecha_fin;
    private $estado_id;
    private $cliente_id;

    public function __construct($id = null, $nombre = null, $descripcion = null, $fecha_inicio = null) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->fecha_inicio = $fecha_inicio;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;
    }

    public function getFechaInicio() {
        return $this->fecha_inicio;
    }

    public function setFechaInicio($fecha_inicio) {
        $this->fecha_inicio = $fecha_inicio;
    }

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE proyecto SET nombre = ?, descripcion = ?, fecha_inicio = ? WHERE id = ?");
            $stmt->execute([$this->nombre, $this->descripcion, $this->fecha_inicio, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO proyecto (nombre, descripcion, fecha_inicio) VALUES (?, ?, ?)");
            $stmt->execute([$this->nombre, $this->descripcion, $this->fecha_inicio]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM proyecto WHERE id = ?");
        $stmt->execute([$this->id]);
    }

    public static function findById($id) {
        $cnn = conectar();
        $sql = "SELECT nombre FROM proyecto WHERE id_proyecto = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function findAll() {
        $cnn = conectar();
        $sql = "SELECT id_proyecto, nombre, estado_id FROM proyecto ORDER BY fecha_inicio DESC";
        $result = $cnn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getProyectoActual($proyectoId) {
        $cnn = conectar();
        $query = "SELECT nombre, cliente_id FROM proyecto WHERE id_proyecto = ?";
        $stmt = $cnn->prepare($query);
        $stmt->bind_param("i", $proyectoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getListaProyectos() {
        $cnn = conectar();
        $query = "SELECT id_proyecto, nombre, estado_id FROM proyecto ORDER BY fecha_inicio DESC";
        $result = $cnn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
