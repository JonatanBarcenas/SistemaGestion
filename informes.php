<?php
require_once 'config/session_config.php';
checkLogin();
include 'conexion.php';
$cnn = conectar();

function getDashboardData($cnn) {
    $data = [];
    
    // 1. Informe de Rendimiento
    // Tiempo promedio de completación
    $sql = "SELECT 
            AVG(DATEDIFF(fecha_entrega, fecha_creacion)) as tiempo_promedio,
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN fecha_entrega >= CURDATE() THEN 1 ELSE 0 END) as pedidos_tiempo,
            (SUM(CASE WHEN fecha_entrega >= CURDATE() THEN 1 ELSE 0 END) / COUNT(*)) * 100 as tasa_completacion
            FROM pedido";
    $result = $cnn->query($sql);
    $data['rendimiento'] = $result->fetch_assoc();

    // Pedidos completados por período (últimos 6 meses)
    $sql = "SELECT 
            DATE_FORMAT(fecha_entrega, '%Y-%m') as periodo,
            COUNT(*) as total_completados,
            SUM(CASE WHEN fecha_entrega >= fecha_creacion THEN 1 ELSE 0 END) as completados_tiempo
            FROM pedido
            WHERE fecha_entrega >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha_entrega, '%Y-%m')
            ORDER BY periodo DESC";
    $result = $cnn->query($sql);
    $data['completados_periodo'] = [];
    while($row = $result->fetch_assoc()) {
        $data['completados_periodo'][] = $row;
    }

    // 2. Informe por Estado
    $sql = "SELECT 
            e.nombre as estado,
            e.color,
            COUNT(*) as cantidad,
            GROUP_CONCAT(p.titulo) as pedidos
            FROM pedido p
            JOIN estado e ON p.estado_id = e.id_estado
            GROUP BY e.id_estado, e.nombre, e.color";
    $result = $cnn->query($sql);
    $data['por_estado'] = [];
    while($row = $result->fetch_assoc()) {
        $data['por_estado'][] = $row;
    }

    // Pedidos retrasados por estado
    $sql = "SELECT 
            e.nombre as estado,
            COUNT(*) as retrasados
            FROM pedido p
            JOIN estado e ON p.estado_id = e.id_estado
            WHERE p.fecha_entrega < CURDATE()
            GROUP BY e.id_estado";
    $result = $cnn->query($sql);
    $data['retrasados_estado'] = [];
    while($row = $result->fetch_assoc()) {
        $data['retrasados_estado'][] = $row;
    }

    // 3. Informe por Cliente
    $sql = "SELECT 
            c.nombre as cliente,
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN p.fecha_entrega < CURDATE() THEN 1 ELSE 0 END) as pedidos_retrasados,
            c.empresa,
            MAX(p.fecha_creacion) as ultimo_pedido
            FROM pedido p
            JOIN cliente c ON p.cliente_id = c.id_cliente
            GROUP BY c.id_cliente";
    $result = $cnn->query($sql);
    $data['por_cliente'] = [];
    while($row = $result->fetch_assoc()) {
        $data['por_cliente'][] = $row;
    }

    // Historial detallado por cliente
    $sql = "SELECT 
            c.nombre as cliente,
            p.titulo,
            p.fecha_creacion,
            p.fecha_entrega,
            e.nombre as estado,
            pr.nivel as prioridad
            FROM pedido p
            JOIN cliente c ON p.cliente_id = c.id_cliente
            JOIN estado e ON p.estado_id = e.id_estado
            JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
            ORDER BY c.nombre, p.fecha_creacion DESC";
    $result = $cnn->query($sql);
    $data['historial_cliente'] = [];
    while($row = $result->fetch_assoc()) {
        $data['historial_cliente'][] = $row;
    }

    // 4. Informe Temporal
    // Pedidos por semana
    $sql = "SELECT 
            YEARWEEK(fecha_creacion) as semana,
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN fecha_entrega < CURDATE() THEN 1 ELSE 0 END) as retrasados
            FROM pedido
            WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(fecha_creacion)
            ORDER BY semana DESC";
    $result = $cnn->query($sql);
    $data['por_semana'] = [];
    while($row = $result->fetch_assoc()) {
        $data['por_semana'][] = $row;
    }

    // Tendencias de carga de trabajo
    $sql = "SELECT 
            DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
            COUNT(*) as nuevos_pedidos,
            SUM(CASE WHEN fecha_entrega < CURDATE() THEN 1 ELSE 0 END) as retrasados,
            COUNT(DISTINCT usuario_id) as usuarios_activos
            FROM pedido
            WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
            ORDER BY mes DESC";
    $result = $cnn->query($sql);
    $data['tendencias'] = [];
    while($row = $result->fetch_assoc()) {
        $data['tendencias'][] = $row;
    }

    return $data;
}

