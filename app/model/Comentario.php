<?php
class Comentario {
    private $id;
    private $comentario;
    private $id_cliente;
    private $id_alerta;

    public function __construct($id = null, $comentario = null, $id_cliente = null, $id_alerta = null) {
        $this->id = $id;
        $this->comentario = $comentario;
        $this->id_cliente = $id_cliente;
        $this->id_alerta = $id_alerta;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getComentario() {
        return $this->comentario;
    }

    public function setComentario($comentario) {
        $this->comentario = $comentario;
    }

    public function getIdCliente() {
        return $this->id_cliente;
    }

    public function setIdCliente($id_cliente) {
        $this->id_cliente = $id_cliente;
    }

    public function getIdAlerta() {
        return $this->id_alerta;
    }

    public function setIdAlerta($id_alerta) {
        $this->id_alerta = $id_alerta;
    }

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE comentario SET comentario = ?, id_cliente = ?, id_alerta = ? WHERE id = ?");
            $stmt->execute([$this->comentario, $this->id_cliente, $this->id_alerta, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO comentario (comentario, id_cliente, id_alerta) VALUES (?, ?, ?)");
            $stmt->execute([$this->comentario, $this->id_cliente, $this->id_alerta]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM comentario WHERE id = ?");
        $stmt->execute([$this->id]);
    }
}
