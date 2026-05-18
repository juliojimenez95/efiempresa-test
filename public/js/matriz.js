/**
 * Gestor de Competencias ERP - Lógica de Interfaz de la Matriz Resumen de Equipo
 * Autor: Julián Ramírez (Software Architect)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Enlazar elementos del DOM
    const periodoSelect = document.getElementById('periodo-select');
    const areaSelect = document.getElementById('area-select');
    const btnExportar = document.getElementById('btn-exportar');

    // Tabla e Hilos
    const tablaCabecera = document.getElementById('matriz-tabla-cabecera');
    const tablaCuerpo = document.getElementById('matriz-tabla-cuerpo');
    const emptyState = document.getElementById('matriz-empty-state');

    // Indicadores del Dashboard
    const statColaboradores = document.getElementById('stat-colaboradores');
    const statEvaluados = document.getElementById('stat-evaluados');
    const statPromedio = document.getElementById('stat-promedio');
    const statCriticos = document.getElementById('stat-criticos');

    // 2. Escuchar cambios de filtros
    periodoSelect.addEventListener('change', cargarMatrizResumen);
    areaSelect.addEventListener('change', cargarMatrizResumen);

    // Cargar matriz inicialmente
    cargarMatrizResumen();

    /**
     * Recupera y renderiza el mapa de calor de la matriz (GET /api/matriz)
     */
    async function cargarMatrizResumen() {
        const periodoId = periodoSelect.value;
        const areaId = areaSelect.value;

        if (!periodoId) {
            mostrarCargando(false);
            return;
        }

        try {
            mostrarCargando(true);
            emptyState.classList.add('d-none');

            // Fetch GET unificado
            let url = `/api/matriz?periodo_id=${periodoId}`;
            if (areaId) {
                url += `&area_id=${areaId}`;
            }

            const response = await fetch(url);
            const res = await response.json();

            if (res.status !== 'success') {
                showToast(res.message || 'Error al obtener la matriz.', 'danger');
                limpiarMatriz();
                return;
            }

            const { competencias, matriz, detalles } = res;

            if (matriz.length === 0) {
                limpiarMatriz();
                emptyState.classList.remove('d-none');
                return;
            }

            // 3. Procesar e inyectar datos en la matriz
            renderMatrizCruzada(competencias, matriz, detalles);
            
            // 4. Calcular y refrescar estadísticas del Dashboard corporativo
            calcularDashboardMetricas(matriz);

        } catch (error) {
            console.error('Error al cargar la matriz:', error);
            showToast('Error de conexión con el servidor de la matriz.', 'danger');
            limpiarMatriz();
        } finally {
            mostrarCargando(false);
        }
    }

    /**
     * Dibuja dinámicamente las cabeceras (competencias) y filas (empleados + intersecciones)
     */
    function renderMatrizCruzada(competencias, empleados, detalles) {
        // A. Renderizar cabecera de la tabla
        tablaCabecera.innerHTML = '';
        const headerRow = document.createElement('tr');
        
        // Cabecera Fija 1: Datos de Colaborador
        const colEmp = document.createElement('th');
        colEmp.className = 'ps-3';
        colEmp.style.minWidth = '220px';
        colEmp.textContent = 'Colaborador';
        headerRow.appendChild(colEmp);

        // Cabecera Fija 2: Porcentaje de Ajuste Ponderado Global
        const colAjuste = document.createElement('th');
        colAjuste.className = 'text-center';
        colAjuste.style.minWidth = '110px';
        colAjuste.textContent = '% Ajuste';
        headerRow.appendChild(colAjuste);

        // Cabeceras Dinámicas: Cada competencia activa
        competencias.forEach(comp => {
            const colComp = document.createElement('th');
            colComp.className = 'col-competencia-header';
            colComp.innerHTML = `
                <span class="block-badge" title="${escapeHTML(comp.bloque_nombre)}">
                    ${escapeHTML(comp.bloque_nombre.substring(0, 10))}...
                </span>
                <span class="fw-bold text-slate-800 d-block small" title="${escapeHTML(comp.nombre)}">
                    ${escapeHTML(comp.nombre)}
                </span>
            `;
            headerRow.appendChild(colComp);
        });

        tablaCabecera.appendChild(headerRow);

        // B. Crear mapa de lookup O(1) para buscar calificaciones instantáneamente
        const scoresMap = {};
        detalles.forEach(d => {
            const empId = parseInt(d.empleado_id, 10);
            const compId = parseInt(d.competencia_id, 10);
            
            if (!scoresMap[empId]) {
                scoresMap[empId] = {};
            }
            scoresMap[empId][compId] = {
                puntuacion: parseFloat(d.puntuacion),
                semaforo: d.semaforo
            };
        });

        // C. Renderizar filas de empleados
        tablaCuerpo.innerHTML = '';
        empleados.forEach(emp => {
            const row = document.createElement('tr');
            
            // Celda 1: Información de perfil del colaborador
            const cellInfo = document.createElement('td');
            cellInfo.className = 'ps-3';
            cellInfo.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <div>
                        <span class="fw-bold text-slate-800 d-block">${escapeHTML(emp.apellidos)}, ${escapeHTML(emp.nombre)}</span>
                        <span class="text-muted small d-block font-sans-serif" style="font-size: 0.725rem;">
                            ${escapeHTML(emp.puesto_nombre)} | <span class="text-indigo">${escapeHTML(emp.area_nombre)}</span>
                        </span>
                    </div>
                </div>
            `;
            row.appendChild(cellInfo);

            // Celda 2: Porcentaje de ajuste con badge semáforo global
            const cellGlobal = document.createElement('td');
            cellGlobal.className = 'text-center';
            if (emp.porcentaje_ajuste !== null) {
                const globalPct = parseFloat(emp.porcentaje_ajuste);
                cellGlobal.innerHTML = `
                    <span class="badge ${obtenerBadgeAjusteClase(globalPct)} fw-bold px-3 py-2 font-monospace fs-7">
                        ${globalPct.toFixed(1)}%
                    </span>
                `;
            } else {
                cellGlobal.innerHTML = `
                    <span class="badge bg-light text-muted fw-normal px-2 py-1 fs-8 border text-uppercase">
                        Pendiente
                    </span>
                `;
            }
            row.appendChild(cellGlobal);

            // Celdas Dinámicas: Calificaciones individuales (Intersecciones)
            competencias.forEach(comp => {
                const cellVal = document.createElement('td');
                cellVal.className = 'cell-calificacion';

                const lookup = scoresMap[emp.empleado_id] ? scoresMap[emp.empleado_id][comp.id] : null;

                if (lookup) {
                    const val = lookup.puntuacion;
                    const sem = lookup.semaforo || 'PENDIENTE';
                    cellVal.innerHTML = `
                        <span class="matriz-badge-score ${obtenerSemaforoClase(sem)}" title="Calificación: ${val.toFixed(1)} en ${escapeHTML(comp.nombre)}">
                            ${val.toFixed(0)}
                        </span>
                    `;
                } else {
                    cellVal.innerHTML = `
                        <span class="matriz-badge-score matriz-score--pendiente" title="Sin evaluar en ${escapeHTML(comp.nombre)}">
                            -
                        </span>
                    `;
                }

                row.appendChild(cellVal);
            });

            tablaCuerpo.appendChild(row);
        });
    }

    /**
     * Calcula los contadores y ratios globales para el Dashboard
     */
    function calcularDashboardMetricas(empleados) {
        const total = empleados.length;
        const evaluadosList = empleados.filter(e => e.porcentaje_ajuste !== null);
        const evaluadosCount = evaluadosList.length;
        
        let promedio = 0.0;
        let criticosCount = 0;

        if (evaluadosCount > 0) {
            const sum = evaluadosList.reduce((acc, curr) => acc + parseFloat(curr.porcentaje_ajuste), 0);
            promedio = sum / evaluadosCount;
            criticosCount = evaluadosList.filter(e => parseFloat(e.porcentaje_ajuste) < 60.0).length;
        }

        // Refrescar DOM
        statColaboradores.textContent = total;
        statEvaluados.textContent = `${evaluadosCount} / ${total}`;
        statPromedio.textContent = `${promedio.toFixed(1)}%`;
        statCriticos.textContent = criticosCount;
        
        // Colores dinámicos en stats
        actualizarColoresStats(promedio, criticosCount);
    }

    /**
     * Auxiliares visuales
     */
    function limpiarMatriz() {
        tablaCabecera.innerHTML = '';
        tablaCuerpo.innerHTML = '';
        statColaboradores.textContent = '0';
        statEvaluados.textContent = '0';
        statPromedio.textContent = '0.0%';
        statCriticos.textContent = '0';
    }

    function mostrarCargando(show) {
        periodoSelect.disabled = show;
        areaSelect.disabled = show;
    }

    function obtenerSemaforoClase(semaforo) {
        switch (semaforo.toUpperCase()) {
            case 'VERDE':
                return 'semaforo--verde';
            case 'AMARILLO':
                return 'semaforo--amarillo';
            case 'ROJO':
                return 'semaforo--rojo';
            default:
                return 'bg-secondary text-white';
        }
    }

    function obtenerBadgeAjusteClase(porcentaje) {
        if (porcentaje >= 85) return 'bg-success text-white';
        if (porcentaje >= 60) return 'bg-warning text-dark';
        return 'bg-danger text-white';
    }

    function actualizarColoresStats(promedio, criticos) {
        // Cambiar color de Ajuste Promedio
        statPromedio.className = 'fw-bold mt-1';
        if (promedio >= 85) {
            statPromedio.classList.add('text-success');
        } else if (promedio >= 60) {
            statPromedio.classList.add('text-warning');
        } else {
            statPromedio.classList.add('text-danger');
        }

        // Alerta en Críticos
        statCriticos.className = 'fw-bold mt-1';
        if (criticos > 0) {
            statCriticos.classList.add('text-danger');
        } else {
            statCriticos.classList.add('text-muted');
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        let icon = 'bi-check-circle-fill text-success';
        if (type === 'danger') icon = 'bi-exclamation-triangle-fill text-danger';
        if (type === 'warning') icon = 'bi-exclamation-circle-fill text-warning';

        toast.className = 'toast-premium';
        toast.innerHTML = `
            <i class="bi ${icon} fs-5"></i>
            <span class="fw-medium">${escapeHTML(message)}</span>
        `;
        
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) reverse forwards';
            toast.addEventListener('animationend', () => {
                toast.remove();
            });
        }, 3500);
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }
});
