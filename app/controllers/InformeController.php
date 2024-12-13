<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InformeController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getEstadisticas() {
        try {
            // Estadísticas generales
            $sql = "SELECT 
                    COUNT(*) as total_pedidos,
                    SUM(CASE WHEN fecha_entrega < CURDATE() THEN 1 ELSE 0 END) as pedidos_atrasados,
                    COUNT(DISTINCT proyecto_id) as total_proyectos,
                    COUNT(DISTINCT cliente_id) as total_clientes,
                    AVG(DATEDIFF(fecha_entrega, fecha_creacion)) as tiempo_promedio
                    FROM pedido";
            
            $result = $this->conn->query($sql);
            $stats = $result->fetch_assoc();

            // Estadísticas por estado
            $sql = "SELECT 
                    e.nombre as estado,
                    COUNT(*) as cantidad
                    FROM pedido p
                    JOIN estado e ON p.estado_id = e.id_estado
                    GROUP BY e.id_estado";
            
            $result = $this->conn->query($sql);
            $stats['por_estado'] = [];
            while($row = $result->fetch_assoc()) {
                $stats['por_estado'][] = $row;
            }

            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function exportarExcel() {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Encabezados
            $sheet->setCellValue('A1', 'Título');
            $sheet->setCellValue('B1', 'Responsable');
            $sheet->setCellValue('C1', 'Proyecto');
            $sheet->setCellValue('D1', 'Estado');
            $sheet->setCellValue('E1', 'Prioridad');
            $sheet->setCellValue('F1', 'Fecha Creación');
            $sheet->setCellValue('G1', 'Fecha Entrega');
            
            // Datos
            $sql = "SELECT 
                    p.titulo,
                    u.nombre as responsable,
                    pr.nombre as proyecto,
                    e.nombre as estado,
                    pri.nivel as prioridad,
                    p.fecha_creacion,
                    p.fecha_entrega
                    FROM pedido p
                    JOIN usuario u ON p.usuario_id = u.id_usuario
                    JOIN proyecto pr ON p.proyecto_id = pr.id_proyecto
                    JOIN estado e ON p.estado_id = e.id_estado
                    JOIN prioridad pri ON p.prioridad_id = pri.id_prioridad
                    ORDER BY p.fecha_creacion DESC";
            
            $result = $this->conn->query($sql);
            $row = 2;
            
            while($data = $result->fetch_assoc()) {
                $sheet->setCellValue('A'.$row, $data['titulo']);
                $sheet->setCellValue('B'.$row, $data['responsable']);
                $sheet->setCellValue('C'.$row, $data['proyecto']);
                $sheet->setCellValue('D'.$row, $data['estado']);
                $sheet->setCellValue('E'.$row, $data['prioridad']);
                $sheet->setCellValue('F'.$row, $data['fecha_creacion']);
                $sheet->setCellValue('G'.$row, $data['fecha_entrega']);
                $row++;
            }

            // Autoajustar columnas
            foreach(range('A','G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            
            // Generar archivo temporal
            $temp_file = tempnam(sys_get_temp_dir(), 'informe_');
            $writer->save($temp_file);
            
            return ['success' => true, 'file' => $temp_file];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>