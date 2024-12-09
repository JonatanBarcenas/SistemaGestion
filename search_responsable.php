<?php
include 'conexion.php';

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    try {
        $cnn = conectar();

        $sql = "SELECT id_usuario, nombre FROM usuario WHERE nombre LIKE ?";
        $stmt = $cnn->prepare($sql);
        $searchTerm = "%" . $query . "%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        $responsables = [];
        while ($row = $result->fetch_assoc()) {
            $responsables[] = $row;
        }

        echo json_encode($responsables);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}
