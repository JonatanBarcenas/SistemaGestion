<?php
require_once 'config/session_config.php';
checkLogin();

include 'conexion.php';

try {
    $cnn = conectar();

    // Obtener el proyecto_id de la URL o usar un valor predeterminado
    $proyecto_id = isset($_GET['proyecto_id']) ? $_GET['proyecto_id'] : 1;

    // Obtener información del proyecto actual
    $sql_proyecto_actual = "SELECT nombre, cliente_id FROM proyecto WHERE id_proyecto = ?";
    $stmt = $cnn->prepare($sql_proyecto_actual);
    $stmt->bind_param("i", $proyecto_id);
    $stmt->execute();
    $proyecto_actual = $stmt->get_result()->fetch_assoc();
    $cliente_id = $proyecto_actual['cliente_id'];
    
    $sql_usuarios = "SELECT id_usuario, nombre FROM usuario ORDER BY nombre";
    $result_usuarios = $cnn->query($sql_usuarios);
    $usuarios = [];
    while($usuario = $result_usuarios->fetch_assoc()) {
        $usuarios[] = $usuario;
    }

    // Obtener lista de proyectos
    $sql_proyectos = "SELECT id_proyecto, nombre, estado_id FROM proyecto ORDER BY fecha_inicio DESC";
    $result_proyectos = $cnn->query($sql_proyectos);
    $proyectos = [];
    while($proyecto = $result_proyectos->fetch_assoc()) {
        $proyectos[] = $proyecto;
    }

    // Consulta principal de pedidos
    $sql = "SELECT 
            p.id_pedido,
            p.titulo AS nombre_tarea,
            u.nombre AS responsable,
            DATE_FORMAT(p.fecha_entrega, '%d - %M') AS fecha_entrega,
            pr.nivel AS prioridad,
            e.nombre AS estado,
            e.color AS estado_color,
            p.usuario_id,
            p.estado_id,
            p.prioridad_id,
            p.cliente_id
        FROM pedido p
        INNER JOIN usuario u ON p.usuario_id = u.id_usuario
        INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
        INNER JOIN estado e ON p.estado_id = e.id_estado
        WHERE p.proyecto_id = ?";

    $stmt = $cnn->prepare($sql);
    $stmt->bind_param("i", $proyecto_id);
    $stmt->execute();
    $consult = $stmt->get_result();
    
    $tabla = "";
    while($resultados = $consult->fetch_array(MYSQLI_ASSOC)){
        $clasePrioridad = "medium-priority";
        $prioridad = $resultados['prioridad'];

        if ($prioridad == 'Alta') {
            $clasePrioridad = 'high-priority';
        }
        if ($prioridad == 'Media') {
            $clasePrioridad = 'medium-priority';
        }
        if ($prioridad == 'Baja') {
            $clasePrioridad = 'low-priority';
        }
    
        $claseEstado = "amarillo";
        $estado = $resultados['estado'];

        if ($estado == 'Pendiente') {
            $claseEstado = 'amarillo';
        }
        if ($estado == 'En Proceso') {
            $claseEstado = 'azul';
        }
        if ($estado == 'Completado') {
            $claseEstado = 'verde';
        }
        if ($estado == 'Cancelado') {
            $claseEstado = 'rojo';
        }
        if ($estado == 'Aplazado') {
            $claseEstado = 'gris';
        }
        
        $tabla .= " <tr>
                        <td>".$resultados['nombre_tarea']."</td>
                        <td>".$resultados['responsable']."</td>
                        <td>".$resultados['fecha_entrega']."</td>
                        <td>
                            <button class='".$claseEstado."'>
                                ".$resultados['estado']."
                            </button>
                        </td>
                        <td> 
                            <button class='".$clasePrioridad."'>
                                ".$prioridad."
                            </button>
                        </td>
                        <td>
                            <button onclick='editarPedido(".$resultados['id_pedido'].")' class='btn-editar'>
                                ✏️
                            </button>
                            <button onclick='eliminarPedido(".$resultados['id_pedido'].")' class='btn-eliminar'>
                                🗑️
                            </button>
                        </td>
                    </tr>";
    }

} catch (PDOException $e) {
    echo "Error en la conexión: " . $e->getMessage();
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
                    <select id="responsable" name="responsable-id" required>
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
                <h2><?php echo htmlspecialchars($proyecto_actual['nombre']); ?></h2>
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
                
                document.getElementById('id_pedido').value = pedido.id_pedido;
                document.querySelector('input[name="titulo"]').value = pedido.titulo;
                document.querySelector('textarea[name="descripcion"]').value = pedido.descripcion;
                document.querySelector('input[name="fecha-entrega"]').value = pedido.fecha_entrega;
                document.querySelector('#responsable').value = pedido.responsable;
                document.querySelector('#responsable-id').value = pedido.usuario_id;
                document.querySelector('#prioridad').value = pedido.prioridad_id;
                document.querySelector('#estado').value = pedido.estado_id;
                
                document.getElementById('btn-submit').textContent = 'Actualizar';
                document.getElementById('modal-container').classList.add('active');
                
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