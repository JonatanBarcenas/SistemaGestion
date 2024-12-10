document.addEventListener('DOMContentLoaded', function() {
    // Manejo de pestañas
    const tabs = document.querySelectorAll('.tab-button');
    const panes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remover active de todas las pestañas
            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));

            // Activar la pestaña seleccionada
            tab.classList.add('active');
            const pane = document.getElementById(tab.dataset.tab);
            if (pane) pane.classList.add('active');
        });
    });

    // Variables globales para los gráficos
    let completacionChart, estadosChart, tendenciasChart;

    function initializeCharts() {
        // Limpiar gráficos existentes si los hay
        if (completacionChart) completacionChart.destroy();
        if (estadosChart) estadosChart.destroy();
        if (tendenciasChart) tendenciasChart.destroy();

        // Gráfico de completación
        const completacionCtx = document.getElementById('completacionChart')?.getContext('2d');
        if (completacionCtx) {
            completacionChart = new Chart(completacionCtx, {
                type: 'line',
                data: {
                    labels: dashboardData.completados_periodo.map(d => d.periodo),
                    datasets: [{
                        label: 'Total Completados',
                        data: dashboardData.completados_periodo.map(d => d.total_completados),
                        borderColor: '#3498db',
                        fill: false
                    }, {
                        label: 'Completados a Tiempo',
                        data: dashboardData.completados_periodo.map(d => d.completados_tiempo),
                        borderColor: '#2ecc71',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#fff'
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                color: '#fff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#fff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de estados
        const estadosCtx = document.getElementById('estadosChart')?.getContext('2d');
        if (estadosCtx) {
            estadosChart = new Chart(estadosCtx, {
                type: 'pie',
                data: {
                    labels: dashboardData.por_estado.map(d => d.estado),
                    datasets: [{
                        data: dashboardData.por_estado.map(d => d.cantidad),
                        backgroundColor: [
                            '#FFD700', // Amarillo para Pendiente
                            '#007BFF', // Azul para En Proceso
                            '#28a745', // Verde para Completado
                            '#dc3545', // Rojo para Cancelado
                            '#6c757d'  // Gris para Aplazado
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#fff'
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de tendencias
        const tendenciasCtx = document.getElementById('tendenciasChart')?.getContext('2d');
        if (tendenciasCtx) {
            tendenciasChart = new Chart(tendenciasCtx, {
                type: 'line',
                data: {
                    labels: dashboardData.tendencias.map(d => d.mes),
                    datasets: [{
                        label: 'Nuevos Pedidos',
                        data: dashboardData.tendencias.map(d => d.nuevos_pedidos),
                        borderColor: '#3498db',
                        fill: false
                    }, {
                        label: 'Retrasados',
                        data: dashboardData.tendencias.map(d => d.retrasados),
                        borderColor: '#e74c3c',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#fff'
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                color: '#fff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#fff'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
        }
    }

    // Actualizar historial de cliente
    function updateHistorialCliente() {
        const cliente = document.getElementById('clienteSelect').value;
        const historial = dashboardData.historial_cliente.filter(
            pedido => pedido.cliente === cliente
        );

        const container = document.getElementById('historialPedidos');
        container.innerHTML = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Fecha Creación</th>
                        <th>Fecha Entrega</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                    </tr>
                </thead>
                <tbody>
                    ${historial.map(pedido => `
                        <tr>
                            <td>${pedido.titulo}</td>
                            <td>${new Date(pedido.fecha_creacion).toLocaleDateString()}</td>
                            <td>${new Date(pedido.fecha_entrega).toLocaleDateString()}</td>
                            <td>${pedido.estado}</td>
                            <td>${pedido.prioridad}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    // Actualizar datos del dashboard
    async function updateDashboardData() {
        const periodo = document.getElementById('periodoSelect').value;
        try {
            const response = await fetch(`get_dashboard_data.php?periodo=${periodo}`);
            const data = await response.json();
            dashboardData = data;
            initializeCharts();
            updateHistorialCliente();
        } catch (error) {
            console.error('Error actualizando datos:', error);
        }
    }

    // Exportar informe
    async function exportarInforme(tipo) {
        try {
            const response = await fetch(`exportar_informe.php?tipo=${tipo}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    periodo: document.getElementById('periodoSelect').value,
                    datos: dashboardData
                })
            });

            if (tipo === 'excel') {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `informe_${new Date().toISOString().split('T')[0]}.xlsx`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                // Manejar PDF
                window.open(URL.createObjectURL(await response.blob()));
            }
        } catch (error) {
            console.error('Error exportando informe:', error);
            alert('Error al exportar el informe');
        }
    }

    // Inicializar los gráficos al cargar
    initializeCharts();

    // Event listeners para cambios
    const clienteSelect = document.getElementById('clienteSelect');
    if (clienteSelect) {
        clienteSelect.addEventListener('change', updateHistorialCliente);
        // Inicializar la tabla de historial
        updateHistorialCliente();
    }

    const periodoSelect = document.getElementById('periodoSelect');
    if (periodoSelect) {
        periodoSelect.addEventListener('change', updateDashboardData);
    }

    // Hacer accesibles las funciones de exportación globalmente
    window.exportarInforme = exportarInforme;
});