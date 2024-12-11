<?php
require_once 'config/session_config.php';
require_once 'model/Proyecto.php';
require_once 'model/Usuario.php';
require_once 'model/Alerta.php';
require_once 'model/Pedido.php';
require_once 'model/Cliente.php';
include 'check_alerts.php';

checkLogin();

$proyecto_id = isset($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 1;

$proyectoActual = Proyecto::findById($proyecto_id);

$usuarios = Usuario::findAll();

$proyectos = Proyecto::findAll();


function updateTaskState($pedido_id, $estado_id) {
    try {
        Pedido::updateEstado($pedido_id, $estado_id);
        return true;
    } catch (Exception $e) {
        echo "<script>alert('".$e->getMessage()."');</script>";
        return false;
    }
}

// Handle state update request
if (isset($_GET['update_estado']) && isset($_GET['pedido_id']) && isset($_GET['estado_id'])) {
    updateTaskState((int)$_GET['pedido_id'], (int)$_GET['estado_id']);
    header("Location: tareas.php");
    exit;
}

// Configuración inicial de paginación
$pagina_vencidas = isset($_GET['pagina']) && $_GET['tipo'] === 'atraso' ? (int)$_GET['pagina'] : 1;
$pagina_proximas = isset($_GET['pagina']) && $_GET['tipo'] === 'fecha' ? (int)$_GET['pagina'] : 1;
$limite = 2;

// Obtener totales y calcular páginas
$total_vencidas = Alerta::countByType('atraso');
$total_proximas = Alerta::countByType('fecha');
$total_paginas_vencidas = ceil($total_vencidas / $limite);
$total_paginas_proximas = ceil($total_proximas / $limite);

// Calcular offsets
$offset_vencidas = ($pagina_vencidas - 1) * $limite;
$offset_proximas = ($pagina_proximas - 1) * $limite;

// Obtener alertas iniciales
$alertas_vencidas = Alerta::findByType('atraso', $offset_vencidas, $limite);
$alertas_proximas = Alerta::findByType('fecha', $offset_proximas, $limite);

// Obtener tareas del usuario
$tareas = Pedido::findByUsuarioId($_SESSION['usuario_id']);

// Verificar fechas y alertas
checkDates();

function renderAlertas($alertas) {
    $html = '';
    foreach ($alertas as $alerta) {
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
    <!-- Modal de Proyecto -->
    <div class="modal-container" id="modal-proyecto">
        <div class="modal">
            <form method="post" id="proyectoForm">
                <input type="text" name="nombre_proyecto" placeholder="Nombre del Proyecto" class="modal-title" required>
                
                <div class="form-group">
                    <label for="cliente_proyecto"><span class="icon">🏢</span> Cliente</label>
                    <select class="combo" name="cliente_id" id="cliente_proyecto" required>
                        <?php
                       $result_clientes = Cliente::getAllOrderByName();

                       // Recorrer el vector de clientes
                    //    foreach ($result_clientes as $cliente) {
                    //        echo "ID: " . $cliente->id_cliente . " - Nombre: " . $cliente->nombre . "<br>";
                    //    }
                        echo "<pre>";
                        echo var_dump($result_clientes);
                        echo "</pre>";
                        
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descripcion_proyecto">Descripción</label>
                    <textarea id="descripcion_proyecto" name="descripcion" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="fecha_fin"><span class="icon">📅</span> Fecha de Finalización</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" required>
                </div>

                <button type="submit" class="btn-agregar">Crear Proyecto</button>
            </form>
        </div>
    </div>
    <div class="container">
         <!-- Sidebar -->
        <div class="sidebar">
            <ul class="menu">
                <li onclick="location.href='tareas.php'">Notificaciones</li>
                <li onclick="location.href='informes.php'">Informes</li>
            </ul>
            <div class="projects">
                <div class="alineacion">
                    <h3>Proyectos</h3>
                    <button class="add-project-btn" style="display: none;" onclick="mostrarModalProyecto()">+</button>
                </div>
                
                <div class="projects-list">
                    <?php foreach($proyectos as $proyecto): ?>
                        <div class="project <?php echo $proyecto['id_proyecto'] == $proyecto_id ? 'active' : ''; ?>" 
                             onclick="cambiarProyecto(<?php echo $proyecto['id_proyecto']; ?>)">
                            <div class="dot" style="background-color: #007bff;"></div>
                            <span><?php echo htmlspecialchars($proyecto['nombre']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <input type="text" placeholder="Buscar" class="search-bar">
                <div class="user-icon">👤 <?php 
                echo $_SESSION['usuario_nombre']; 
                echo $_SESSION['usuario_id']; 
                ?></div>
            </header>

            <!-- Tasks Section -->
            <section class="project-section">
                <div class="task-list">
                    <h3>Tus tareas</h3>
                    <ul id="task-list">
                        <?php foreach ($tareas as $tarea): ?>
                            <li data-id="<?= $tarea['id_pedido'] ?>" onclick="loadTaskDetails(<?= $tarea['id_pedido'] ?>)"><?= $tarea['nombre_tarea'] ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="task-details" id="task-details">
                <h2 id="task-title">Selecciona una tarea</h2>
                <div id="tarea" class="tarea">
                    <div class="info"><span>Responsable:</span> <span id="responsable">-</span></div>
                    <div class="info"><span>Fecha de entrega:</span> <span id="fecha-entrega">-</span></div>
                    <div class="info"><span>Proyecto:</span> <span id="proyecto">Proyecto 1</span></div>
                    <div class="info">
                        <span>Estado:</span>
                        <form method="GET" action="tareas.php">
                            <input type="hidden" name="pedido_id" id="pedido_id">
                            <select name="estado_id" id="estado" onchange="this.form.submit()">
                                <option value="1">Pendiente</option>
                                <option value="2">En Proceso</option>
                                <option value="3">Completado</option>
                                <option value="4">Cancelado</option>
                                <option value="5">Aplazado</option>
                            </select>
                            <input type="hidden" name="update_estado" value="true">
                        </form>
                    </div>
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
    let currentTaskId = null;
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
        currentTaskId = taskId;
        const response = await fetch(`get_task_details.php?id=${taskId}`);
        const data = await response.json();
        
        if (data) {
            document.getElementById("task-title").textContent = data.nombre_tarea;
            document.getElementById("responsable").textContent = data.responsable;
            document.getElementById("fecha-entrega").textContent = data.fecha_entrega;
            document.getElementById("task-description").textContent = data.descripcion;
            document.getElementById("estado").value = data.estado_id;
            document.getElementById("pedido_id").value = taskId;

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
            const response = await fetch(`get_alertas_ajax.php?tipo=${tipo}&pagina=${pagina}&id=${<?php echo $_SESSION['usuario_id']; ?>}`);
            console.log("response "+response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log("data "+data);
            
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
                    <p>Error al cargar las alertas. Por favor, intente de nuevo. </p>
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

    function mostrarModalProyecto() {
            document.getElementById('modal-proyecto').classList.add('active');
        }

        function cambiarProyecto(proyectoId) {
            window.location.href = `index.php?proyecto_id=${proyectoId}`;
        }
    </script>
</body>
</html>