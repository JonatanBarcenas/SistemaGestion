<?php
require_once(__DIR__ . '/../config/conexion.php');

class Usuario {
    private $id;
    private $nombre;
    private $email;
    private $id_role;

    public function __construct($id = null, $nombre = null, $email = null, $id_role = null) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->id_role = $id_role;
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

    public function getIdRole() {
        return $this->id_role;
    }

    public function setIdRole($id_role) {
        $this->id_role = $id_role;
    }

    public function save($pdo) {
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE usuario SET nombre = ?, email = ?, id_role = ? WHERE id = ?");
            $stmt->execute([$this->nombre, $this->email, $this->id_role, $this->id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO usuario (nombre, email, id_role) VALUES (?, ?, ?)");
            $stmt->execute([$this->nombre, $this->email, $this->id_role]);
            $this->id = $pdo->lastInsertId();
        }
    }

    public function delete($pdo) {
        $stmt = $pdo->prepare("DELETE FROM usuario WHERE id = ?");
        $stmt->execute([$this->id]);
    }

    public static function findByEmail($cnn, $email) {
        $sql = "SELECT id_usuario, nombre, email, rol_id FROM usuario WHERE email = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new Usuario($row['id_usuario'], $row['nombre'], $row['email'], $row['rol_id']);
        }

        return null; 
    }

    public function verifyPassword($cnn, $password){
        $sql = "SELECT * FROM usuario WHERE password = ?";
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("s", $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return true;
        }

        return false; 
    }

    public static function findAll() {
        $cnn = conectar();
        $sql = "SELECT id_usuario, nombre FROM usuario ORDER BY nombre";
        $result = $cnn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
