<?php
require_once 'config/session_config.php';
checkLogin();

require_once 'conexion.php';
require_once 'model/Proyecto.php';
require_once 'model/Pedido.php';

$cnn = conectar();
$proyectoManager = new Proyecto();
$pedidoManager = new Pedido();

try {
    $proyectoId = isset($_GET['proyecto_id']) ? $_GET['proyecto_id'] : 1;

    // Obtener información del proyecto actual
    $proyectoActual = $proyectoManager->getProyectoActual($proyectoId);
    $clienteId = $proyectoActual['cliente_id'];

    // Obtener lista de proyectos
    $proyectos = $proyectoManager->getListaProyectos();

    // Obtener pedidos del proyecto actual
    $pedidos = $pedidoManager->getPedidosByProyecto($proyectoId);

    // Renderizar la tabla
    $tabla = '';
    foreach ($pedidos as $pedido) {
        $clasePrioridad = $pedido['prioridad'] === 'Alta' ? 'high-priority' :
                          ($pedido['prioridad'] === 'Media' ? 'medium-priority' : 'low-priority');

        $claseEstado = match ($pedido['estado']) {
            'Pendiente' => 'amarillo',
            'En Proceso' => 'azul',
            'Completado' => 'verde',
            'Cancelado' => 'rojo',
            'Aplazado' => 'gris',
            default => 'amarillo',
        };

        $tabla .= "
            <tr>
                <td>{$pedido['nombre_tarea']}</td>
                <td>{$pedido['responsable']}</td>
                <td>{$pedido['fecha_entrega']}</td>
                <td><button class='$claseEstado'>{$pedido['estado']}</button></td>
                <td><button class='$clasePrioridad'>{$pedido['prioridad']}</button></td>
                <td>
                    <button onclick='editarPedido({$pedido['id_pedido']})' class='btn-editar'>✏️</button>
                    <button onclick='eliminarPedido({$pedido['id_pedido']})' class='btn-eliminar'>🗑️</button>
                </td>
            </tr>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/index.css">
    <title>Gestión de Proyectos</title>
</head>
<body>
    <!-- Modal de Pedidos -->
    <div class="modal-container" id="modal-container">
        <div class="modal">
            <form method="post" id="pedidoForm">
                <input type="hidden" name="id_pedido" id="id_pedido">
                <input type="hidden" name="proyecto_id" value="<?php echo $proyecto_id; ?>">
                <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                <input type="text" name="titulo" placeholder="INGRESE EL NOMBRE" class="modal-title" required>
                <div class="form-group">
                    <label for="responsable"><span class="icon">👤</span> Responsable</label>
                    <select id="responsable" class="combo" name="responsable-id" required>
                        <option value="">Seleccione un responsable</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['id_usuario'] ?>"><?= $usuario['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fecha"><span class="icon">📅</span> Fecha de entrega</label>
                    <input type="date" id="fecha" name='fecha-entrega' required>
                </div>
                
                <div class="form-group">
                    <label for="prioridad"><span class="icon">🔥</span> Prioridad</label>
                    <select class='combo' name="prioridad" id="prioridad" required>
                        <option value="1">Baja</option>
                        <option value="2">Media</option>
                        <option value="3">Alta</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado"><span class="icon">🔄</span> Estado</label>
                    <select class='combo' name="estado" id="estado" required>
                        <option value="1">Pendiente</option>
                        <option value="2">En Proceso</option>
                        <option value="3">Completado</option>
                        <option value="4">Cancelado</option>
                        <option value="5">Aplazado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn-agregar" id="btn-submit">Guardar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Proyecto -->
    <div class="modal-container" id="modal-proyecto">
        <div class="modal">
            <form method="post" id="proyectoForm">
                <input type="text" name="nombre_proyecto" placeholder="Nombre del Proyecto" class="modal-title" required>
                
                <div class="form-group">
                    <label for="cliente_proyecto"><span class="icon">🏢</span> Cliente</label>
                    <select class="combo" name="cliente_id" id="cliente_proyecto" required>
                        <?php
                        $sql_clientes = "SELECT id_cliente, nombre FROM cliente ORDER BY nombre";
                        $result_clientes = $cnn->query($sql_clientes);
                        while($cliente = $result_clientes->fetch_assoc()) {
                            echo "<option value='".$cliente['id_cliente']."'>".$cliente['nombre']."</option>";
                        }
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
                    <button class="add-project-btn" onclick="mostrarModalProyecto()">+</button>
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
            <div class="logout-container">
                <button class="logout-btn" onclick="location.href='logout.php'">Cerrar Sesión</button>
            </div>
        </div>

        <!-- Main content -->
        <main class="main-content">
            <header class="header">
                <input type="text" placeholder="Buscar" class="search-bar">
                <div class="user-icon">👤 <?php echo isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario'; ?></div>
            </header>
            <section class="project-section">
                <h2><?php echo htmlspecialchars($proyectoActual['nombre']); ?></h2>
                <button id="add-task" class="add-task">Agregar Tarea +</button>
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>Nombre de la tarea</th>
                            <th>Responsable</th>
                            <th>Fecha de entrega</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $tabla; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const addTaskButton = document.getElementById("add-task");
            const modalContainer = document.getElementById("modal-container");
            const pedidoForm = document.getElementById("pedidoForm");
            
            addTaskButton.addEventListener("click", () => {
                pedidoForm.reset();
                document.getElementById('id_pedido').value = '';
                document.getElementById('btn-submit').textContent = 'Agregar';
                modalContainer.classList.add("active");
            });

            modalContainer.addEventListener("click", (e) => {
                if (e.target === modalContainer) {
                    modalContainer.classList.remove("active");
                }
            });

            const inputResponsable = document.getElementById("responsable");
            const resultadosContainer = document.getElementById("resultados");
            const responsableIdField = document.getElementById("responsable-id");

            inputResponsable.addEventListener("input", async () => {
                const query = inputResponsable.value.trim();
                if (query.length === 0) {
                    resultadosContainer.innerHTML = "";
                    return;
                }

                try {
                    const response = await fetch(`search_responsable.php?query=${query}`);
                    const data = await response.json();
                    renderResults(data);
                } catch (error) {
                    console.error("Error fetching responsables:", error);
                }
            });

            function renderResults(data) {
                resultadosContainer.innerHTML = "";
                data.forEach((item) => {
                    const li = document.createElement("li");
                    li.textContent = item.nombre;
                    li.dataset.id = item.id_usuario;
                    resultadosContainer.appendChild(li);

                    li.addEventListener("click", () => {
                        inputResponsable.value = item.nombre;
                        responsableIdField.value = item.id_usuario;
                        resultadosContainer.innerHTML = "";
                    });
                });
            }

            pedidoForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(pedidoForm);
                const id = document.getElementById('id_pedido').value;
                
                try {
                    const url = id ? 'edit_pedido.php' : 'save_pedido.php';
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert(id ? 'Pedido actualizado correctamente' : 'Pedido creado correctamente');
                        window.location.reload();
                    } else {
                        throw new Error(data.error || 'Error al procesar el pedido');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud: ' + error.message);
                }
            });

            // Manejar el formulario de proyecto
            const proyectoForm = document.getElementById('proyectoForm');
            proyectoForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                
                try {
                    const response = await fetch('save_proyecto.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Proyecto creado correctamente');
                        window.location.reload();
                    } else {
                        throw new Error(data.error || 'Error al crear el proyecto');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al crear el proyecto: ' + error.message);
                }
            });
        });

        function mostrarModalProyecto() {
            document.getElementById('modal-proyecto').classList.add('active');
        }

        function cambiarProyecto(proyectoId) {
            window.location.href = `index.php?proyecto_id=${proyectoId}`;
        }

        async function editarPedido(id) {
    try {
        const response = await fetch(`get_pedido.php?id=${id}`);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error al obtener los datos del pedido');
        }
        
        const pedido = result.data;
        
        // Get all form elements
        const elements = {
            id_pedido: document.getElementById('id_pedido'),
            titulo: document.querySelector('input[name="titulo"]'),
            descripcion: document.querySelector('textarea[name="descripcion"]'),
            fecha_entrega: document.querySelector('input[name="fecha-entrega"]'),
            responsable: document.querySelector('#responsable'),
            prioridad: document.querySelector('#prioridad'),
            estado: document.querySelector('#estado')
        };

        // Check if all elements exist
        for (const [key, element] of Object.entries(elements)) {
            if (!element) {
                throw new Error(`No se encontró el elemento ${key}`);
            }
        }

        // Set values only if elements exist
        elements.id_pedido.value = pedido.id_pedido;
        elements.titulo.value = pedido.titulo;
        elements.descripcion.value = pedido.descripcion;
        elements.fecha_entrega.value = pedido.fecha_entrega;
        elements.responsable.value = pedido.usuario_id;
        elements.prioridad.value = pedido.prioridad_id;
        elements.estado.value = pedido.estado_id;
        
        // Update button text and show modal
        const submitBtn = document.getElementById('btn-submit');
        if (submitBtn) {
            submitBtn.textContent = 'Actualizar';
        }

        const modalContainer = document.getElementById('modal-container');
        if (modalContainer) {
            modalContainer.classList.add('active');
        } else {
            throw new Error('No se encontró el modal');
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar los datos del pedido: ' + error.message);
    }
}

        async function eliminarPedido(id) {
            if (!confirm('¿Está seguro de que desea eliminar este pedido?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('id', id);
                
                const response = await fetch('delete_pedido.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Error al eliminar el pedido');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al eliminar el pedido');
            }
        }
    </script>
</body>
</html>