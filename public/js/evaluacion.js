/**
 * Gestor de Competencias ERP - Lógica de Interfaz de Evaluación One-to-One
 * Autor: Julián Ramírez (Software Architect)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Enlazar elementos del DOM
    const empleadoSelect = document.getElementById('empleado-select');
    const puestoInput = document.getElementById('puesto-input');
    const evaluadorInput = document.getElementById('evaluador-input');
    const periodoInput = document.getElementById('periodo-input');
    
    const emptyState = document.getElementById('empty-state');
    const competenciasContainer = document.getElementById('competencias-container');
    const footerMetricas = document.getElementById('footer-metricas');
    const badgeAjusteGlobal = document.getElementById('badge-ajuste-global');

    // Variables globales de estado
    let currentEmpleadoId = null;
    let currentPeriodoId = null;
    let competenciasList = [];

    // 2. Escuchar evento de cambio en el selector de Empleado
    empleadoSelect.addEventListener('change', async (e) => {
        const empleadoId = e.target.value;
        if (!empleadoId) {
            resetInterface();
            return;
        }

        currentEmpleadoId = parseInt(empleadoId, 10);
        await cargarFormularioEvaluacion(currentEmpleadoId);
    });

    /**
     * Hace Fetch GET al backend para obtener la configuración y datos del empleado
     */
    async function cargarFormularioEvaluacion(empleadoId) {
        try {
            showLoading(true);

            const response = await fetch(`/api/evaluacion?empleado_id=${empleadoId}`);
            const res = await response.json();

            if (res.status !== 'success') {
                showToast(res.message || 'Error al cargar los datos.', 'danger');
                resetInterface();
                return;
            }

            const { empleado, periodo, perfil, guardado } = res;

            // Guardar periodo ID y competencias globales
            currentPeriodoId = parseInt(periodo.id, 10);
            competenciasList = perfil;

            // Rellenar campos descriptivos del Panel Izquierdo
            puestoInput.value = empleado.puesto_nombre;
            evaluadorInput.value = guardado ? guardado.cabecera.evaluado_por : 'Julián Ramírez (Arquitecto)';
            periodoInput.value = periodo.nombre;

            // Ocultar Empty State y mostrar contenedores principales
            emptyState.classList.add('d-none');
            competenciasContainer.classList.remove('d-none');
            footerMetricas.classList.remove('d-none');

            // Renderizar la lista de competencias dinámicas
            renderCompetencias(perfil, guardado);

        } catch (error) {
            console.error('Error:', error);
            showToast('Fallo al conectar con el servidor para obtener los datos.', 'danger');
            resetInterface();
        } finally {
            showLoading(false);
        }
    }

    /**
     * Dibuja dinámicamente las filas de competencias
     */
    function renderCompetencias(perfil, guardado) {
        competenciasContainer.innerHTML = '';

        // Mapear los detalles guardados previamente por competencia_id para un acceso ultra rápido
        const detallesMap = new Map();
        if (guardado && Array.isArray(guardado.detalles)) {
            guardado.detalles.forEach(d => {
                detallesMap.set(parseInt(d.competencia_id, 10), d);
            });
            // Mostrar el porcentaje global ya guardado
            actualizarBadgeAjusteGlobal(parseFloat(guardado.cabecera.porcentaje_ajuste));
        } else {
            actualizarBadgeAjusteGlobal(0.0);
        }

        perfil.forEach(c => {
            const competenciaId = parseInt(c.competencia_id, 10);
            const detalle = detallesMap.get(competenciaId);

            // Obtener valores precargados o por defecto
            const puntuacionActiva = detalle ? parseInt(detalle.puntuacion, 10) : 0;
            const comentarioActivo = detalle ? (detalle.comentario || '') : '';
            const semaforoActivo = detalle ? (detalle.semaforo || 'PENDIENTE') : 'PENDIENTE';
            const gapActivo = detalle ? parseFloat(detalle.gap) : 0.0;

            // Crear el elemento HTML de la fila de competencia
            const row = document.createElement('div');
            row.className = 'competencia-row';
            row.setAttribute('data-competencia-id', competenciaId);

            // Renderizar la fila con Bootstrap
            row.innerHTML = `
                <div class="row align-items-center g-3">
                    <!-- Detalle Competencia -->
                    <div class="col-md-5">
                        <h6 class="fw-bold m-0 text-slate-800">${escapeHTML(c.nombre)}</h6>
                        <p class="text-muted small m-0 mt-1">${escapeHTML(c.descripcion)}</p>
                        <div class="mt-2">
                            <span class="badge bg-light text-secondary border px-2 py-1 font-monospace small">
                                Ideal: ${parseFloat(c.valor_ideal)}
                            </span>
                            <span class="badge bg-light text-secondary border px-2 py-1 font-monospace small ms-1">
                                Peso: ${parseFloat(c.peso)}%
                            </span>
                            <span class="badge bg-light text-secondary border px-2 py-1 font-monospace small ms-1 id="gap-badge-${competenciaId}">
                                Gap: ${gapActivo > 0 ? '+' : ''}${gapActivo.toFixed(1)}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Selector de Puntuación (Cápsulas) -->
                    <div class="col-md-3 d-flex flex-column align-items-md-center">
                        <span class="small text-muted fw-semibold mb-2 d-md-none">Puntuación:</span>
                        <div class="d-flex align-items-center">
                            ${[1, 2, 3, 4, 5].map(val => `
                                <span class="capsula-btn ${puntuacionActiva === val ? 'active' : ''}" 
                                      data-value="${val}" 
                                      data-competencia-id="${competenciaId}">
                                    ${val}
                                </span>
                            `).join('')}
                        </div>
                    </div>

                    <!-- Semáforo Ovalado -->
                    <div class="col-md-2 text-md-center">
                        <span class="small text-muted fw-semibold mb-1 d-block d-md-none">Semáforo:</span>
                        <span class="semaforo-oval ${obtenerClaseSemaforo(semaforoActivo)}" id="semaforo-${competenciaId}">
                            ${semaforoActivo}
                        </span>
                    </div>

                    <!-- Comentario & Botón Guarda -->
                    <div class="col-md-2">
                        <div class="d-flex flex-column gap-2">
                            <input type="text" 
                                   class="form-control form-control-sm border-slate-300 input-comentario" 
                                   placeholder="Añadir nota..." 
                                   value="${escapeHTML(comentarioActivo)}"
                                   data-competencia-id="${competenciaId}">
                            
                            <button class="btn btn-primary btn-sm w-100 btn-guarda" 
                                    data-competencia-id="${competenciaId}">
                                <i class="bi bi-floppy-fill me-1"></i>Guarda
                            </button>
                        </div>
                    </div>
                </div>
            `;

            competenciasContainer.appendChild(row);
        });

        // Enlazar los listeners de eventos para los selectores de puntuación y botón guarda
        vincularEventosCompetencias();
    }

    /**
     * Vincula los clicks y eventos interactivos en la lista de competencias
     */
    function vincularEventosCompetencias() {
        // Clic en las cápsulas selectoras de números (1 al 5)
        const capsulas = competenciasContainer.querySelectorAll('.capsula-btn');
        capsulas.forEach(capsula => {
            capsula.addEventListener('click', (e) => {
                const compId = parseInt(capsula.getAttribute('data-competencia-id'), 10);
                const valor = parseInt(capsula.getAttribute('data-value'), 10);

                // Remover clase active en la misma fila y asignarla a la clickeada
                const fila = capsula.closest('.competencia-row');
                fila.querySelectorAll('.capsula-btn').forEach(btn => btn.classList.remove('active'));
                capsula.classList.add('active');

                // Disparar guardado/recalculado en vivo automáticamente al calificar
                guardarEvaluacionCompleta(compId);
            });
        });

        // Clic en el botón "Guarda" de cada fila
        const botonesGuarda = competenciasContainer.querySelectorAll('.btn-guarda');
        botonesGuarda.forEach(btn => {
            btn.addEventListener('click', () => {
                const compId = parseInt(btn.getAttribute('data-competencia-id'), 10);
                guardarEvaluacionCompleta(compId);
            });
        });

        // También registrar el evento enter en el input de comentario para guardar
        const comentarios = competenciasContainer.querySelectorAll('.input-comentario');
        comentarios.forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const compId = parseInt(input.getAttribute('data-competencia-id'), 10);
                    guardarEvaluacionCompleta(compId);
                }
            });
        });
    }

    /**
     * Recolecta el estado actual de todas las competencias en el DOM y lo envía al Servidor
     */
    async function guardarEvaluacionCompleta(competenciaIdDisparadora) {
        if (!currentEmpleadoId || !currentPeriodoId) {
            showToast('Empleado o periodo no definido.', 'warning');
            return;
        }

        const puntuaciones = {};
        const comentarios = {};
        let valorValidoEncontrado = false;

        // Recorrer el DOM recolectando las puntuaciones y comentarios
        const filas = competenciasContainer.querySelectorAll('.competencia-row');
        filas.forEach(fila => {
            const compId = parseInt(fila.getAttribute('data-competencia-id'), 10);
            
            // Buscar la cápsula activa
            const capsulaActiva = fila.querySelector('.capsula-btn.active');
            const score = capsulaActiva ? parseInt(capsulaActiva.getAttribute('data-value'), 10) : 0;
            
            // Buscar comentario
            const commentInput = fila.querySelector('.input-comentario');
            const comment = commentInput ? commentInput.value.trim() : '';

            // Solo registramos puntuaciones mayores a 0
            if (score > 0) {
                puntuaciones[compId] = score;
                valorValidoEncontrado = true;
            }
            if (comment !== '') {
                comentarios[compId] = comment;
            }
        });

        if (!valorValidoEncontrado) {
            showToast('Por favor califica al menos una competencia antes de guardar.', 'warning');
            return;
        }

        try {
            // Animación del botón disparador para mostrar que está guardando
            const botonFila = competenciasContainer.querySelector(`.btn-guarda[data-competencia-id="${competenciaIdDisparadora}"]`);
            const htmlOriginal = botonFila ? botonFila.innerHTML : '';
            if (botonFila) {
                botonFila.disabled = true;
                botonFila.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            }

            const payload = {
                empleado_id: currentEmpleadoId,
                periodo_id: currentPeriodoId,
                puntuaciones: puntuaciones,
                comentarios: comentarios,
                evaluado_por: evaluadorInput.value.trim() || 'Julián Ramírez (Arquitecto)'
            };

            const response = await fetch('/api/evaluacion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const res = await response.json();

            // Restaurar botón
            if (botonFila) {
                botonFila.disabled = false;
                botonFila.innerHTML = htmlOriginal;
            }

            if (res.status !== 'success') {
                showToast(res.message || 'Error al guardar la evaluación.', 'danger');
                return;
            }

            // --- ACTUALIZACIÓN DE DOM EN TIEMPO REAL ---
            
            // 1. Actualizar Ajuste Global
            actualizarBadgeAjusteGlobal(parseFloat(res.porcentaje_ajuste));

            // 2. Actualizar semáforos e indicadores individuales devueltos por el backend
            if (Array.isArray(res.evaluaciones)) {
                res.evaluaciones.forEach(ev => {
                    const compId = parseInt(ev.competencia_id, 10);
                    
                    // Actualizar semáforo visual
                    const semaforoSpan = document.getElementById(`semaforo-${compId}`);
                    if (semaforoSpan) {
                        semaforoSpan.textContent = ev.semaforo;
                        semaforoSpan.className = `semaforo-oval ${obtenerClaseSemaforo(ev.semaforo)}`;
                    }

                    // Actualizar el Gap Badge
                    const gapSpan = document.getElementById(`gap-badge-${compId}`);
                    if (gapSpan) {
                        const gapVal = parseFloat(ev.gap);
                        gapSpan.textContent = `Gap: ${gapVal > 0 ? '+' : ''}${gapVal.toFixed(1)}`;
                    }
                });
            }

            showToast('¡Calificación y métricas sincronizadas con éxito, parce!', 'success');

        } catch (error) {
            console.error('Error al guardar:', error);
            showToast('Error de red al guardar la calificación.', 'danger');
        }
    }

    /**
     * Auxiliares visuales
     */
    function resetInterface() {
        currentEmpleadoId = null;
        currentPeriodoId = null;
        competenciasList = [];
        
        puestoInput.value = '';
        evaluadorInput.value = '';
        periodoInput.value = '';
        
        emptyState.classList.remove('d-none');
        competenciasContainer.classList.add('d-none');
        footerMetricas.classList.add('d-none');
    }

    function showLoading(show) {
        if (show) {
            empleadoSelect.disabled = true;
        } else {
            empleadoSelect.disabled = false;
        }
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

    function actualizarBadgeAjusteGlobal(porcentaje) {
        badgeAjusteGlobal.textContent = `${porcentaje.toFixed(1)}%`;
        
        // Estilo de color dinámico del badge de ajuste global
        badgeAjusteGlobal.className = 'badge fs-6 px-3 py-2';
        if (porcentaje >= 85) {
            badgeAjusteGlobal.classList.add('bg-success');
        } else if (porcentaje >= 60) {
            badgeAjusteGlobal.classList.add('bg-warning', 'text-dark');
        } else {
            badgeAjusteGlobal.classList.add('bg-danger');
        }
    }

    /**
     * Muestra una notificación flotante tipo Toast premium en la esquina superior derecha
     */
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        // Iconos según el tipo
        let icon = 'bi-check-circle-fill text-success';
        if (type === 'danger') icon = 'bi-exclamation-triangle-fill text-danger';
        if (type === 'warning') icon = 'bi-exclamation-circle-fill text-warning';

        toast.className = 'toast-premium';
        toast.innerHTML = `
            <i class="bi ${icon} fs-5"></i>
            <span class="fw-medium">${escapeHTML(message)}</span>
        `;
        
        container.appendChild(toast);

        // Auto-eliminar después de 3.5 segundos con animación de salida
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
