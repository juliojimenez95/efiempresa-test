<?php
declare(strict_types=1);

/**
 * Vista de Parametrización de Catálogos (CRUD de Competencias, Periodos y Escalas)
 * Módulo: Gestor de Competencias ERP
 */

$container = \App\Core\Container::getInstance();
$competenciaRepo = $container->get(\App\Repositories\Contracts\CompetenciaRepositoryInterface::class);
$periodoRepo = $container->get(\App\Repositories\Contracts\PeriodoRepositoryInterface::class);

try {
    $competencias = $competenciaRepo->findAll();
    $bloques = $competenciaRepo->findBloques();
    $periodos = $periodoRepo->findAll();
    $escalas = $periodoRepo->findEscalas();
} catch (\Throwable $e) {
    $competencias = [];
    $bloques = [];
    $periodos = [];
    $escalas = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Catálogos | Gestor de Competencias</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Hojas de estilos del módulo -->
    <link rel="stylesheet" href="/css/evaluacion.css">
    <link rel="stylesheet" href="/css/catalogo.css">
</head>
<body class="py-4">

    <!-- Contenedor para notificaciones Toast flotantes -->
    <div id="toast-container"></div>

    <div class="container-fluid max-width-lg px-4">
        <!-- Cabecera principal -->
        <header class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="text-uppercase tracking-wider text-muted fw-semibold small">Configuración Paramétrica</span>
                <h1 class="h3 fw-bold m-0 text-slate-800">Mantenimiento de Catálogos</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="/evaluacion" class="btn btn-indigo btn-sm text-white" style="background-color: #4f46e5;">
                    <i class="bi bi-person-fill-gear me-1"></i> Nueva Evaluación
                </a>
                <a href="/matriz" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> Matriz Resumen
                </a>
            </div>
        </header>

        <!-- Pestañas de Mantenimiento Modular -->
        <div class="card app-card mb-4">
            <div class="card-header bg-white p-0">
                <ul class="nav nav-tabs nav-tabs-premium border-0" id="catalogoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-competencias" data-bs-toggle="tab" data-bs-target="#pane-competencias" type="button" role="tab" aria-controls="pane-competencias" aria-selected="true">
                            <i class="bi bi-trophy-fill me-2"></i>Competencias
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-periodos" data-bs-toggle="tab" data-bs-target="#pane-periodos" type="button" role="tab" aria-controls="pane-periodos" aria-selected="false">
                            <i class="bi bi-calendar-event-fill me-2"></i>Periodos Evaluativos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-escalas" data-bs-toggle="tab" data-bs-target="#pane-escalas" type="button" role="tab" aria-controls="pane-escalas" aria-selected="false">
                            <i class="bi bi-sliders2-vertical me-2"></i>Escalas y Semáforos
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="catalogoTabsContent">
                    
                    <!-- PANE 1: MANTENIMIENTO DE COMPETENCIAS -->
                    <div class="tab-pane fade show active" id="pane-competencias" role="tabpanel" aria-labelledby="tab-competencias">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold m-0 text-slate-800">Catálogo de Competencias</h5>
                                <p class="text-muted small m-0">Gestiona las competencias técnicas y blandas asignadas a los perfiles de puestos.</p>
                            </div>
                            <button class="btn btn-indigo btn-sm text-white" id="btn-nueva-competencia" style="background-color: #4f46e5;">
                                <i class="bi bi-plus-lg me-1"></i> Nueva Competencia
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-slate-100" id="tabla-competencias">
                                <thead class="table-light text-secondary fw-semibold">
                                    <tr>
                                        <th class="ps-3" style="width: 5%;">ID</th>
                                        <th style="width: 25%;">Competencia</th>
                                        <th style="width: 20%;">Bloque / Familia</th>
                                        <th style="width: 30%;">Descripción</th>
                                        <th style="width: 10%; text-align: center;">Estado</th>
                                        <th class="pe-3" style="width: 10%; text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpo-tabla-competencias">
                                    <!-- PHP pre-renderizado para carga inmediata -->
                                    <?php foreach ($competencias as $c): ?>
                                        <tr data-id="<?= (int) $c['id'] ?>">
                                            <td class="ps-3 font-monospace"><?= (int) $c['id'] ?></td>
                                            <td><strong class="text-slate-800"><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td>
                                                <span class="badge bg-indigo-subtle text-indigo px-2 py-1">
                                                    <?= htmlspecialchars($c['bloque_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?= htmlspecialchars($c['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= (int) $c['activo'] === 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> px-2 py-1 text-uppercase">
                                                    <?= (int) $c['activo'] === 1 ? 'Activo' : 'Desactivado' ?>
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
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- PANE 2: MANTENIMIENTO DE PERIODOS -->
                    <div class="tab-pane fade" id="pane-periodos" role="tabpanel" aria-labelledby="tab-periodos">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold m-0 text-slate-800">Ciclos y Periodos Evaluativos</h5>
                                <p class="text-muted small m-0">Administra las fechas de evaluación de talento. Un periodo cerrado bloqueará futuras actualizaciones.</p>
                            </div>
                            <button class="btn btn-indigo btn-sm text-white" id="btn-nuevo-periodo" style="background-color: #4f46e5;">
                                <i class="bi bi-calendar-plus me-1"></i> Nuevo Periodo
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-slate-100" id="tabla-periodos">
                                <thead class="table-light text-secondary fw-semibold">
                                    <tr>
                                        <th class="ps-3" style="width: 5%;">ID</th>
                                        <th style="width: 25%;">Periodo</th>
                                        <th style="width: 15%; text-align: center;">Fecha Inicio</th>
                                        <th style="width: 15%; text-align: center;">Fecha Fin</th>
                                        <th style="width: 20%; text-align: center;">Escala Aplicada</th>
                                        <th style="width: 10%; text-align: center;">Estado</th>
                                        <th class="pe-3" style="width: 10%; text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpo-tabla-periodos">
                                    <?php foreach ($periodos as $p): ?>
                                        <tr data-id="<?= (int) $p['id'] ?>">
                                            <td class="ps-3 font-monospace"><?= (int) $p['id'] ?></td>
                                            <td>
                                                <strong class="text-slate-800"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <?php if ((int) $p['activo'] === 1): ?>
                                                    <span class="badge bg-success ms-2 small">Activo Actual</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center font-monospace small"><?= htmlspecialchars($p['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center font-monospace small"><?= htmlspecialchars($p['fecha_fin'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                                    Escala: <?= htmlspecialchars($p['escala_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= (int) $p['cerrado'] === 1 ? 'bg-danger text-white' : 'bg-success text-white' ?> px-2 py-1 text-uppercase fs-8 fw-semibold">
                                                    <?= (int) $p['cerrado'] === 1 ? 'CERRADO' : 'ABIERTO' ?>
                                                </span>
                                            </td>
                                            <td class="text-center pe-3">
                                                <div class="d-inline-flex gap-1">
                                                    <button class="btn btn-outline-secondary btn-sm btn-editar-periodo" title="Editar periodo">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <?php if ((int) $p['cerrado'] === 0): ?>
                                                        <button class="btn btn-outline-danger btn-sm btn-cerrar-periodo" title="Cerrar periodo definitivamente" data-id="<?= (int) $p['id'] ?>">
                                                            <i class="bi bi-lock-fill"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- PANE 3: ESCALAS Y SEMÁFOROS -->
                    <div class="tab-pane fade" id="pane-escalas" role="tabpanel" aria-labelledby="tab-escalas">
                        <div class="mb-4">
                            <h5 class="fw-bold m-0 text-slate-800">Configuración de Escalas de Valoración</h5>
                            <p class="text-muted small m-0">Reglas matemáticas y umbrales semánticos aplicados en el cálculo del GAP corporativo.</p>
                        </div>

                        <!-- Detalle de Escalas activas en el Sistema -->
                        <div class="row g-4 mb-4">
                            <?php foreach ($escalas as $esc): ?>
                                <div class="col-md-6">
                                    <div class="card p-4 border-slate-200 shadow-sm h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-indigo-subtle text-indigo px-3 py-1 fw-bold text-uppercase">Escala Oficial</span>
                                            <span class="text-muted small font-monospace">ID: <?= (int) $esc['id'] ?></span>
                                        </div>
                                        <h5 class="fw-bold text-slate-800 mb-1"><?= htmlspecialchars($esc['nombre'], ENT_QUOTES, 'UTF-8') ?></h5>
                                        <p class="text-muted small mb-3"><?= htmlspecialchars($esc['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="p-3 bg-light rounded border">
                                            <div class="row text-center">
                                                <div class="col-6 border-end">
                                                    <span class="text-muted small d-block mb-1">Mínimo Permitido</span>
                                                    <span class="h4 fw-bold text-dark font-monospace"><?= (float) $esc['valor_min'] ?></span>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block mb-1">Máximo Permitido</span>
                                                    <span class="h4 fw-bold text-dark font-monospace"><?= (float) $esc['valor_max'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Visualización Interactiva del Patrón Strategy para Semáforos -->
                        <div class="card border-slate-200 shadow-sm p-4">
                            <h6 class="fw-bold mb-3 text-slate-800">
                                <i class="bi bi-palette-fill me-2 text-indigo"></i>Estrategia del Semáforo Porcentual (`SemaforoPorcentualStrategy`)
                            </h6>
                            <p class="text-muted small">
                                Los colores del semáforo se calculan de manera porcentual ponderando la brecha (GAP) contra la escala máxima configurada del periodo:
                            </p>
                            <div class="row g-3 text-center mt-2">
                                <div class="col-md-4">
                                    <div class="semaforo-preview-card p-3">
                                        <div class="semaforo-dot-large semaforo--verde mb-2 mx-auto">V</div>
                                        <h6 class="fw-bold mb-1 text-slate-800">Verde (Cumplido)</h6>
                                        <span class="badge bg-success-subtle text-success fs-7">Rango: >= 85%</span>
                                        <p class="text-muted small m-0 mt-2">El colaborador alcanza o supera las expectativas ideales del puesto.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="semaforo-preview-card p-3">
                                        <div class="semaforo-dot-large semaforo--amarillo mb-2 mx-auto">A</div>
                                        <h6 class="fw-bold mb-1 text-slate-800">Amarillo (Desarrollo)</h6>
                                        <span class="badge bg-warning-subtle text-warning fs-7">Rango: 60% a 84.9%</span>
                                        <p class="text-muted small m-0 mt-2">Brecha leve por mejorar. Requiere mentoring o plan de capacitación.</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="semaforo-preview-card p-3">
                                        <div class="semaforo-dot-large semaforo--rojo mb-2 mx-auto">R</div>
                                        <h6 class="fw-bold mb-1 text-slate-800">Rojo (Crítico)</h6>
                                        <span class="badge bg-danger-subtle text-danger fs-7">Rango: < 60%</span>
                                        <p class="text-muted small m-0 mt-2">Brecha crítica alta. Afecta severamente los procesos del puesto.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DINÁMICO: CREAR/EDITAR COMPETENCIA -->
    <div class="modal fade" id="modal-competencia" tabindex="-1" aria-labelledby="modalCompetenciaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="form-competencia" class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white p-3">
                    <h5 class="modal-title fw-bold" id="modalCompetenciaLabel">Competencia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="comp-id" name="id" value="0">
                    
                    <div class="mb-3">
                        <label for="comp-nombre" class="form-label fw-bold text-secondary small">Nombre de la Competencia</label>
                        <input type="text" class="form-control border-slate-300" id="comp-nombre" name="nombre" required placeholder="Ej: Liderazgo Operativo">
                    </div>
                    
                    <div class="mb-3">
                        <label for="comp-bloque-id" class="form-label fw-bold text-secondary small">Bloque o Familia Competencial</label>
                        <select class="form-select border-slate-300" id="comp-bloque-id" name="bloque_id" required>
                            <option value="">-- Elige un bloque --</option>
                            <?php foreach ($bloques as $b): ?>
                                <option value="<?= (int) $b['id'] ?>"><?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="comp-descripcion" class="form-label fw-bold text-secondary small">Descripción de la Competencia</label>
                        <textarea class="form-control border-slate-300" id="comp-descripcion" name="descripcion" rows="3" placeholder="Define los comportamientos clave observados..."></textarea>
                    </div>

                    <div class="mb-1">
                        <label for="comp-activo" class="form-label fw-bold text-secondary small">Estado Activo</label>
                        <select class="form-select border-slate-300" id="comp-activo" name="activo">
                            <option value="1">Activo</option>
                            <option value="0">Desactivado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-indigo btn-sm text-white" style="background-color: #4f46e5;">Guardar Competencia</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DINÁMICO: CREAR/EDITAR PERIODO -->
    <div class="modal fade" id="modal-periodo" tabindex="-1" aria-labelledby="modalPeriodoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="form-periodo" class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white p-3">
                    <h5 class="modal-title fw-bold" id="modalPeriodoLabel">Periodo Evaluativo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="periodo-id" name="id" value="0">
                    
                    <div class="mb-3">
                        <label for="periodo-nombre" class="form-label fw-bold text-secondary small">Nombre del Ciclo</label>
                        <input type="text" class="form-control border-slate-300" id="periodo-nombre" name="nombre" required placeholder="Ej: Q3-2026">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="periodo-fecha-inicio" class="form-label fw-bold text-secondary small">Fecha Inicio</label>
                            <input type="date" class="form-control border-slate-300" id="periodo-fecha-inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-6">
                            <label for="periodo-fecha-fin" class="form-label fw-bold text-secondary small">Fecha Fin</label>
                            <input type="date" class="form-control border-slate-300" id="periodo-fecha-fin" name="fecha_fin" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="periodo-escala" class="form-label fw-bold text-secondary small">Escala de Valoración</label>
                        <select class="form-select border-slate-300" id="periodo-escala" name="escala_valoracion_id" required>
                            <option value="">-- Elige la escala --</option>
                            <?php foreach ($escalas as $e): ?>
                                <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label for="periodo-activo" class="form-label fw-bold text-secondary small">Activo Actual</label>
                            <select class="form-select border-slate-300" id="periodo-activo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="periodo-cerrado" class="form-label fw-bold text-secondary small">Cerrado (Bloqueo)</label>
                            <select class="form-select border-slate-300" id="periodo-cerrado" name="cerrado">
                                <option value="0">Abierto (Permite cambios)</option>
                                <option value="1">Cerrado (Solo lectura)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-indigo btn-sm text-white" style="background-color: #4f46e5;">Guardar Periodo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper JS via CDN (para Nav-Tabs y Modales) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script controlador asíncrono para el catálogo -->
    <script src="/js/catalogo.js" defer></script>
</body>
</html>
