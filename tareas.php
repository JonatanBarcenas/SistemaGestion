<?php
    session_start(); 

    if (!isset($_SESSION['usuario_id'])) {
        header("Location:login.php");
        exit();
    }

    $usuario_id = $_SESSION['usuario_id']; 

    include 'conexion.php';

    try {
        $cnn = conectar();

        // Obtener tareas del usuario
        $sql = "SELECT p.titulo AS nombre_tarea, p.id_pedido, pr.nivel AS prioridad
                FROM pedido p
                INNER JOIN usuario u ON p.usuario_id = u.id_usuario
                INNER JOIN prioridad pr ON p.prioridad_id = pr.id_prioridad
                WHERE p.usuario_id = ?";
        
        $stmt = $cnn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $tareas = [];
        while ($row = $result->fetch_assoc()) {
            $tareas[] = $row;
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
    <title>Interfaz de Usuario</title>
    <link rel="stylesheet" href="css/tareas.css">
</head>
<body>
    <div class="container">
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
        

        <main class="main-content">
            <header class="header">
                <input type="text" placeholder="Buscar" class="search-bar">
                <div class="user-icon">👤 <?php $_SESSION['usuario_nombre'] ?></div>
            </header>
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
        </main>
    </div>

    <script>
        // Obtener la lista de tareas
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
    </script>
</body>

</html>