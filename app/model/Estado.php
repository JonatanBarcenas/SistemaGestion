<?php
class Estado {
    private $id;
    private $descripcion;

    public function __construct($id = null, $descripcion = null) {
        $this->id = $id;
        $this->descripcion = $descripcion;
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

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE estado SET descripcion = ? WHERE id = ?");
            $stmt->execute([$this->descripcion, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO estado (descripcion) VALUES (?)");
            $stmt->execute([$this->descripcion]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM estado WHERE id = ?");
        $stmt->execute([$this->id]);
    }
}
