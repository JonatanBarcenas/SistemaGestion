<?php
function conectar() {
    try {
        $cnn = new mysqli("localhost", "root", "@Canelo67", "publimpacto");
        if ($cnn->connect_error) {
            throw new Exception("Error de conexión: " . $cnn->connect_error);
        }
        return $cnn;
    } catch (Exception $e) {
        throw new Exception("Error al conectar con la base de datos: " . $e->getMessage());
    }
}
?>

