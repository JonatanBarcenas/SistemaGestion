<?php
require_once 'config/session_config.php';
checkLogin();

include 'conexion.php';

try {
    $cnn = conectar();

    $sql = "SELECT 
            p.id_pedido,
            p.titulo AS nombre_tarea,
            u.nombre AS responsable,
            DATE_FORMAT(p.fecha_entrega, '%d - %M') AS fecha_entrega,
            pr.nivel AS prioridad,
            p.usuario_id,
            p.estado_id,
            p.prioridad_id,
            p.cliente_id
        FROM pedido p
        INNER JOIN usuario u ON p.usuario_id = u.id_usuario
        INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
        WHERE 1";

    $consult = $cnn->query($sql);
    
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
        $tabla .= " <tr>
                        <td>".$resultados['nombre_tarea']."</td>
                        <td>".$resultados['responsable']."</td>
                        <td>".$resultados['fecha_entrega']."</td>
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
    <div class="modal-container" id="modal-container">
        <div class="modal">
            <form method="post" id="pedidoForm">
                <input type="hidden" name="id_pedido" id="id_pedido">
                <input type="text" name="titulo" placeholder="INGRESE EL NOMBRE" class="modal-title" required>
                <div class="form-group">
                    <label for="responsable"><span class="icon">👤</span> Responsable</label>
                    <input type="text" id="responsable" placeholder="Ingrese un nombre" autocomplete="off" required>
                    <input type="hidden" id="responsable-id" name='responsable-id' required>
                    <ul id="resultados" class="autocomplete-results"></ul>
                </div>

                <div class="form-group">
                    <label for="fecha"><span class="icon">📅</span> Fecha de entrega</label>
                    <input type="date" id="fecha" name='fecha-entrega' required>
                </div>
                
                <div class="form-group">
                    <label for="prioridad"><span class="icon">🔥</span> Prioridad</label>
                    <select name="prioridad" id="prioridad" required>
                        <option value="1">Baja</option>
                        <option value="2">Media</option>
                        <option value="3">Alta</option>
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

        <!-- Main content -->
        <main class="main-content">
            <header class="header">
                <input type="text" placeholder="Buscar" class="search-bar">
                <div class="user-icon">👤 <?php echo isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario'; ?></div>
            </header>
            <section class="project-section">
                <h2>PROYECTO 1</h2>
                <button id="add-task" class="add-task">Agregar Tarea +</button>
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>Nombre de la tarea</th>
                            <th>Responsable</th>
                            <th>Fecha de entrega</th>
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
    });

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