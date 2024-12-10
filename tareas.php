<?php
require_once 'config/session_config.php';
checkLogin();
include 'conexion.php';
include 'check_alerts.php';

// Configuración inicial de paginación
$pagina_vencidas = isset($_GET['pagina']) && $_GET['tipo'] === 'atraso' ? (int)$_GET['pagina'] : 1;
$pagina_proximas = isset($_GET['pagina']) && $_GET['tipo'] === 'fecha' ? (int)$_GET['pagina'] : 1;
$limite = 2;


function getTotalAlertas($tipo) {
    $cnn = conectar();
    $sql = "SELECT COUNT(*) as total FROM alerta WHERE estado = 'no_leida' AND tipo = ?";
    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

// Obtener totales y calcular páginas
$total_vencidas = getTotalAlertas('atraso');
$total_proximas = getTotalAlertas('fecha');
$total_paginas_vencidas = ceil($total_vencidas / $limite);
$total_paginas_proximas = ceil($total_proximas / $limite);

// Calcular offsets
$offset_vencidas = ($pagina_vencidas - 1) * $limite;
$offset_proximas = ($pagina_proximas - 1) * $limite;

// Obtener alertas iniciales
$alertas_vencidas = getAlertas('atraso', $offset_vencidas, $limite);
$alertas_proximas = getAlertas('fecha', $offset_proximas, $limite);

try {
    $cnn = conectar();
    // Obtener tareas del usuario
    $sql = "SELECT p.titulo AS nombre_tarea, p.id_pedido, pr.nivel AS prioridad
            FROM pedido p
            INNER JOIN usuario u ON p.usuario_id = u.id_usuario
            INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
            WHERE p.usuario_id = ?";
    
    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $tareas = [];
    while ($row = $result->fetch_assoc()) {
        $tareas[] = $row;
    }

    checkDates();
    
} catch (PDOException $e) {
    error_log("Error en la conexión: " . $e->getMessage());
    $tareas = [];
}

function renderAlertas($alertas) {
    $html = '';
    foreach($alertas as $alerta) {
        $html .= "<div class='alerta {$alerta['tipo']}'>
                    <p>{$alerta['mensaje']}</p>
                    <button class='leer-btn' onclick='marcarLeida({$alerta['id_alerta']})'>Marcar como leída</button>
                </div>";
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interfaz de Usuario</title>
    <link rel="stylesheet" href="css/tareas.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="menu">
                <li onclick="location.href='tareas.php'">Notificaciones</li>
                <li onclick="location.href='informes.php'">Informes</li>
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <input type="text" placeholder="Buscar" class="search-bar">
                <div class="user-icon">👤 <?php echo $_SESSION['usuario_nombre']; ?></div>
            </header>

            <!-- Tasks Section -->
            <section class="project-section">
                <div class="task-list">
                    <h3>Tus tareas</h3>
                    <ul id="task-list">
                        <?php foreach ($tareas as $tarea): ?>
                            <li data-id="<?= $tarea['id_pedido'] ?>"><?= $tarea['nombre_tarea'] ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="task-details" id="task-details">
                    <h2 id="task-title">Selecciona una tarea</h2>
                    <div id="tarea" class="tarea">
                        <div class="info"><span>Responsable:</span> <span id="responsable">-</span></div>
                        <div class="info"><span>Fecha de entrega:</span> <span id="fecha-entrega">-</span></div>
                        <div class="info"><span>Proyecto:</span> <span id="proyecto">Proyecto 1</span></div>
                        <div class="description" id="task-description"></div>
                    </div>
                </div>
            </section>

            <!-- Alerts Section -->
            <div class="alerts-wrapper">
                <!-- Vencidos Section -->
                <section class="project-section alertas">
                    <div>
                        <h3>Vencidos</h3>
                        <div id="alertas-vencidos" class="alertas-container">
                            <?php echo renderAlertas($alertas_vencidas); ?>
                        </div>
                        <div class="pagination">
                            <button onclick="cambiarPagina('atraso', <?= $pagina_vencidas-1 ?>)" 
                                    <?= $pagina_vencidas <= 1 ? 'disabled' : '' ?> 
                                    class="prev-btn">Anterior</button>
                            <span class="pagina-actual"><?= $pagina_vencidas ?> / <?= $total_paginas_vencidas ?></span>
                            <button onclick="cambiarPagina('atraso', <?= $pagina_vencidas+1 ?>)"
                                    <?= $pagina_vencidas >= $total_paginas_vencidas ? 'disabled' : '' ?> 
                                    class="next-btn">Siguiente</button>
                        </div>
                    </div>
                </section>

                <!-- Próximos Section -->
                <section class="project-section alertas">
                    <div>
                        <h3>Próximos</h3>
                        <div id="alertas-proximos" class="alertas-container">
                            <?php echo renderAlertas($alertas_proximas); ?>
                        </div>
                        <div class="pagination">
                            <button onclick="cambiarPagina('fecha', <?= $pagina_proximas-1 ?>)"
                                    <?= $pagina_proximas <= 1 ? 'disabled' : '' ?> 
                                    class="prev-btn">Anterior</button>
                            <span class="pagina-actual"><?= $pagina_proximas ?> / <?= $total_paginas_proximas ?></span>
                            <button onclick="cambiarPagina('fecha', <?= $pagina_proximas+1 ?>)"
                                    <?= $pagina_proximas >= $total_paginas_proximas ? 'disabled' : '' ?> 
                                    class="next-btn">Siguiente</button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
    // Manejo de tareas
    const taskList = document.getElementById("task-list");
    const taskDetails = document.getElementById("task-details");

    taskList.addEventListener("click", async (e) => {
        if (e.target && e.target.nodeName === "LI") {
            const taskId = e.target.getAttribute("data-id");
            await loadTaskDetails(taskId);
        }
    });

    async function loadTaskDetails(taskId) {
        try {
            const response = await fetch(`get_task_details.php?id=${taskId}`);
            const data = await response.json();
            
            if (data) {
                document.getElementById("task-title").textContent = data.nombre_tarea;
                document.getElementById("responsable").textContent = data.responsable;
                document.getElementById("fecha-entrega").textContent = data.fecha_entrega;
                document.getElementById("task-description").textContent = data.descripcion;

                taskDetails.style.display = "block";
                document.getElementById("tarea").style.display = "block";
            }
        } catch (error) {
            console.error("Error al cargar los detalles de la tarea:", error);
        }
    }

    async function cambiarPagina(tipo, pagina) {
        const contenedor = tipo === 'atraso' ? 'alertas-vencidos' : 'alertas-proximos';
        const container = document.getElementById(contenedor);
        
        if (!container) {
            console.error(`Container ${contenedor} not found`);
            return;
        }

        // Add loading state
        container.style.opacity = '0.5';
        
        try {
            const response = await fetch(`get_alertas_ajax.php?tipo=${tipo}&pagina=${pagina}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error desconocido');
            }
            
            // Render alerts
            let html = '';
            if (!data.alertas || data.alertas.length === 0) {
                html = '<p class="no-alertas">No hay alertas para mostrar</p>';
            } else {
                data.alertas.forEach(alerta => {
                    html += `
                        <div class='alerta ${alerta.tipo}'>
                            <p>${alerta.mensaje}</p>
                            <button class='leer-btn' onclick='marcarLeida(${alerta.id_alerta})'>
                                Marcar como leída
                            </button>
                        </div>`;
                });
            }
            
            container.innerHTML = html;
            
            // Update pagination
            const paginationContainer = container.nextElementSibling;
            if (paginationContainer) {
                const paginaActual = paginationContainer.querySelector('.pagina-actual');
                if (paginaActual) {
                    paginaActual.textContent = `${data.pagina_actual} / ${data.total_paginas}`;
                }
                
                const prevBtn = paginationContainer.querySelector('.prev-btn');
                const nextBtn = paginationContainer.querySelector('.next-btn');
                
                if (prevBtn) {
                    prevBtn.disabled = data.pagina_actual <= 1;
                    prevBtn.onclick = () => cambiarPagina(tipo, data.pagina_actual - 1);
                }
                
                if (nextBtn) {
                    nextBtn.disabled = data.pagina_actual >= data.total_paginas;
                    nextBtn.onclick = () => cambiarPagina(tipo, data.pagina_actual + 1);
                }
            }
            
            // Update URL without page reload
            const url = new URL(window.location.href);
            url.searchParams.set('tipo', tipo);
            url.searchParams.set('pagina', data.pagina_actual);
            history.pushState({}, '', url);
            
        } catch (error) {
            console.error('Error al cambiar de página:', error);
            container.innerHTML = `
                <div class="error-mensaje">
                    <p>Error al cargar las alertas. Por favor, intente de nuevo.</p>
                    <button onclick="cambiarPagina('${tipo}', ${pagina})">Reintentar</button>
                </div>`;
        } finally {
            container.style.opacity = '1';
        }
}
    
    // Manejo de marcar como leída
    async function marcarLeida(id) {
        try {
            const response = await fetch('marcar_alerta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_alerta: id })
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Error al marcar como leída:', error);
        }
    }
    </script>
</body>
</html>