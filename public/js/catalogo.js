/**
 * Gestor de Competencias ERP - Lógica de Interfaz de Catálogos y Mantenimiento
 * Autor: Julián Ramírez (Software Architect)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Inicializar Modales Bootstrap
    const compModalElement = document.getElementById('modal-competencia');
    const perModalElement = document.getElementById('modal-periodo');
    
    const compModal = new bootstrap.Modal(compModalElement);
    const perModal = new bootstrap.Modal(perModalElement);

    // 2. Enlazar Formularios e Inputs
    const formCompetencia = document.getElementById('form-competencia');
    const formPeriodo = document.getElementById('form-periodo');

    // Botones de Apertura
    const btnNuevaCompetencia = document.getElementById('btn-nueva-competencia');
    const btnNuevoPeriodo = document.getElementById('btn-nuevo-periodo');

    // Cuerpos de Tabla
    const cuerpoTablaCompetencias = document.getElementById('cuerpo-tabla-competencias');
    const cuerpoTablaPeriodos = document.getElementById('cuerpo-tabla-periodos');

    // Variables de Estado Local
    let listCompetencias = [];
    let listBloques = [];
    let listPeriodos = [];
    let listEscalas = [];

    // Cargar catálogos maestros inicialmente de la API para tener referencias completas
    inicializarCatalogos();

    // 3. Escuchar Eventos de Modales
    btnNuevaCompetencia.addEventListener('click', () => {
        formCompetencia.reset();
        document.getElementById('comp-id').value = '0';
        document.getElementById('comp-activo').value = '1';
        
        document.getElementById('modalCompetenciaLabel').textContent = 'Crear Nueva Competencia';
        compModal.show();
    });

    btnNuevoPeriodo.addEventListener('click', () => {
        formPeriodo.reset();
        document.getElementById('periodo-id').value = '0';
        document.getElementById('periodo-activo').value = '1';
        document.getElementById('periodo-cerrado').value = '0';

        document.getElementById('modalPeriodoLabel').textContent = 'Crear Nuevo Periodo';
        perModal.show();
    });

    // 4. Delegación de Eventos en Tablas (Edición y Acciones Rápidas)
    cuerpoTablaCompetencias.addEventListener('click', async (e) => {
        const btnEditar = e.target.closest('.btn-editar-competencia');
        const btnEliminar = e.target.closest('.btn-eliminar-competencia');

        if (btnEditar) {
            const tr = btnEditar.closest('tr');
            const id = parseInt(tr.getAttribute('data-id'), 10);
            await abrirEditarCompetencia(id);
        }

        if (btnEliminar) {
            const tr = btnEliminar.closest('tr');
            const id = parseInt(tr.getAttribute('data-id'), 10);
            await procesarEliminarCompetencia(id);
        }
    });

    cuerpoTablaPeriodos.addEventListener('click', async (e) => {
        const btnEditar = e.target.closest('.btn-editar-periodo');
        const btnCerrar = e.target.closest('.btn-cerrar-periodo');

        if (btnEditar) {
            const tr = btnEditar.closest('tr');
            const id = parseInt(tr.getAttribute('data-id'), 10);
            await abrirEditarPeriodo(id);
        }

        if (btnCerrar) {
            const id = parseInt(btnCerrar.getAttribute('data-id'), 10);
            await procesarCierrePeriodo(id);
        }
    });

    // 5. Envío y Procesamiento de Formularios vía AJAX
    formCompetencia.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            id: parseInt(document.getElementById('comp-id').value, 10),
            nombre: document.getElementById('comp-nombre').value.trim(),
            bloque_id: parseInt(document.getElementById('comp-bloque-id').value, 10),
            descripcion: document.getElementById('comp-descripcion').value.trim(),
            activo: parseInt(document.getElementById('comp-activo').value, 10)
        };

        try {
            const response = await fetch('/api/catalogo/competencia', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify(payload)
            });
            const res = await response.json();

            if (res.status === 'success') {
                showToast(res.message, 'success');
                compModal.hide();
                await cargarCompetenciasAPI();
            } else {
                showToast(res.message || 'Error al guardar competencia.', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de red al intentar guardar.', 'danger');
        }
    });

    formPeriodo.addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            id: parseInt(document.getElementById('periodo-id').value, 10),
            nombre: document.getElementById('periodo-nombre').value.trim(),
            fecha_inicio: document.getElementById('periodo-fecha-inicio').value,
            fecha_fin: document.getElementById('periodo-fecha-fin').value,
            escala_valoracion_id: parseInt(document.getElementById('periodo-escala').value, 10),
            activo: parseInt(document.getElementById('periodo-activo').value, 10),
            cerrado: parseInt(document.getElementById('periodo-cerrado').value, 10)
        };

        try {
            const response = await fetch('/api/catalogo/periodo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify(payload)
            });
            const res = await response.json();

            if (res.status === 'success') {
                showToast(res.message, 'success');
                perModal.hide();
                await cargarPeriodosAPI();
            } else {
                showToast(res.message || 'Error al guardar el periodo.', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de red al intentar guardar.', 'danger');
        }
    });

    /**
     * Carga todos los catálogos maestros en frío
     */
    async function inicializarCatalogos() {
        await cargarCompetenciasAPI();
        await cargarPeriodosAPI();
    }

    /**
     * Consume la API de Competencias y Redibuja la Tabla
     */
    async function cargarCompetenciasAPI() {
        try {
            const response = await fetch('/api/catalogo/competencias');
            const res = await response.json();

            if (res.status === 'success') {
                listCompetencias = res.competencias || [];
                listBloques = res.bloques || [];
                renderTablaCompetencias(listCompetencias);
            }
        } catch (error) {
            console.error('Error al cargar competencias:', error);
        }
    }

    /**
     * Consume la API de Periodos y Redibuja la Tabla
     */
    async function cargarPeriodosAPI() {
        try {
            const response = await fetch('/api/catalogo/periodos');
            const res = await response.json();

            if (res.status === 'success') {
                listPeriodos = res.periodos || [];
                listEscalas = res.escalas || [];
                renderTablaPeriodos(listPeriodos);
            }
        } catch (error) {
            console.error('Error al cargar periodos:', error);
        }
    }

    /**
     * Renderiza el listado HTML de Competencias
     */
    function renderTablaCompetencias(competencias) {
        cuerpoTablaCompetencias.innerHTML = '';

        competencias.forEach(c => {
            const row = document.createElement('tr');
            row.setAttribute('data-id', c.id);
            row.innerHTML = `
                <td class="ps-3 font-monospace">${c.id}</td>
                <td><strong class="text-slate-800">${escapeHTML(c.nombre)}</strong></td>
                <td>
                    <span class="badge bg-indigo-subtle text-indigo px-2 py-1">
                        ${escapeHTML(c.bloque_nombre)}
                    </span>
                </td>
                <td class="text-muted small">${escapeHTML(c.descripcion || '')}</td>
                <td class="text-center">
                    <span class="badge ${parseInt(c.activo, 10) === 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} px-2 py-1 text-uppercase">
                        ${parseInt(c.activo, 10) === 1 ? 'Activo' : 'Desactivado'}
                    </span>
                </td>
                <td class="text-center pe-3">
                    <div class="d-inline-flex gap-1">
                        <button class="btn btn-outline-secondary btn-sm btn-editar-competencia" title="Editar competencia">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm btn-eliminar-competencia" title="Desactivar competencia">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </div>
                </td>
            `;
            cuerpoTablaCompetencias.appendChild(row);
        });
    }

    /**
     * Renderiza el listado HTML de Periodos Evaluativos
     */
    function renderTablaPeriodos(periodos) {
        cuerpoTablaPeriodos.innerHTML = '';

        periodos.forEach(p => {
            const row = document.createElement('tr');
            row.setAttribute('data-id', p.id);

            const labelActivo = parseInt(p.activo, 10) === 1 
                ? '<span class="badge bg-success ms-2 small">Activo Actual</span>' 
                : '';

            const lockButton = parseInt(p.cerrado, 10) === 0
                ? `<button class="btn btn-outline-danger btn-sm btn-cerrar-periodo" title="Cerrar periodo definitivamente" data-id="${p.id}"><i class="bi bi-lock-fill"></i></button>`
                : '';

            row.innerHTML = `
                <td class="ps-3 font-monospace">${p.id}</td>
                <td>
                    <strong class="text-slate-800">${escapeHTML(p.nombre)}</strong>
                    ${labelActivo}
                </td>
                <td class="text-center font-monospace small">${p.fecha_inicio}</td>
                <td class="text-center font-monospace small">${p.fecha_fin}</td>
                <td class="text-center">
                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                        Escala: ${escapeHTML(p.escala_nombre)}
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge ${parseInt(p.cerrado, 10) === 1 ? 'bg-danger text-white' : 'bg-success text-white'} px-2 py-1 text-uppercase fs-8 fw-semibold">
                        ${parseInt(p.cerrado, 10) === 1 ? 'CERRADO' : 'ABIERTO'}
                    </span>
                </td>
                <td class="text-center pe-3">
                    <div class="d-inline-flex gap-1">
                        <button class="btn btn-outline-secondary btn-sm btn-editar-periodo" title="Editar periodo">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        ${lockButton}
                    </div>
                </td>
            `;
            cuerpoTablaPeriodos.appendChild(row);
        });
    }

    /**
     * Carga y rellena el modal de edición de competencia
     */
    async function abrirEditarCompetencia(id) {
        const comp = listCompetencias.find(c => parseInt(c.id, 10) === id);
        if (!comp) return;

        document.getElementById('comp-id').value = comp.id;
        document.getElementById('comp-nombre').value = comp.nombre;
        document.getElementById('comp-bloque-id').value = comp.bloque_id;
        document.getElementById('comp-descripcion').value = comp.descripcion || '';
        document.getElementById('comp-activo').value = comp.activo;

        document.getElementById('modalCompetenciaLabel').textContent = 'Editar Competencia';
        compModal.show();
    }

    /**
     * Carga y rellena el modal de edición de periodo
     */
    async function abrirEditarPeriodo(id) {
        const per = listPeriodos.find(p => parseInt(p.id, 10) === id);
        if (!per) return;

        document.getElementById('periodo-id').value = per.id;
        document.getElementById('periodo-nombre').value = per.nombre;
        document.getElementById('periodo-fecha-inicio').value = per.fecha_inicio;
        document.getElementById('periodo-fecha-fin').value = per.fecha_fin;
        document.getElementById('periodo-escala').value = per.escala_valoracion_id;
        document.getElementById('periodo-activo').value = per.activo;
        document.getElementById('periodo-cerrado').value = per.cerrado;

        document.getElementById('modalPeriodoLabel').textContent = 'Editar Periodo Evaluativo';
        perModal.show();
    }

    /**
     * Lógica de eliminación lógica para la competencia
     */
    async function procesarEliminarCompetencia(id) {
        const confirmed = confirm('¿Estás seguro de que deseas desactivar lógicamente esta competencia, socio?');
        if (!confirmed) return;

        try {
            const response = await fetch('/api/catalogo/competencia/eliminar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({ id })
            });
            const res = await response.json();

            if (res.status === 'success') {
                showToast(res.message, 'success');
                await cargarCompetenciasAPI();
            } else {
                showToast(res.message || 'Error al desactivar competencia.', 'danger');
            }
        } catch (error) {
            console.error('Error al eliminar:', error);
            showToast('Error de red al intentar desactivar.', 'danger');
        }
    }

    /**
     * Realiza el cierre definitivo de un periodo (Bloqueo transaccional)
     */
    async function procesarCierrePeriodo(id) {
        const confirmed = confirm('¿Estás seguro de que deseas CERRAR este periodo definitivamente, parce?\n\nADVERTENCIA: Esta acción es irreversible. Se bloqueará cualquier inserción o edición de calificaciones para evaluaciones registradas en este ciclo.');
        if (!confirmed) return;

        const per = listPeriodos.find(p => parseInt(p.id, 10) === id);
        if (!per) return;

        // Construir payload idéntico para cierre seguro
        const payload = {
            id: per.id,
            nombre: per.nombre,
            fecha_inicio: per.fecha_inicio,
            fecha_fin: per.fecha_fin,
            escala_valoracion_id: per.escala_valoracion_id,
            activo: 0, // Lo inactivamos para que no reciba nuevas
            cerrado: 1  // Bloqueo estricto activado en BD
        };

        try {
            const response = await fetch('/api/catalogo/periodo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify(payload)
            });
            const res = await response.json();

            if (res.status === 'success') {
                showToast('Periodo cerrado y bloqueado permanentemente, parce.', 'success');
                await cargarPeriodosAPI();
            } else {
                showToast(res.message || 'Error al cerrar el periodo.', 'danger');
            }
        } catch (error) {
            console.error('Error al cerrar periodo:', error);
            showToast('Error de red al procesar el cierre.', 'danger');
        }
    }

    /**
     * Mensajes Toast premium
     */
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
