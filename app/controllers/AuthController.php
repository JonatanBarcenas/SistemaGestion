<?php
class AuthController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        try {
            $sql = "SELECT id_usuario, nombre, password, rol_id 
                    FROM usuario 
                    WHERE email = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // if (password_verify($password, $user['password'])) {
                if ($password == $user['password']) {
                    $_SESSION['usuario_id'] = $user['id_usuario'];
                    $_SESSION['usuario_nombre'] = $user['nombre'];
                    $_SESSION['rol_id'] = $user['rol_id'];
                    
                    return ['success' => true];
                }
                throw new Exception("Contraseña incorrecta");
            }
            throw new Exception("Usuario no encontrado");
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        return ['success' => true];
    }

    public function checkAuth() {
        if (!isset($_SESSION['usuario_id'])) {
            return ['success' => false, 'error' => 'No autorizado'];
        }
        return ['success' => true];
    }

    public function getUserInfo($id) {
        try {
            $sql = "SELECT u.id_usuario, u.nombre, u.email, r.nombre as rol
                    FROM usuario u
                    JOIN rol r ON u.rol_id = r.id_rol
                    WHERE u.id_usuario = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => true, 'data' => $result->fetch_assoc()];
            }
            throw new Exception("Usuario no encontrado");
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>