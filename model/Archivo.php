<?php
class Archivo {
    private $id;
    private $nombre;
    private $fecha_subida;
    private $id_cliente;

    public function __construct($id = null, $nombre = null, $fecha_subida = null, $id_cliente = null) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->fecha_subida = $fecha_subida;
        $this->id_cliente = $id_cliente;
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

    public function getFechaSubida() {
        return $this->fecha_subida;
    }

    public function setFechaSubida($fecha_subida) {
        $this->fecha_subida = $fecha_subida;
    }

    public function getIdCliente() {
        return $this->id_cliente;
    }

    public function setIdCliente($id_cliente) {
        $this->id_cliente = $id_cliente;
    }

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE archivo SET nombre = ?, fecha_subida = ?, id_cliente = ? WHERE id = ?");
            $stmt->execute([$this->nombre, $this->fecha_subida, $this->id_cliente, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO archivo (nombre, fecha_subida, id_cliente) VALUES (?, ?, ?)");
            $stmt->execute([$this->nombre, $this->fecha_subida, $this->id_cliente]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM archivo WHERE id = ?");
        $stmt->execute([$this->id]);
    }
}
