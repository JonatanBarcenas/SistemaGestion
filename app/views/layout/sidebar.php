<div class="sidebar">
    <ul class="menu">
        <li onclick="location.href='../tareas/tareas.php'" class="<?php echo $currentPage == 'tareas' ? 'active' : ''; ?>">
            Notificaciones
        </li>
        <li onclick="location.href='../informes/informes.php'" class="<?php echo $currentPage == 'informes' ? 'active' : ''; ?>">
            Informes
        </li>
    </ul>
    <div class="projects">
        <div class="alineacion">
            <h3>Proyectos</h3>
            <button class="add-project-btn" onclick="mostrarModalProyecto()">+</button>
        </div>
        <div class="projects-list">
            <?php foreach($proyectos as $proyecto): ?>
                <div class="project <?php echo $proyecto['id_proyecto'] == ($proyecto_id ?? null) ? 'active' : ''; ?>" 
                     onclick="cambiarProyecto(<?php echo $proyecto['id_proyecto']; ?>)">
                    <div class="dot" style="background-color: <?php echo $proyecto['estado_color']; ?>"></div>
                    <span><?php echo htmlspecialchars($proyecto['nombre']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>