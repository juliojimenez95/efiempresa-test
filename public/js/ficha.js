/**
 * Gestor de Competencias ERP - Lógica de Interfaz de Ficha Individual e Historial
 * Autor: Julián Ramírez (Software Architect)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Enlazar elementos del DOM
    const empleadoSelect = document.getElementById('empleado-select');
    const periodoSelect = document.getElementById('periodo-select');
    
    // Contenedores del panel izquierdo
    const fichaPerfilContainer = document.getElementById('ficha-perfil-container');
    const fichaHistoricoCard = document.getElementById('ficha-historico-card');
    const timelineContainer = document.getElementById('timeline-container');
    
    // Perfil del colaborador
    const perfilNombre = document.getElementById('perfil-nombre');
    const perfilPuesto = document.getElementById('perfil-puesto');
    const perfilEmail = document.getElementById('perfil-email');
    const perfilArea = document.getElementById('perfil-area');
    const avatarIniciales = document.getElementById('avatar-iniciales');

    // Contenedores del panel derecho
    const periodoFiltroCard = document.getElementById('periodo-filtro-card');
    const fichaEmptyState = document.getElementById('ficha-empty-state');
    const fichaAnalisisContainer = document.getElementById('ficha-analisis-container');
    
    // Cabecera de evaluación
    const evaluacionPeriodoTitulo = document.getElementById('evaluacion-periodo-titulo');
    const evaluacionEvaluador = document.getElementById('evaluacion-evaluador');
    const evaluacionAjusteGlobal = document.getElementById('evaluacion-ajuste-global');
    const tablaCuerpoFicha = document.getElementById('tabla-cuerpo-ficha');

    // Variables globales de estado
    let currentEmpleadoId = null;
    let currentPeriodoId = null;
    let historialEvaluaciones = [];

    // 2. Escuchar cambio de empleado
    empleadoSelect.addEventListener('change', async (e) => {
        const empleadoId = e.target.value;
        if (!empleadoId) {
            resetInterface();
            return;
        }

        currentEmpleadoId = parseInt(empleadoId, 10);
        await cargarFichaTalento(currentEmpleadoId);
    });

    // 3. Escuchar cambio del periodo desde el dropdown superior
    periodoSelect.addEventListener('change', async (e) => {
        const periodoId = e.target.value;
        if (!periodoId) return;

        currentPeriodoId = parseInt(periodoId, 10);
        
        // Sincronizar clase activa en la timeline visual
        sincronizarTimelineActiva(currentPeriodoId);
        
        await cargarDetalleEvaluacionPeriodo(currentEmpleadoId, currentPeriodoId);
    });

    /**
     * Carga el perfil del colaborador y su historial de evaluaciones (GET /api/ficha)
     */
    async function cargarFichaTalento(empleadoId) {
        try {
            showLoading(true);

            const response = await fetch(`/api/ficha?empleado_id=${empleadoId}`);
            const res = await response.json();

            if (res.status !== 'success') {
                showToast(res.message || 'Error al cargar la ficha del empleado.', 'danger');
                resetInterface();
                return;
            }

            const { empleado, historial } = res;
            historialEvaluaciones = historial || [];

            // Rellenar Ficha del Colaborador
            perfilNombre.textContent = `${empleado.nombre} ${empleado.apellidos}`;
            perfilPuesto.textContent = empleado.puesto_nombre;
            perfilEmail.textContent = empleado.email;
            perfilArea.textContent = empleado.area_nombre;
            
            // Iniciales del Avatar
            const iniciales = `${empleado.nombre.charAt(0)}${empleado.apellidos.charAt(0)}`.toUpperCase();
            avatarIniciales.textContent = iniciales;

            // Mostrar el contenedor de Ficha Perfil
            fichaPerfilContainer.classList.remove('d-none');
            fichaHistoricoCard.classList.remove('d-none');

            // Renderizar Historial en Timeline y rellenar dropdown de periodo
            if (historialEvaluaciones.length === 0) {
                renderTimelineVacio();
                periodoFiltroCard.classList.add('d-none');
                fichaAnalisisContainer.classList.add('d-none');
                fichaEmptyState.classList.remove('d-none');
                
                showToast('Este colaborador aún no cuenta con evaluaciones registradas.', 'warning');
            } else {
                fichaEmptyState.classList.add('d-none');
                periodoFiltroCard.classList.remove('d-none');
                fichaAnalisisContainer.classList.remove('d-none');

                renderTimeline(historialEvaluaciones);
                poblarDropdownPeriodos(historialEvaluaciones);

                // Por defecto cargar el último periodo evaluado (primero del historial ordenado desc)
                const ultimoPeriodoId = parseInt(historialEvaluaciones[0].periodo_id, 10);
                currentPeriodoId = ultimoPeriodoId;
                periodoSelect.value = ultimoPeriodoId;
                
                sincronizarTimelineActiva(ultimoPeriodoId);
                await cargarDetalleEvaluacionPeriodo(empleadoId, ultimoPeriodoId);
            }

        } catch (error) {
            console.error('Error:', error);
            showToast('Error al conectar con el servidor para obtener la ficha.', 'danger');
            resetInterface();
        } finally {
            showLoading(false);
        }
    }

    /**
     * Realiza la llamada Fetch GET para cargar la tabla comparativa del periodo seleccionado
     */
    async function cargarDetalleEvaluacionPeriodo(empleadoId, periodoId) {
        try {
            tablaCuerpoFicha.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                        <span class="ms-2 small text-muted">Cargando métricas de brechas...</span>
                    </td>
                </tr>
            `;

            const response = await fetch(`/api/ficha?empleado_id=${empleadoId}&periodo_id=${periodoId}`);
            const res = await response.json();

            if (res.status !== 'success') {
                showToast('Error al cargar la comparación ideal vs real.', 'danger');
                return;
            }

            const { detalle } = res;
            if (!detalle) {
                tablaCuerpoFicha.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No se encontraron detalles de evaluación para este periodo.
                        </td>
                    </tr>
                `;
                return;
            }

            // Rellenar cabecera detallada
            const perInfo = historialEvaluaciones.find(h => parseInt(h.periodo_id, 10) === periodoId);
            evaluacionPeriodoTitulo.textContent = `Periodo: ${perInfo ? perInfo.periodo_nombre : 'Q-Periodo'}`;
            evaluacionEvaluador.textContent = detalle.cabecera.evaluado_por || 'Julián Ramírez (Auditor)';
            
            const ajusteGlobal = parseFloat(detalle.cabecera.porcentaje_ajuste);
            actualizarBadgeAjusteGlobal(ajusteGlobal);

            // Renderizar filas de la tabla de competencias comparativa (7 columnas)
            renderTablaCompetencias(detalle.detalles);

        } catch (error) {
            console.error('Error al cargar detalle:', error);
            showToast('Error de red al cargar el detalle competencial.', 'danger');
        }
    }

    /**
     * Dibuja las filas dinámicas de competencias con barra de progreso de ajuste individual
     */
    function renderTablaCompetencias(detalles) {
        tablaCuerpoFicha.innerHTML = '';

        detalles.forEach(d => {
            const peso = parseFloat(d.peso);
            const ideal = parseFloat(d.valor_ideal);
            const real = parseFloat(d.puntuacion);
            const gap = parseFloat(d.gap);
            const semaforo = d.semaforo || 'PENDIENTE';

            // Cálculo del % de Ajuste Individual aplicando capping estricto
            const ratio = ideal > 0 ? Math.min(real / ideal, 1.0) : 0.0;
            const pctAjuste = ratio * 100;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="ps-3">
                    <span class="fw-semibold text-slate-800 d-block">${escapeHTML(d.competencia_nombre)}</span>
                    <span class="text-muted small d-block font-sans-serif" style="font-size: 0.775rem;">
                        ${escapeHTML(d.bloque_nombre)}
                    </span>
                </td>
                <td class="text-center font-monospace">${peso.toFixed(1)}%</td>
                <td class="text-center font-monospace fw-medium text-secondary">${ideal.toFixed(1)}</td>
                <td class="text-center font-monospace fw-bold text-dark">${real.toFixed(1)}</td>
                <td class="text-center font-monospace fw-semibold ${gap < 0 ? 'text-danger' : 'text-success'}">
                    ${gap > 0 ? '+' : ''}${gap.toFixed(1)}
                </td>
                <td class="text-center">
                    <span class="semaforo-oval ${obtenerClaseSemaforo(semaforo)}">${semaforo}</span>
                </td>
                <td class="pe-3">
                    <div class="d-flex align-items-center gap-2 justify-content-center">
                        <span class="fw-bold text-slate-700 font-monospace" style="font-size: 0.875rem;">
                            ${pctAjuste.toFixed(1)}%
                        </span>
                        <div class="progress-bar-premium flex-grow-1 d-none d-md-block" style="max-width: 60px;">
                            <div class="progress-bar-fill ${obtenerProgresoBg(ratio)}" style="width: ${pctAjuste}%"></div>
                        </div>
                    </div>
                </td>
            `;

            tablaCuerpoFicha.appendChild(row);
        });
    }

    /**
     * Dibuja la línea de tiempo en el contenedor izquierdo
     */
    function renderTimeline(historial) {
        timelineContainer.innerHTML = '';

        historial.forEach(h => {
            const periodoId = parseInt(h.periodo_id, 10);
            const item = document.createElement('div');
            item.className = 'timeline-item';
            item.setAttribute('data-periodo-id', periodoId);

            item.innerHTML = `
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-slate-800">${escapeHTML(h.periodo_nombre)}</strong>
                        <span class="badge ${obtenerBadgeAjusteClase(parseFloat(h.porcentaje_ajuste))} font-monospace">
                            ${parseFloat(h.porcentaje_ajuste).toFixed(1)}%
                        </span>
                    </div>
                    <span class="text-muted small d-block">Cargo: ${escapeHTML(h.puesto_nombre)}</span>
                    <span class="text-muted small d-block" style="font-size: 0.75rem;">
                        Evaluado: ${new Date(h.created_at).toLocaleDateString('es-ES')}
                    </span>
                </div>
            `;

            // Escuchar clic en cada elemento de la línea de tiempo para cambiar de periodo
            item.querySelector('.timeline-content').addEventListener('click', () => {
                currentPeriodoId = periodoId;
                periodoSelect.value = periodoId;
                sincronizarTimelineActiva(periodoId);
                cargarDetalleEvaluacionPeriodo(currentEmpleadoId, periodoId);
            });

            timelineContainer.appendChild(item);
        });
    }

    function renderTimelineVacio() {
        timelineContainer.innerHTML = `
            <div class="text-center py-4 bg-light rounded border border-dashed">
                <i class="bi bi-info-circle text-muted fs-4 d-block mb-2"></i>
                <span class="text-muted small">Sin registros de evaluación</span>
            </div>
        `;
    }

    /**
     * Rellena las opciones del selector de periodos
     */
    function poblarDropdownPeriodos(historial) {
        periodoSelect.innerHTML = '';
        historial.forEach(h => {
            const opt = document.createElement('option');
            opt.value = h.periodo_id;
            opt.textContent = h.periodo_nombre;
            periodoSelect.appendChild(opt);
        });
    }

    /**
     * Remueve y agrega la clase active al timeline-item correspondiente
     */
    function sincronizarTimelineActiva(periodoId) {
        const items = timelineContainer.querySelectorAll('.timeline-item');
        items.forEach(item => {
            const id = parseInt(item.getAttribute('data-periodo-id'), 10);
            if (id === periodoId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    /**
     * Auxiliares visuales
     */
    function resetInterface() {
        currentEmpleadoId = null;
        currentPeriodoId = null;
        historialEvaluaciones = [];
        
        fichaPerfilContainer.classList.add('d-none');
        fichaHistoricoCard.classList.add('d-none');
        periodoFiltroCard.classList.add('d-none');
        fichaAnalisisContainer.classList.add('d-none');
        fichaEmptyState.classList.remove('d-none');
    }

    function showLoading(show) {
        empleadoSelect.disabled = show;
    }

    function obtenerClaseSemaforo(semaforo) {
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

    function obtenerProgresoBg(ratio) {
        if (ratio >= 0.85) return 'bg-success';
        if (ratio >= 0.60) return 'bg-warning';
        return 'bg-danger';
    }

    function obtenerBadgeAjusteClase(porcentaje) {
        if (porcentaje >= 85) return 'bg-success-subtle text-success';
        if (porcentaje >= 60) return 'bg-warning-subtle text-warning';
        return 'bg-danger-subtle text-danger';
    }

    function actualizarBadgeAjusteGlobal(porcentaje) {
        evaluacionAjusteGlobal.textContent = `${porcentaje.toFixed(1)}%`;
        evaluacionAjusteGlobal.className = 'badge fs-5 px-3 py-2';
        if (porcentaje >= 85) {
            evaluacionAjusteGlobal.classList.add('bg-success');
        } else if (porcentaje >= 60) {
            evaluacionAjusteGlobal.classList.add('bg-warning', 'text-dark');
        } else {
            evaluacionAjusteGlobal.classList.add('bg-danger');
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
