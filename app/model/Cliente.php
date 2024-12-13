<?php

require_once(__DIR__ . '/../config/conexion.php');

class Cliente {
    private $id;
    private $nombre;
    private $email;

    public function __construct($id = null, $nombre = null, $email = null) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
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

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE cliente SET nombre = ?, email = ? WHERE id = ?");
            $stmt->execute([$this->nombre, $this->email, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cliente (nombre, email) VALUES (?, ?)");
            $stmt->execute([$this->nombre, $this->email]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM cliente WHERE id = ?");
        $stmt->execute([$this->id]);
    }

    public static function getAllOrderByName() {
        $cnn = conectar();
        $sql = "SELECT * FROM cliente ORDER BY nombre";
        $result = $cnn->query($sql);

        $clientes = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clientes[] = new Cliente($row['id_cliente'], $row['nombre'], $row['email']);
            }
        }
        return $clientes;
    }
}
