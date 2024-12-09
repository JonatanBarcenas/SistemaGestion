<?php
session_start();
    include 'conexion.php';

    try {
        $cnn = conectar();

        $sql = "SELECT 
                    p.titulo AS nombre_tarea,
                    u.nombre AS responsable,
                    DATE_FORMAT(p.fecha_entrega, '%d - %M') AS fecha_entrega,
                    pr.nivel AS prioridad
                FROM pedido p
                INNER JOIN usuario u ON p.usuario_id = u.id_usuario
                INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad";

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
                        </tr>";
        }

    } catch (PDOException $e) {
        echo "Error en la conexión: " . $e->getMessage();
    }

    if(isset($_POST['btn-agregar'])){
        $sql = "INSERT INTO pedido VALUES 
            (null, '".$_POST['titulo']."', '".$_POST['descripcion']."', now(), '".$_POST['fecha-entrega']."', 1, 1, 1, '".$_POST['responsable-id']."')";
        echo $sql;    
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/index.css">
    <title>Gestión de Proyectos</title>
    <script>
        // script.js
        document.addEventListener("DOMContentLoaded", () => {
            const addTaskButton = document.getElementById("add-task");
            const modalContainer = document.getElementById("modal-container");
   
            addTaskButton.addEventListener("click", () => {
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
            let userSelected = {};

            // Función para realizar búsqueda
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

            // Función para mostrar los resultados
            function renderResults(data) {
                resultadosContainer.innerHTML = "";
                data.forEach((item) => {
                    const li = document.createElement("li");
                    li.textContent = item.nombre;
                    li.dataset.id = item.id_usuario;
                    resultadosContainer.appendChild(li);

                    // Manejar clic en un resultado
                    li.addEventListener("click", () => {
                        inputResponsable.value = item.nombre;
                        responsableIdField.value = item.id_usuario;

                        // Guardar datos en el objeto
                        userSelected = {
                            id: item.id_usuario,
                            nombre: item.nombre,
                        };

                        resultadosContainer.innerHTML = "";
                        console.log("Usuario seleccionado:", userSelected);
                    });
                });
            }
        });

    </script>
</head>
<body>
<div class="modal-container" id="modal-container">
        <div class="modal">
            <form method="post">
                <input type="text" name="titulo" placeholder="INGRESE EL NOMBRE" class="modal-title">
                <div class="form-group">
                    <label for="responsable"><span class="icon">👤</span> Responsable</label>
                    <input type="text" id="responsable" placeholder="Ingrese un nombre" autocomplete="off">
                    <input type="hidden" id="responsable-id" name='responsable-id'> <!-- ID del responsable seleccionado -->
                    <ul id="resultados" class="autocomplete-results"></ul>
                </div>

                <div class="form-group">
                    <label for="fecha"><span class="icon">📅</span> Fecha de entrega</label>
                    <input type="date" id="fecha" name='fecha-entrega'>
                </div>
                <div class="form-group">
                    <label for="proyecto"><span class="icon">📁</span> Proyecto</label>
                    <div class="project-info">
                        <span class="project-color"></span>
                        <span class="project-name">Proyecto 1</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" rows="4" name="descripcion"></textarea>
                </div>
                <button type="submit" class="btn-agregar" name="btn-agregar">Agregar</button>
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
                <div class="user-icon">👤 <?php $_SESSION['usuario_nombre'] ?></div>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $tabla; ?>
                        
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>

