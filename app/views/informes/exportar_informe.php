<?php
require_once '../../config/session_config.php';
require_once '../../config/conexion.php';
require '../../../vendor/autoload.php'; // Para PHPSpreadsheet
checkLogin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

header('Content-Type: application/json');

function getDatosInforme($cnn) {
    $datos = [];
    
    // Pedidos con detalles
    $sql = "SELECT 
            p.titulo,
            p.descripcion,
            DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y') as fecha_creacion,
            DATE_FORMAT(p.fecha_entrega, '%d/%m/%Y') as fecha_entrega,
            u.nombre as responsable,
            e.nombre as estado,
            pr.nivel as prioridad,
            c.nombre as cliente,
            proy.nombre as proyecto
            FROM pedido p
            JOIN usuario u ON p.usuario_id = u.id_usuario
            JOIN estado e ON p.estado_id = e.id_estado
            JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
            JOIN cliente c ON p.cliente_id = c.id_cliente
            JOIN proyecto proy ON p.proyecto_id = proy.id_proyecto
            ORDER BY p.fecha_creacion DESC";
            
    $result = $cnn->query($sql);
    while($row = $result->fetch_assoc()) {
        $datos['pedidos'][] = $row;
    }
    
    // Estadísticas generales
    $sql = "SELECT 
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN fecha_entrega < CURDATE() THEN 1 ELSE 0 END) as pedidos_atrasados,
            COUNT(DISTINCT proyecto_id) as total_proyectos,
            COUNT(DISTINCT cliente_id) as total_clientes
            FROM pedido";
    $result = $cnn->query($sql);
    $datos['estadisticas'] = $result->fetch_assoc();
    
    return $datos;
}

try {
    $cnn = conectar();
    $tipo = $_GET['tipo'] ?? 'excel';
    $datos = getDatosInforme($cnn);
    
    if ($tipo === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Estilo para encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0066cc'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        
        // Estadísticas generales
        $sheet->setCellValue('A1', 'ESTADÍSTICAS GENERALES');
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A2', 'Total Pedidos');
        $sheet->setCellValue('B2', $datos['estadisticas']['total_pedidos']);
        $sheet->setCellValue('A3', 'Pedidos Atrasados');
        $sheet->setCellValue('B3', $datos['estadisticas']['pedidos_atrasados']);
        $sheet->setCellValue('A4', 'Total Proyectos');
        $sheet->setCellValue('B4', $datos['estadisticas']['total_proyectos']);
        $sheet->setCellValue('A5', 'Total Clientes');
        $sheet->setCellValue('B5', $datos['estadisticas']['total_clientes']);
        
        // Lista de pedidos
        $sheet->setCellValue('A7', 'LISTA DE PEDIDOS');
        $sheet->mergeCells('A7:I7');
        
        // Encabezados
        $headers = [
            'A8' => 'Título',
            'B8' => 'Proyecto',
            'C8' => 'Cliente',
            'D8' => 'Responsable',
            'E8' => 'Estado',
            'F8' => 'Prioridad',
            'G8' => 'Fecha Creación',
            'H8' => 'Fecha Entrega',
            'I8' => 'Descripción'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->applyFromArray($headerStyle);
        }
        
        // Datos
        $row = 9;
        foreach ($datos['pedidos'] as $pedido) {
            $sheet->setCellValue('A' . $row, $pedido['titulo']);
            $sheet->setCellValue('B' . $row, $pedido['proyecto']);
            $sheet->setCellValue('C' . $row, $pedido['cliente']);
            $sheet->setCellValue('D' . $row, $pedido['responsable']);
            $sheet->setCellValue('E' . $row, $pedido['estado']);
            $sheet->setCellValue('F' . $row, $pedido['prioridad']);
            $sheet->setCellValue('G' . $row, $pedido['fecha_creacion']);
            $sheet->setCellValue('H' . $row, $pedido['fecha_entrega']);
            $sheet->setCellValue('I' . $row, $pedido['descripcion']);
            $row++;
        }
        
        // Autoajustar columnas
        foreach(range('A','I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Generar el archivo
        $writer = new Xlsx($spreadsheet);
        $filename = 'Informe_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        
    } else if ($tipo === 'pdf') {
        // Aquí implementarías la exportación a PDF
        // Puedes usar TCPDF o MPDF
        echo json_encode(['error' => 'Exportación a PDF no implementada aún']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>