<?php
// Prevent errors from being output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json');

// Start session and include required files
session_start();
require_once 'conexion.php';
require_once 'check_alerts.php';

try {
    // Check session
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Sesión no válida');
    }

    // Get and validate parameters
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $limite = 4; // Alertas por página

    // Validate alert type
    if (!in_array($tipo, ['atraso', 'fecha'])) {
        throw new Exception('Tipo de alerta inválido');
    }

    // Calculate offset
    $offset = ($pagina - 1) * $limite;

    // Get alerts
    $alertas = getAlertas($tipo, $offset, $limite);

    // Get total alerts for pagination
    $cnn = conectar();
    $sql = "SELECT COUNT(*) as total FROM alerta WHERE estado = 'no_leida' AND tipo = ?";
    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Calculate total pages
    $total_paginas = ceil($total / $limite);

    // Validate current page
    if ($pagina > $total_paginas && $total_paginas > 0) {
        $pagina = $total_paginas;
    }
    if ($pagina < 1) {
        $pagina = 1;
    }

    // Format alerts for response
    $alertas_formateadas = array_map(function($alerta) {
        return [
            'id_alerta' => $alerta['id_alerta'],
            'mensaje' => $alerta['mensaje'],
            'tipo' => $alerta['tipo'],
            'fecha_generacion' => $alerta['fecha_generacion']
        ];
    }, $alertas);

    // Send success response
    echo json_encode([
        'success' => true,
        'alertas' => $alertas_formateadas,
        'pagina_actual' => $pagina,
        'total_paginas' => $total_paginas,
        'total_alertas' => $total
    ]);

} catch (Exception $e) {
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>