$dashboardData = getDashboardData($cnn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes - Sistema de Gestión</title>
    <link rel="stylesheet" href="css/informes.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="menu">
                <li onclick="location.href='tareas.php'">Notificaciones</li>
                <li onclick="location.href='informes.php'" class="active">Informes</li>
            </ul>
            <div class="projects">
                <h3>Proyectos</h3>
                <div class="project">
                    <div class="dot" style="background-color: yellow;"></div>
                    <span onclick="location.href='index.php'">Proyecto 1</span>
                </div>
                <div class="project">
                    <div class="dot" style="background-color: cyan;"></div>
                    <span>Proyecto 2</span>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <main class="main-content">
            <header class="header">
                <h1>Panel de Informes</h1>
                <div class="header-controls">
                    <select id="periodoSelect" class="form-control">
                        <option value="mes">Este Mes</option>
                        <option value="trimestre">Último Trimestre</option>
                        <option value="semestre">Último Semestre</option>
                        <option value="anio">Este Año</option>
                    </select>
                    <div class="user-icon">👤 <?php echo isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario'; ?></div>
                </div>
            </header>

            <!-- Navegación de pestañas -->
            <div class="tabs">
                <button class="tab-button active" data-tab="rendimiento">Rendimiento</button>
                <button class="tab-button" data-tab="estados">Estados</button>
                <button class="tab-button" data-tab="clientes">Clientes</button>
                <button class="tab-button" data-tab="temporal">Temporal</button>
            </div>

            <!-- Contenido de las pestañas -->
            <div class="tab-content">
                <!-- 1. Pestaña de Rendimiento -->
                <div id="rendimiento" class="tab-pane active">
                    <div class="dashboard-grid">
                        <!-- KPIs de Rendimiento -->
                        <div class="dashboard-card">
                            <h3>Métricas de Rendimiento</h3>
                            <div class="kpi-grid">
                                <div class="kpi-item">
                                    <span class="kpi-value"><?php echo round($dashboardData['rendimiento']['tiempo_promedio'], 1); ?></span>
                                    <span class="kpi-label">Días Promedio</span>
                                </div>
                                <div class="kpi-item">
                                    <span class="kpi-value"><?php echo round($dashboardData['rendimiento']['tasa_completacion'], 1); ?>%</span>
                                    <span class="kpi-label">Tasa de Completación</span>
                                </div>
                                <div class="kpi-item">
                                    <span class="kpi-value"><?php echo $dashboardData['rendimiento']['total_pedidos']; ?></span>
                                    <span class="kpi-label">Total Pedidos</span>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Completación -->
                        <div class="dashboard-card">
                            <h3>Completación por Período</h3>
                            <div class="chart-container">
                                <canvas id="completacionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Pestaña de Estados -->
                <div id="estados" class="tab-pane">
                    <div class="dashboard-grid">
                        <!-- Tabla de Estados -->
                        <div class="dashboard-card full-width">
                            <h3>Distribución por Estados</h3>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Estado</th>
                                            <th>Cantidad</th>
                                            <th>Retrasados</th>
                                            <th>Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($dashboardData['por_estado'] as $estado): 
                                            $estadoClase = strtolower(str_replace(' ', '-', $estado['estado']));
                                            $retrasados = 0;
                                            foreach($dashboardData['retrasados_estado'] as $retrasado) {
                                                if($retrasado['estado'] === $estado['estado']) {
                                                    $retrasados = $retrasado['retrasados'];
                                                    break;
                                                }
                                            }
                                            $porcentaje = ($estado['cantidad'] / array_sum(array_column($dashboardData['por_estado'], 'cantidad'))) * 100;
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="estado-badge" style="background-color: <?php echo $estado['color']; ?>">
                                                        <?php echo $estado['estado']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $estado['cantidad']; ?></td>
                                                <td class="warning"><?php echo $retrasados; ?></td>
                                                <td>
                                                    <div class="progress-bar">
                                                        <div class="progress" 
                                                             style="width: <?php echo $porcentaje; ?>%; background-color: <?php echo $estado['color']; ?>">
                                                        </div>
                                                    </div>
                                                    <span class="porcentaje-texto"><?php echo round($porcentaje, 1); ?>%</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Gráfico de Estados -->
                        <div class="dashboard-card">
                            <h3>Distribución Visual de Estados</h3>
                            <div class="chart-container">
                                <canvas id="estadosChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 3. Pestaña de Clientes -->
                <div id="clientes" class="tab-pane">
                    <div class="dashboard-grid">
                        <!-- Resumen por Cliente -->
                        <div class="dashboard-card full-width">
                            <h3>Resumen por Cliente</h3>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Empresa</th>
                                            <th>Total Pedidos</th>
                                            <th>Retrasados</th>
                                            <th>Último Pedido</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($dashboardData['por_cliente'] as $cliente): ?>
                                            <tr>
                                                <td><?php echo $cliente['cliente']; ?></td>
                                                <td><?php echo $cliente['empresa']; ?></td>
                                                <td><?php echo $cliente['total_pedidos']; ?></td>
                                                <td class="warning"><?php echo $cliente['pedidos_retrasados']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($cliente['ultimo_pedido'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Historial de Pedidos -->
                        <div class="dashboard-card">
                            <h3>Historial de Pedidos por Cliente</h3>
                            <select id="clienteSelect" class="form-control">
                                <?php 
                                $clientes_unicos = array_unique(array_column($dashboardData['historial_cliente'], 'cliente'));
                                foreach($clientes_unicos as $cliente): 
                                ?>
                                    <option value="<?php echo $cliente; ?>"><?php echo $cliente; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="historialPedidos" class="table-responsive">
                                <!-- Se llenará con JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Pestaña Temporal -->
                <div id="temporal" class="tab-pane">
                    <div class="dashboard-grid">
                        <!-- Tendencias -->
                        <div class="dashboard-card">
                            <h3>Tendencias de Carga de Trabajo</h3>
                            <div class="chart-container">
                                <canvas id="tendenciasChart"></canvas>
                            </div>
                        </div>

                        <!-- Estadísticas por Semana -->
                        <div class="dashboard-card">
                            <h3>Estadísticas Semanales</h3>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Semana</th>
                                            <th>Total Pedidos</th>
                                            <th>Retrasados</th>
                                            <th>Tasa de Éxito</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($dashboardData['por_semana'] as $semana): 
                                            $tasa_exito = ($semana['total_pedidos'] - $semana['retrasados']) / $semana['total_pedidos'] * 100;
                                        ?>
                                            <tr>
                                                <td>Semana <?php echo date('W', strtotime($semana['semana'] . ' +0 day')); ?></td>
                                                <td><?php echo $semana['total_pedidos']; ?></td>
                                                <td class="warning"><?php echo $semana['retrasados']; ?></td>
                                                <td>
                                                    <div class="progress-bar">
                                                        <div class="progress" style="width: <?php echo $tasa_exito; ?>%"></div>
                                                    </div>
                                                    <?php echo round($tasa_exito, 1); ?>%
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de exportación -->
            <div class="export-buttons">
                <button onclick="exportarInforme('pdf')" class="btn-export pdf">
                    <i class="fas fa-file-pdf"></i> Exportar a PDF
                </button>
                <button onclick="exportarInforme('excel')" class="btn-export excel">
                    <i class="fas fa-file-excel"></i> Exportar a Excel
                </button>
            </div>
        </main>
    </div>

    <script>
        // Pasar los datos de PHP a JavaScript
        const dashboardData = <?php echo json_encode($dashboardData); ?>;
    </script>
    <script src="js/informes.js"></script>
</body>
</html>